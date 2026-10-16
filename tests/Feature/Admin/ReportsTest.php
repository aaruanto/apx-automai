<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * The reports page returned a 500 on PostgreSQL because the top-services query
 * compared status against a double-quoted "confirmed", which Postgres reads as
 * a column name. These cover the page rendering at all, the period clamping,
 * and the degraded render that replaced the raw 500 page.
 */

function reportAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function seedBooking(string $status, string $date, int $price = 1500, ?Service $service = null): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'XYZ '.$unique, 'color' => 'Blue',
    ]);

    $service ??= Service::create([
        'name' => 'Service '.$unique, 'price' => $price, 'duration' => 45,
    ]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id,
        'service_id' => $service->id, 'booking_date' => $date,
        'booking_time' => '10:00', 'status' => $status,
        'reference_number' => 'APX-'.$unique,
    ]);

    // Every real booking gets pivot rows through attachServices; a helper that
    // skipped them was testing a shape the application never produces.
    $booking->services()->attach($service->id, ['price' => $price, 'duration' => 45]);

    return $booking;
}

it('renders with a completely empty database', function () {
    // Aggregates over no rows are their own failure class, so this must hold
    // before any data exists.
    $this->actingAs(reportAdmin())
        ->get('/admin/reports')
        ->assertOk()
        ->assertViewHas('serviceStats', [])
        ->assertViewMissing('reportError');
});

it('renders with bookings present and totals them', function () {
    seedBooking('completed', today()->toDateString());
    seedBooking('completed', today()->subDays(2)->toDateString());
    seedBooking('cancelled', today()->subDay()->toDateString());

    $response = $this->actingAs(reportAdmin())->get('/admin/reports')->assertOk();

    expect($response->viewData('totalBookings'))->toBe(3);
    // Revenue is completed work only, so the cancelled one contributes nothing.
    expect($response->viewData('totalRevenue'))->toBe(3000.0);
    expect($response->viewData('cancelledCount'))->toBe(1);
    expect($response->viewData('serviceStats'))->toHaveCount(3);
});

/*
 * Revenue used to be computed three ways that were each wrong. Every one of
 * these reproduced a confirmed failure before the fix.
 */

it('does not erase revenue when a booking is completed', function () {
    $booking = seedBooking('confirmed', today()->toDateString());
    $admin   = reportAdmin();

    // Confirmed work is not yet earned, so it should not count.
    expect($this->actingAs($admin)->get('/admin/reports')->viewData('totalRevenue'))->toBe(0.0);

    $booking->update(['status' => 'completed']);

    // It used to go the other way: 1500 while confirmed, 0 once finished.
    expect($this->actingAs($admin)->get('/admin/reports')->viewData('totalRevenue'))->toBe(1500.0);
});

it('reports what a booking was sold for, not the current price list', function () {
    $service = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 45]);
    seedBooking('completed', today()->toDateString(), 1500, $service);

    $service->update(['price' => 9999]);

    // Previously reported 9999: a price change rewrote history.
    expect($this->actingAs(reportAdmin())->get('/admin/reports')->viewData('totalRevenue'))
        ->toBe(1500.0);
});

it('counts every service on a booking, not just the first', function () {
    $oil     = Service::create(['name' => 'Oil', 'price' => 1000, 'duration' => 30]);
    $brakes  = Service::create(['name' => 'Brakes', 'price' => 2000, 'duration' => 30]);
    $booking = seedBooking('completed', today()->toDateString(), 1000, $oil);
    $booking->services()->attach($brakes->id, ['price' => 2000, 'duration' => 30]);

    // Previously 1000: the query joined the legacy single service_id.
    expect($this->actingAs(reportAdmin())->get('/admin/reports')->viewData('totalRevenue'))
        ->toBe(3000.0);
});

it('stops counting a deleted booking', function () {
    // A query-builder join bypasses the SoftDeletes scope, so this has to be
    // excluded by hand or a deleted booking keeps earning.
    $booking = seedBooking('completed', today()->toDateString());
    $booking->delete();

    expect($this->actingAs(reportAdmin())->get('/admin/reports')->viewData('totalRevenue'))
        ->toBe(0.0);
});

it('averages over completed bookings rather than all of them', function () {
    seedBooking('completed', today()->toDateString(), 2000);
    seedBooking('pending', today()->toDateString(), 2000);

    // 2000 / 1 completed, not 2000 / 2 bookings.
    expect($this->actingAs(reportAdmin())->get('/admin/reports')->viewData('avgBookingValue'))
        ->toBe(2000.0);
});

it('shows completed work in the status breakdown', function () {
    seedBooking('completed', today()->toDateString());

    // 'completed' was missing from this chart entirely, so finished work
    // appeared in no slice of it.
    expect($this->actingAs(reportAdmin())->get('/admin/reports')->viewData('statusCounts'))
        ->toHaveKey('completed');
});

it('accepts each period the selector offers', function (int $days) {
    $this->actingAs(reportAdmin())
        ->get('/admin/reports?period='.$days)
        ->assertOk()
        ->assertViewHas('period', $days);
})->with([7, 30, 90, 365]);

it('falls back to 30 days for a period it does not offer', function (string $period) {
    // An unbounded period reached subDays() and the chart loop, so period=100000
    // meant tens of thousands of queries and a timeout.
    $this->actingAs(reportAdmin())
        ->get('/admin/reports?period='.$period)
        ->assertOk()
        ->assertViewHas('period', 30);
})->with(['100000', 'abc', '-5', '0', '31']);

it('degrades to a visible message instead of a 500 when the query fails', function () {
    // Forcing a genuine SQL failure rather than mocking, so this exercises the
    // same path a Postgres incompatibility would.
    Schema::drop('services');

    $response = $this->actingAs(reportAdmin())->get('/admin/reports');

    $response->assertOk();
    expect($response->viewData('reportError'))->toContain('could not be generated');
    $response->assertSee('Report unavailable');

    // Degraded, not wrong: figures read zero rather than stale or random.
    expect($response->viewData('totalBookings'))->toBe(0);
    expect($response->viewData('serviceStats'))->toBe([]);
});

it('is closed to customers and guests', function () {
    $this->get('/admin/reports')->assertRedirect('/login');

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get('/admin/reports')
        ->assertStatus(403);
});
