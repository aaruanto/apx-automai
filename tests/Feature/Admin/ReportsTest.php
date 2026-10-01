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

function seedBooking(string $status, string $date): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'XYZ '.$unique, 'color' => 'Blue',
    ]);

    $service = Service::create([
        'name' => 'Service '.$unique, 'price' => 1500, 'duration' => 45,
    ]);

    return Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id,
        'service_id' => $service->id, 'booking_date' => $date,
        'booking_time' => '10:00', 'status' => $status,
        'reference_number' => 'APX-'.$unique,
    ]);
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
    seedBooking('confirmed', today()->toDateString());
    seedBooking('confirmed', today()->subDays(2)->toDateString());
    seedBooking('cancelled', today()->subDay()->toDateString());

    $response = $this->actingAs(reportAdmin())->get('/admin/reports')->assertOk();

    expect($response->viewData('totalBookings'))->toBe(3);
    expect($response->viewData('totalRevenue'))->toBe(3000.0);
    expect($response->viewData('cancelledCount'))->toBe(1);
    expect($response->viewData('serviceStats'))->toHaveCount(3);
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
