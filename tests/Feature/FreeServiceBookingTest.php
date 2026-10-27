<?php

use App\Models\Bundle;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Complimentary services accompany paid work; they are not a booking of their
 * own.
 *
 * The catalogue carries three ₱0 services, and the shop's own wording already
 * stated the condition — "Complimentary exterior car wash with any paid
 * service". Nothing enforced it. A guest with no account could book a free car
 * wash and take a real half-hour slot for ₱0, and staff would find nothing to
 * do when the customer arrived.
 *
 * Not an abuse guard: no booking is paid up front, so paid services could fill
 * the calendar just as easily. This is the system honouring a policy the shop
 * had already published.
 */
beforeEach(function () {
    Mail::fake();
});

function paidService(?string $name = null): Service
{
    static $n = 1;

    return Service::create([
        'name'     => $name ?? 'Paid Svc '.$n++,
        'price'    => 1500,
        'duration' => 45,
    ]);
}

function freeService(?string $name = null): Service
{
    static $n = 1;

    return Service::create([
        'name'     => $name ?? 'Free Svc '.$n++,
        'price'    => 0,
        'duration' => 30,
    ]);
}

function freeSvcCustomer(): User
{
    return User::factory()->create(['role' => 'customer', 'is_guest' => false, 'is_active' => true]);
}

// ── The rule itself ───────────────────────────────────────────────────────

it('treats an all-free selection as free-only', function () {
    expect(BookingPolicy::isFreeOnly(collect([freeService(), freeService()])))->toBeTrue();
});

it('does not treat a selection containing any paid service as free-only', function () {
    expect(BookingPolicy::isFreeOnly(collect([freeService(), paidService()])))->toBeFalse();
});

it('never blocks a bundle, which is sold at its own price', function () {
    $bundle = Bundle::create(['name' => 'Test Bundle', 'price' => 2000, 'is_active' => true]);

    // Even if every service inside it happens to be complimentary.
    expect(BookingPolicy::isFreeOnly(collect([freeService(), freeService()]), $bundle))->toBeFalse();
});

it('leaves an empty selection to the validation that already rejects it', function () {
    // Answering true here would replace each path's clear "choose a service"
    // message with one about complimentary work, which explains nothing.
    expect(BookingPolicy::isFreeOnly(collect()))->toBeFalse();
});

// ── Guest booking ─────────────────────────────────────────────────────────

it('refuses a guest booking of only complimentary services', function () {
    $free = freeService('Free Car Wash');

    $this->postJson('/booking/guest', [
        'guest_name'    => 'Freebie Tester',
        'guest_email'   => 'freebie@gmail.com',
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
        'service_ids'   => [$free->id],
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2020,
        'vehicle_plate' => 'FRE 0001',
        'vehicle_type'  => 'car',
    ])->assertStatus(422)->assertJsonPath('message', BookingPolicy::FREE_ONLY_MESSAGE);

    expect(App\Models\Booking::count())->toBe(0);
});

it('allows a guest booking that pairs a complimentary service with paid work', function () {
    $paid = paidService('Change Oil');
    $free = freeService('Free Car Wash');

    $this->postJson('/booking/guest', [
        'guest_name'    => 'Mix Tester',
        'guest_email'   => 'mix@gmail.com',
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
        'service_ids'   => [$paid->id, $free->id],
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2020,
        'vehicle_plate' => 'MIX 0001',
        'vehicle_type'  => 'car',
    ])->assertOk()->assertJsonPath('success', true);

    expect(App\Models\Booking::count())->toBe(1);
});

// ── Customer portal booking ───────────────────────────────────────────────

it('refuses a customer booking of only complimentary services', function () {
    $customer = freeSvcCustomer();
    $free     = freeService('Free Car Wash');

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
        'plate_number' => 'CUS 0001', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [$free->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertStatus(422)->assertJsonPath('message', BookingPolicy::FREE_ONLY_MESSAGE);

    expect(App\Models\Booking::count())->toBe(0);
});

it('does not take a slot for a refused customer booking', function () {
    $customer = freeSvcCustomer();
    $free     = freeService('Free Car Wash');

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
        'plate_number' => 'CUS 0002', 'vehicle_type' => 'car',
    ]);

    // The check runs before reserve(), so nothing is written and nothing needs
    // cleaning up afterwards.
    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [$free->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(App\Models\Booking::withTrashed()->count())->toBe(0);
});

it('allows a customer booking that pairs a complimentary service with paid work', function () {
    $customer = freeSvcCustomer();
    $paid     = paidService('Change Oil');
    $free     = freeService('Free Car Wash');

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
        'plate_number' => 'CUS 0003', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [$paid->id, $free->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertOk();

    expect(App\Models\Booking::count())->toBe(1);
});

// ── What the mixed booking is actually worth ──────────────────────────────
//
// The two tests above prove a paid-plus-free booking is accepted. Neither
// looked at the money, so a free service that quietly picked up a price — or
// a paid one flattened to zero by sitting beside it — would have passed both.
// BookingPricing snapshots every price into booking_service.price, and that
// pivot is what reports and the customer's total spent read, so it is the
// thing worth asserting.

it('prices a guest booking so the complimentary service is free and the paid work is not', function () {
    $paid = paidService('Change Oil');
    $free = freeService('Free Car Wash');

    $this->postJson('/booking/guest', [
        'guest_name'    => 'Price Tester',
        'guest_email'   => 'price@gmail.com',
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
        'service_ids'   => [$paid->id, $free->id],
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2020,
        'vehicle_plate' => 'PRC 0001',
        'vehicle_type'  => 'car',
    ])->assertOk();

    $booking = App\Models\Booking::with('services')->sole();

    // Asserted before the prices below, and not only for its own sake: a
    // missing pivot row would make $prices[$free->id] null, which casts to
    // 0.0 and would pass the free-service assertion without the service
    // being on the booking at all.
    expect($booking->services)->toHaveCount(2);

    $prices = $booking->services->pluck('pivot.price', 'id');

    expect((float) $prices[$paid->id])->toBe(1500.0)
        ->and((float) $prices[$free->id])->toBe(0.0)
        // A guest gets no account discount, so the list price stands.
        ->and((float) $booking->list_price)->toBe(1500.0);
});

it('prices a customer booking the same way, and carries both services', function () {
    $customer = freeSvcCustomer();
    $paid     = paidService('Change Oil');
    $free     = freeService('Free Car Wash');

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
        'plate_number' => 'PRC 0002', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [$paid->id, $free->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertOk();

    $booking = App\Models\Booking::with('services')->sole();

    // The free service is on the booking, not silently dropped for costing
    // nothing — staff need to know to wash the car.
    expect($booking->services)->toHaveCount(2);

    $prices = $booking->services->pluck('pivot.price', 'id');

    expect((float) $prices[$paid->id])->toBe(1500.0)
        ->and((float) $prices[$free->id])->toBe(0.0);
});

it('counts the complimentary service toward how long the bay is held', function () {
    // 45 minutes of paid work plus a 30-minute wash is 75 minutes of someone's
    // day. Pricing it at zero must not shorten the slot.
    $customer = freeSvcCustomer();
    $paid     = paidService('Change Oil');
    $free     = freeService('Free Car Wash');

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
        'plate_number' => 'PRC 0003', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [$paid->id, $free->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertOk();

    expect((int) App\Models\Booking::sole()->duration)->toBe(75);
});
