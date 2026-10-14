<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function dashBooking(string $status, string $date, ?User $customer = null, ?Service $service = null, int $price = 1500): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer ??= User::factory()->create(['role' => 'customer']);
    $service  ??= Service::create(['name' => 'Dash '.$unique, 'price' => $price, 'duration' => 30]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'DSH '.$unique, 'color' => 'Red',
    ]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => $date, 'booking_time' => '10:00', 'duration' => 30,
        'status' => $status, 'reference_number' => 'APX-'.$unique,
    ]);
    $booking->services()->attach($service->id, ['price' => $price, 'duration' => 30]);

    return $booking;
}

// ── 7a: the Activity Log is gone ────────────────────────────────────────

it('no longer offers an Activity Log anywhere', function () {
    $this->actingAs(dashAdmin())->get('/admin/dashboard')->assertOk()
        ->assertDontSee('Activity Log');

    $this->actingAs(dashAdmin())->get('/admin/profile')->assertOk()
        ->assertDontSee('Recent Activity');
});

// ── 7b: three cards, each filtering the list ────────────────────────────

it('totals completed plus pending, not every booking', function () {
    dashBooking('completed', today()->toDateString());
    dashBooking('pending', today()->toDateString());
    // Neither completed nor pending, so it must not inflate the total.
    dashBooking('cancelled', today()->toDateString());

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard')->assertOk();

    expect($r->viewData('completed'))->toBe(1);
    expect($r->viewData('pending'))->toBe(1);
    expect($r->viewData('totalBookings'))->toBe(2);
});

it('links each card to the bookings list filtered by that status', function () {
    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard')->assertOk();

    // The Completed card used to point at status=confirmed, a different status.
    $r->assertSee(route('admin.bookings.index', ['status' => 'completed']), false);
    $r->assertSee(route('admin.bookings.index', ['status' => 'pending']), false);
});

it('preselects that status on the bookings list', function () {
    $this->actingAs(dashAdmin())->get('/admin/bookings?status=pending')->assertOk()
        ->assertSee('value="pending" selected', false);
});

// ── 7e: filters drive the whole page ────────────────────────────────────

it('filters the figures by date range', function () {
    dashBooking('completed', today()->toDateString());
    dashBooking('completed', today()->subMonths(6)->toDateString());

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard?'.http_build_query([
        'from' => today()->subDays(7)->toDateString(),
        'to'   => today()->toDateString(),
    ]))->assertOk();

    expect($r->viewData('completed'))->toBe(1);
});

it('filters by status', function () {
    dashBooking('completed', today()->toDateString());
    dashBooking('pending', today()->toDateString());

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard?status=completed')->assertOk();

    expect($r->viewData('completed'))->toBe(1);
    expect($r->viewData('pending'))->toBe(0);
});

it('filters by customer', function () {
    $mine   = User::factory()->create(['role' => 'customer']);
    $theirs = User::factory()->create(['role' => 'customer']);
    dashBooking('completed', today()->toDateString(), $mine);
    dashBooking('completed', today()->toDateString(), $theirs);

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard?customer='.$mine->id)->assertOk();

    expect($r->viewData('completed'))->toBe(1);
});

it('filters by service', function () {
    $oil   = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 30]);
    $brake = Service::create(['name' => 'Brakes', 'price' => 2500, 'duration' => 30]);
    dashBooking('completed', today()->toDateString(), null, $oil);
    dashBooking('completed', today()->toDateString(), null, $brake);

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard?service='.$oil->id)->assertOk();

    expect($r->viewData('completed'))->toBe(1);
});

it('applies the same filters to the live poll', function () {
    dashBooking('completed', today()->toDateString());
    dashBooking('pending', today()->toDateString());

    // Otherwise a refresh would quietly replace a filtered view with
    // unfiltered numbers.
    $json = $this->actingAs(dashAdmin())
        ->getJson('/admin/dashboard/live?status=completed')->assertOk();

    expect($json->json('stats.completed'))->toBe(1);
    expect($json->json('stats.pending'))->toBe(0);
});

it('ignores a nonsense filter rather than breaking', function (string $query) {
    $this->actingAs(dashAdmin())->get('/admin/dashboard?'.$query)->assertOk();
})->with([
    'status=notastatus',
    'from=not-a-date',
    'range=hourly',
    'service=abc',
    'customer=999999',
    'from=2026-12-31&to=2026-01-01',
]);

// ── 7c: the grouping toggle ─────────────────────────────────────────────

it('groups the chart by each interval offered', function (string $range) {
    dashBooking('completed', today()->toDateString());

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard?range='.$range)->assertOk();

    expect($r->viewData('chartLabels'))->not->toBeEmpty();
    expect(count($r->viewData('chartLabels')))->toBe(count($r->viewData('chartData')));
})->with(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);

it('counts bookings into the right bucket', function () {
    dashBooking('completed', today()->toDateString());
    dashBooking('completed', today()->toDateString());

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard?range=daily')->assertOk();

    // Two on the same day land in one bucket, and every value is a whole number.
    expect(array_sum($r->viewData('chartData')))->toBe(2);
    foreach ($r->viewData('chartData') as $v) {
        expect($v)->toBeInt();
    }
});

// ── 7d: revenue is revenue ──────────────────────────────────────────────

it('charts money actually taken, not the price list', function () {
    $oil = Service::create(['name' => 'Oil Change', 'price' => 1500, 'duration' => 30]);
    // Never booked, so it should contribute nothing.
    Service::create(['name' => 'Never Booked', 'price' => 9999, 'duration' => 30]);

    dashBooking('completed', today()->toDateString(), null, $oil, 1500);
    dashBooking('completed', today()->toDateString(), null, $oil, 1500);

    $r = $this->actingAs(dashAdmin())->get('/admin/dashboard')->assertOk();

    expect($r->viewData('revenueLabels'))->toContain('Oil Change');
    expect($r->viewData('revenueLabels'))->not->toContain('Never Booked');
    expect($r->viewData('revenueData')[0])->toBe(3000.0);
});

// ── 7f: a row opens its own booking ─────────────────────────────────────

it('opens one booking from the recent list', function () {
    $booking = dashBooking('pending', today()->toDateString());

    $this->actingAs(dashAdmin())->get('/admin/dashboard')->assertOk()
        ->assertSee(route('admin.bookings.edit', $booking->id), false);
});

it('keeps the dashboard to admins', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get('/admin/dashboard')->assertStatus(403);
});
