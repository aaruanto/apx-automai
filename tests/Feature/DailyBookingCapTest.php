<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * The shop's daily intake limit — Settings -> Max Bookings Per Day.
 *
 * This is the only cap of its kind in the project; there is no limit on the
 * number of user accounts. It is enforced inside BookingAvailability::reserve(),
 * which every path that creates a booking goes through, so it holds whether the
 * request came from the guest form, the customer portal or the admin panel.
 *
 * Bookings here are spread across different times on purpose. Three may overlap
 * one slot (MECHANIC_CAPACITY), so booking them all at one time would hit the
 * per-slot limit and prove nothing about the daily one.
 */
beforeEach(function () {
    Mail::fake();
    Setting::flushCache();
});

function capCustomer(): User
{
    return User::factory()->create(['role' => 'customer', 'is_guest' => false, 'is_active' => true]);
}

function capVehicle(User $user): Vehicle
{
    static $n = 100;

    return Vehicle::create([
        'user_id'      => $user->id,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2020,
        'plate_number' => 'CAP '.$n++,
        'vehicle_type' => 'car',
    ]);
}

function capService(): Service
{
    static $n = 1;

    return Service::create(['name' => 'Cap Svc '.$n++, 'price' => 1500, 'duration' => 30]);
}

/** Book through the real customer endpoint, one booking per time slot. */
function bookAt(User $user, Vehicle $vehicle, Service $service, string $date, string $time)
{
    return test()->actingAs($user)->postJson('/customer/bookings', [
        'service_ids'  => [$service->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => $date,
        'booking_time' => $time,
    ]);
}

it('accepts bookings right up to the cap', function () {
    Setting::set('max_bookings', 3);

    $service = capService();
    $date    = nextWeekdayAfter(3);

    foreach (['09:00', '10:00', '11:00'] as $time) {
        $user = capCustomer();
        bookAt($user, capVehicle($user), $service, $date, $time)->assertOk();
    }

    expect(Booking::whereDate('booking_date', $date)->count())->toBe(3);
});

it('refuses the one past the cap, and says why', function () {
    Setting::set('max_bookings', 3);

    $service = capService();
    $date    = nextWeekdayAfter(3);

    foreach (['09:00', '10:00', '11:00'] as $time) {
        $user = capCustomer();
        bookAt($user, capVehicle($user), $service, $date, $time)->assertOk();
    }

    $user = capCustomer();
    $response = bookAt($user, capVehicle($user), $service, $date, '13:00');

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('That date is unavailable');

    // Refused means not written, not written-then-hidden.
    expect(Booking::whereDate('booking_date', $date)->count())->toBe(3);
});

it('enforces the cap on the server, not just in the booking calendar', function () {
    Setting::set('max_bookings', 2);

    $service = capService();
    $date    = nextWeekdayAfter(4);

    foreach (['09:00', '10:00'] as $time) {
        $user = capCustomer();
        bookAt($user, capVehicle($user), $service, $date, $time)->assertOk();
    }

    // Posted straight at the endpoint, exactly as something bypassing the UI
    // would. The picker never offered this slot; that is not what stops it.
    $user = capCustomer();
    bookAt($user, capVehicle($user), $service, $date, '15:30')->assertStatus(422);

    expect(Booking::whereDate('booking_date', $date)->count())->toBe(2);
});

it('applies the same cap to guest bookings', function () {
    Setting::set('max_bookings', 2);

    $service = capService();
    $date    = nextWeekdayAfter(5);

    foreach (['09:00', '10:00'] as $time) {
        $user = capCustomer();
        bookAt($user, capVehicle($user), $service, $date, $time)->assertOk();
    }

    // A different controller entirely, and the limit still holds, because both
    // go through BookingAvailability::reserve().
    $this->postJson('/booking/guest', [
        'guest_name'    => 'Capped Guest',
        'guest_email'   => 'capped.guest@gmail.com',
        'booking_date'  => $date,
        'booking_time'  => '14:00',
        'service_ids'   => [$service->id],
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2020,
        'vehicle_plate' => 'GST 4321',
        'vehicle_type'  => 'car',
    ])->assertStatus(422);

    expect(Booking::whereDate('booking_date', $date)->count())->toBe(2);
});

it('counts only live bookings, so a cancellation frees a place', function () {
    Setting::set('max_bookings', 2);

    $service = capService();
    $date    = nextWeekdayAfter(6);

    $first = capCustomer();
    bookAt($first, capVehicle($first), $service, $date, '09:00')->assertOk();

    $second = capCustomer();
    bookAt($second, capVehicle($second), $service, $date, '10:00')->assertOk();

    // Full.
    $third = capCustomer();
    bookAt($third, capVehicle($third), $service, $date, '11:00')->assertStatus(422);

    Booking::whereDate('booking_date', $date)->first()->update(['status' => 'cancelled']);

    // A cancelled booking is not holding a place, so the day has room again.
    bookAt($third, capVehicle($third), $service, $date, '11:00')->assertOk();
});

it('treats a cap of zero as no limit at all', function () {
    Setting::set('max_bookings', 0);

    $service = capService();
    $date    = nextWeekdayAfter(7);

    foreach (['09:00', '10:00', '11:00', '13:00', '14:00'] as $time) {
        $user = capCustomer();
        bookAt($user, capVehicle($user), $service, $date, $time)->assertOk();
    }

    expect(Booking::whereDate('booking_date', $date)->count())->toBe(5);
});

it('has no limit on the number of user accounts', function () {
    // Recorded deliberately. Phase 4 asked where the user cap is enforced, and
    // the answer is that there is not one: registration and admin customer
    // creation both create accounts without counting what exists. If a cap is
    // ever wanted, this test is the place it will fail first.
    $before = User::count();

    foreach (range(1, 25) as $i) {
        User::factory()->create(['role' => 'customer']);
    }

    expect(User::count())->toBe($before + 25);
});
