<?php

use App\Models\Booking;
use App\Models\Bundle;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The discount for booking signed in rather than as a guest.
 *
 * Every figure here is decided on the server from database rows. The browser
 * sends what the customer chose; what that costs is never read from it.
 */

beforeEach(function () {
    Setting::flushCache();
    Setting::setMany(['account_discount_enabled' => true, 'account_discount_percent' => 5]);
});

function discountCustomer(string $plate = 'ACD 1111'): array
{
    $user    = User::factory()->create(['role' => 'customer', 'is_guest' => false]);
    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => $plate, 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    return [$user, $vehicle];
}

function discountSvc(float $price, int $duration = 30): Service
{
    return Service::create(['name' => 'Svc '.uniqid(), 'price' => $price, 'duration' => $duration]);
}

// ── Who gets it ──────────────────────────────────────────────────────────

it('discounts a booking made while signed in', function () {
    [$user, $vehicle] = discountCustomer();
    $service = discountSvc(2000);

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids' => [$service->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect((float) $booking->list_price)->toBe(2000.0);
    expect($booking->discount_percent)->toBe(5);
    expect((float) $booking->discount_amount)->toBe(100.0);
    // The discount reaches the snapshots, so every revenue query sees it.
    expect($booking->total_price)->toBe(1900.0);
});

it('charges a guest the full price', function () {
    $service = discountSvc(2000);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'walkin@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'ACD 2222', 'vehicle_type' => 'car',
        'service_ids' => [$service->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->discount_percent)->toBe(0);
    expect($booking->total_price)->toBe(2000.0);
});

it('gives nothing to a guest record the shop created', function () {
    // Otherwise booking as a guest would earn the discount meant for
    // actually registering.
    $guest = User::factory()->create(['role' => 'customer', 'is_guest' => true]);

    expect(app(BookingPricing::class)->discountPercentFor($guest))->toBe(0);
});

it('gives nothing while the discount is switched off', function () {
    Setting::setMany(['account_discount_enabled' => false]);
    [$user, $vehicle] = discountCustomer();
    $service = discountSvc(2000);

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids' => [$service->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    expect(Booking::sole()->total_price)->toBe(2000.0);
});

// ── Stacking with bundles ────────────────────────────────────────────────

it('applies the discount after the bundle price, in that order', function () {
    // Bundle 2000 (list 2300), then 5% off the bundle price.
    $bundle = Bundle::create(['name' => 'Care', 'price' => 2000, 'is_active' => true]);
    $bundle->services()->attach([discountSvc(1500)->id, discountSvc(800)->id]);
    [$user, $vehicle] = discountCustomer('ACD 3333');

    $this->actingAs($user)->postJson('/customer/bookings', [
        'bundle_id' => $bundle->id, 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect((float) $booking->bundle_price)->toBe(2000.0);
    expect((float) $booking->list_price)->toBe(2000.0);   // the bundle is the base
    expect((float) $booking->discount_amount)->toBe(100.0);
    expect($booking->total_price)->toBe(1900.0);
    // Not 2300 * 0.95 = 2185: the discount is taken off the bundle price,
    // not off what the services would have cost separately.
});

// ── The arithmetic ───────────────────────────────────────────────────────

it('spreads the discount so the snapshots sum to the charged total', function (array $prices, int $percent) {
    Setting::setMany(['account_discount_percent' => $percent]);
    [$user, $vehicle] = discountCustomer('ACD 4444');

    $services = collect($prices)->map(fn ($p) => discountSvc($p));

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids' => $services->pluck('id')->all(), 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking  = Booking::sole();
    $expected = round(array_sum($prices) * (100 - $percent) / 100, 2);

    expect($booking->total_price)->toBe($expected);
})->with([
    [[1500, 800], 5],
    [[333, 333, 334], 7],
    [[999, 1], 33],
    [[1000], 50],
]);

it('clamps a nonsense percentage rather than giving work away', function (int $stored, int $effective) {
    Setting::setMany(['account_discount_percent' => $stored]);
    $user = User::factory()->create(['role' => 'customer', 'is_guest' => false]);

    expect(app(BookingPricing::class)->discountPercentFor($user))->toBe($effective);
})->with([
    [150, 100],
    [-20, 0],
]);

// ── Not trusting the browser ─────────────────────────────────────────────

it('ignores a discount the browser tries to send', function () {
    [$user, $vehicle] = discountCustomer('ACD 5555');
    $service = discountSvc(2000);

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids' => [$service->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        // All of this is noise; the server prices from its own rows.
        'discount_percent' => 90,
        'discount_amount'  => 1800,
        'total'            => 1,
        'list_price'       => 1,
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->discount_percent)->toBe(5);
    expect($booking->total_price)->toBe(1900.0);
});

// ── Admin settings ───────────────────────────────────────────────────────

it('lets the admin change the percentage', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put('/admin/settings/booking', [
            'open_time' => '09:00', 'close_time' => '18:00',
            'weekend_open_time' => '09:00', 'weekend_close_time' => '12:00',
            'max_bookings' => 20, 'slot_duration' => 30,
            'account_discount_enabled' => '1', 'account_discount_percent' => 10,
        ])->assertRedirect();

    Setting::flushCache();
    expect((int) Setting::get('account_discount_percent'))->toBe(10);
});

it('refuses a percentage high enough to be a typo', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put('/admin/settings/booking', [
            'open_time' => '09:00', 'close_time' => '18:00',
            'weekend_open_time' => '09:00', 'weekend_close_time' => '12:00',
            'max_bookings' => 20, 'slot_duration' => 30,
            'account_discount_percent' => 90,
        ])->assertSessionHasErrors('account_discount_percent');
});

it('records a discount change in the audit trail', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'name' => 'The Admin']))
        ->put('/admin/settings/booking', [
            'open_time' => '09:00', 'close_time' => '18:00',
            'weekend_open_time' => '09:00', 'weekend_close_time' => '12:00',
            'max_bookings' => 20, 'slot_duration' => 30,
            'account_discount_enabled' => '1', 'account_discount_percent' => 15,
        ]);

    $log = App\Models\AuditLog::where('action', 'settings.account_discount_changed')->sole();
    expect($log->new_values['percent'])->toBe(15);
    expect($log->actor_name)->toBe('The Admin');
});

// ── The nudge ────────────────────────────────────────────────────────────

it('tells a visitor what signing in would save them', function () {
    $this->get('/')->assertOk()->assertSee('ACCOUNT_DISCOUNT', false);
});

it('does not dangle an offer that is switched off', function () {
    Setting::setMany(['account_discount_enabled' => false]);

    $this->get('/')->assertOk()->assertSee('"enabled":false', false);
});
