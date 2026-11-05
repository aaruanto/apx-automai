<?php

/**
 * A guest booking that fails must leave nothing behind.
 *
 * The account was created at the top of store(), before the checks that can
 * refuse the booking. Every one of those returns 422 — a plate held by someone
 * else, a withdrawn bundle, a service that does not suit the vehicle,
 * complimentary work on its own, a slot that has gone — and the users row
 * stayed, with no booking attached to it.
 *
 * What that did to the customer is worse than the stray row. They were told
 * their booking failed, while silently holding an account they had never heard
 * of. No setup link was sent, because that only happens once a booking exists.
 * And /register then refused their address, because it was taken — by the
 * account the failed booking had made for them. There was no way out of that
 * from the front of the shop.
 *
 * Two of these are in the live data, both from 20 October.
 */

use App\Models\Bundle;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function orphanPaidService(): Service
{
    return Service::create(['name' => 'Paid '.uniqid(), 'price' => 1500, 'duration' => 45]);
}

function orphanFreeService(): Service
{
    return Service::create(['name' => 'Free '.uniqid(), 'price' => 0, 'duration' => 20]);
}

function attemptGuestBooking(array $overrides = []): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/booking/guest', array_merge([
        'guest_name'    => 'Juan Dela Cruz',
        'guest_email'   => 'orphan@example.com',
        'guest_phone'   => '09171234567',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'ORP 1111',
        'vehicle_type'  => 'car',
        'service_ids'   => [orphanPaidService()->id],
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
    ], $overrides));
}

/** Nothing of this attempt survives: no account, no vehicle, no booking. */
function expectNothingWasCreated(): void
{
    expect(User::where('email', 'orphan@example.com')->count())->toBe(0);
    expect(Vehicle::withTrashed()->where('plate_number', 'ORP 1111')->count())->toBe(0);
    expect(App\Models\Booking::count())->toBe(0);
}

// ── Each way a booking can be refused ────────────────────────────────────

it('creates no account when the booking is complimentary work alone', function () {
    // The pair in the live data are this case: the addresses are
    // freebie.tester.apx@ and allfree.apx@.
    attemptGuestBooking(['service_ids' => [orphanFreeService()->id]])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expectNothingWasCreated();
});

it('creates no account when the plate belongs to somebody else', function () {
    $owner = User::factory()->create(['role' => 'customer']);
    Vehicle::create([
        'user_id' => $owner->id, 'plate_number' => 'ORP 1111',
        'make' => 'Honda', 'model' => 'Civic', 'vehicle_type' => 'car', 'year' => 2020,
    ]);

    attemptGuestBooking()->assertStatus(422);

    // The stranger gets no account, and the owner keeps their one vehicle.
    expect(User::where('email', 'orphan@example.com')->count())->toBe(0);
    expect(Vehicle::where('plate_number', 'ORP 1111')->sole()->user_id)->toBe($owner->id);
    expect(App\Models\Booking::count())->toBe(0);
});

it('creates no account when the chosen bundle has been withdrawn', function () {
    attemptGuestBooking(['bundle_id' => 99999])->assertStatus(422);

    expectNothingWasCreated();
});

it('creates no account when a service does not suit the vehicle', function () {
    $carOnly = Service::create([
        'name' => 'Aircon Cleaning', 'price' => 2000, 'duration' => 60, 'vehicle_type' => 'car',
    ]);

    attemptGuestBooking([
        'service_ids' => [$carOnly->id], 'vehicle_type' => 'motorcycle',
    ])->assertStatus(422);

    expectNothingWasCreated();
});

it('creates no account when the slot is gone', function () {
    // Outside opening hours is the same refusal the picker guards against, and
    // it is thrown from reserve() — the last point at which this can fail, and
    // the one the transaction exists for.
    attemptGuestBooking(['booking_time' => '04:00'])->assertStatus(422);

    expectNothingWasCreated();
});

it('creates no account when the vehicle type is wrong for the bundle', function () {
    $bundle = Bundle::create([
        'name' => 'Motorcycle Care', 'price' => 2500,
        'vehicle_type' => 'motorcycle', 'is_active' => true,
    ]);
    $bundle->services()->attach(orphanPaidService()->id);

    attemptGuestBooking(['bundle_id' => $bundle->id, 'vehicle_type' => 'car'])
        ->assertStatus(422);

    expectNothingWasCreated();
});

// ── It must still do its job when the booking is good ────────────────────

it('still creates the account and the booking when the booking succeeds', function () {
    attemptGuestBooking()->assertOk()->assertJsonPath('success', true);

    $user = User::where('email', 'orphan@example.com')->sole();

    expect($user->is_guest)->toBeTrue();
    expect(App\Models\Booking::where('user_id', $user->id)->count())->toBe(1);
    expect(Vehicle::where('plate_number', 'ORP 1111')->sole()->user_id)->toBe($user->id);
});

it('leaves an existing guest untouched when their new booking is refused', function () {
    // The reorder must not start deleting accounts that were already there.
    attemptGuestBooking()->assertOk();

    $before = User::where('email', 'orphan@example.com')->sole();

    attemptGuestBooking([
        'booking_time' => '13:00', 'service_ids' => [orphanFreeService()->id],
    ])->assertStatus(422);

    $after = User::where('email', 'orphan@example.com')->sole();

    expect($after->id)->toBe($before->id);
    expect($after->is_guest)->toBeTrue();
    // Their first booking survives the second attempt's rollback.
    expect(App\Models\Booking::where('user_id', $after->id)->count())->toBe(1);
});

it('still refuses a guest booking on a registered account, creating nothing', function () {
    User::factory()->create([
        'email' => 'orphan@example.com', 'name' => 'Real Owner',
        'role' => 'customer', 'is_guest' => false,
    ]);

    attemptGuestBooking()->assertStatus(422);

    expect(User::where('email', 'orphan@example.com')->sole()->name)->toBe('Real Owner');
    expect(App\Models\Booking::count())->toBe(0);
});
