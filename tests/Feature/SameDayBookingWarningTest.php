<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The same car twice on one day, at times that do not overlap.
 *
 * Overlapping is already refused outright by assertVehicleFree(), inside
 * reserve()'s transaction and after the row lock, so two submissions arriving
 * together cannot both pass. What survives is the accidental double-booking:
 * the customer submits, does not see the confirmation, comes back and picks a
 * different time. Nothing overlaps, so nothing objects.
 *
 * It is a warning and not a refusal, because one car on one day can be
 * deliberate — a wash in the morning and the aircon in the afternoon is a real
 * thing to want. The customer is told and asked, and a confirmed booking goes
 * through unchanged.
 */

function sdwCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function sdwVehicle(User $owner, string $plate = 'SDW 1001'): Vehicle
{
    return Vehicle::create([
        'user_id' => $owner->id, 'plate_number' => $plate,
        'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021, 'vehicle_type' => 'car',
    ]);
}

function sdwService(int $duration = 60): Service
{
    return Service::create([
        'name' => 'Same Day '.$duration, 'price' => 1200, 'duration' => $duration,
    ]);
}

/** Book through the customer's own path, the way the dashboard does. */
function sdwBook(User $customer, Vehicle $vehicle, Service $service, string $date, string $time, array $extra = [])
{
    return test()->actingAs($customer)->postJson('/customer/bookings', array_merge([
        'service_ids'  => [$service->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => $date,
        'booking_time' => $time,
    ], $extra));
}

// ── the gap ───────────────────────────────────────────────────────────────

it('warns when the same vehicle is already booked that day', function () {
    $customer = sdwCustomer();
    $vehicle  = sdwVehicle($customer);
    $service  = sdwService();
    $date     = nextWeekday();

    sdwBook($customer, $vehicle, $service, $date, '09:00')->assertOk();

    // 14:00 does not overlap 09:00–10:00, so every existing check passes and
    // the second booking used to be created in silence.
    $second = sdwBook($customer, $vehicle, $service, $date, '14:00');

    $second->assertStatus(409);
    expect($second->json('needs_confirmation'))->toBeTrue();
    expect($second->json('message'))->toContain('already has a booking');
    expect($second->json('message'))->toContain(Booking::first()->reference_number);
    expect($second->json('message'))->toContain('9:00 AM');

    // Warned, not booked — the customer has not answered yet.
    expect(Booking::count())->toBe(1);
});

it('books it anyway once the customer confirms', function () {
    $customer = sdwCustomer();
    $vehicle  = sdwVehicle($customer);
    $service  = sdwService();
    $date     = nextWeekday();

    sdwBook($customer, $vehicle, $service, $date, '09:00')->assertOk();
    sdwBook($customer, $vehicle, $service, $date, '14:00')->assertStatus(409);

    // One car on one day can be deliberate. Answering yes books it.
    sdwBook($customer, $vehicle, $service, $date, '14:00', ['confirm_same_day' => true])
        ->assertOk();

    expect(Booking::count())->toBe(2);
});

// ── what must not change ──────────────────────────────────────────────────

it('still refuses an overlapping booking outright', function () {
    $customer = sdwCustomer();
    $vehicle  = sdwVehicle($customer);
    $service  = sdwService(60);
    $date     = nextWeekday();

    sdwBook($customer, $vehicle, $service, $date, '09:00')->assertOk();

    // 09:30 lands inside 09:00–10:00. This is a refusal, not a question, and
    // confirming must not talk its way past it.
    $overlap = sdwBook($customer, $vehicle, $service, $date, '09:30');
    $overlap->assertStatus(422);
    expect($overlap->json('message'))->toContain('already has a booking at that time');

    sdwBook($customer, $vehicle, $service, $date, '09:30', ['confirm_same_day' => true])
        ->assertStatus(422);

    expect(Booking::count())->toBe(1);
});

it('says nothing when a second vehicle is booked the same day', function () {
    $customer = sdwCustomer();
    $service  = sdwService();
    $date     = nextWeekday();

    $car  = sdwVehicle($customer, 'SDW 2001');
    $bike = sdwVehicle($customer, 'SDW 2002');

    sdwBook($customer, $car, $service, $date, '09:00')->assertOk();

    // A household bringing two cars in on the same day is not a mistake, and
    // the rule is keyed on the vehicle, not the owner.
    sdwBook($customer, $bike, $service, $date, '14:00')->assertOk();

    expect(Booking::count())->toBe(2);
});

it('does not warn about a booking that was cancelled', function () {
    $customer = sdwCustomer();
    $vehicle  = sdwVehicle($customer);
    $service  = sdwService();
    $date     = nextWeekday();

    sdwBook($customer, $vehicle, $service, $date, '09:00')->assertOk();

    $first = Booking::first();
    app(\App\Services\BookingCancellation::class)->cancel($first, 'Changed my mind', $customer->id);

    // Rebooking the same car on the same day after cancelling is the normal
    // way to move an appointment. Only ACTIVE_STATUSES count.
    sdwBook($customer, $vehicle, $service, $date, '14:00')->assertOk();

    expect(Booking::where('status', '!=', 'cancelled')->count())->toBe(1);
});

// ── the lookup itself ────────────────────────────────────────────────────

it('ignores the booking being rescheduled when looking for a clash', function () {
    $customer = sdwCustomer();
    $vehicle  = sdwVehicle($customer);
    $service  = sdwService();
    $date     = nextWeekday();

    sdwBook($customer, $vehicle, $service, $date, '09:00')->assertOk();
    $booking = Booking::first();

    $availability = app(BookingAvailability::class);

    // Without the exclusion a booking moved within its own day would warn
    // about itself, which is both wrong and impossible to get past.
    expect($availability->vehicleBookedOn($vehicle->id, $date, $booking->id))->toBeNull();
    expect($availability->vehicleBookedOn($vehicle->id, $date)?->id)->toBe($booking->id);
});

it('reports the earliest booking of the day, not an arbitrary one', function () {
    $customer = sdwCustomer();
    $vehicle  = sdwVehicle($customer);
    $service  = sdwService();
    $date     = nextWeekday();

    sdwBook($customer, $vehicle, $service, $date, '14:00')->assertOk();
    sdwBook($customer, $vehicle, $service, $date, '09:00', ['confirm_same_day' => true])->assertOk();

    // The message names a time, so it should name the first one of the day
    // rather than whichever row the database happened to return.
    expect(app(BookingAvailability::class)->vehicleBookedOn($vehicle->id, $date)->booking_time)
        ->toBe('09:00');
});

// ── admin is looking at the customer ─────────────────────────────────────

it('does not warn an admin booking a walk-in', function () {
    $admin    = User::factory()->create(['role' => 'admin']);
    $service  = sdwService();
    $date     = nextWeekday();

    $payload = fn (string $time) => [
        'customer_name'  => 'Walk In',
        'customer_phone' => '09170005555',
        'plate'          => 'SDW 3001',
        'service_id'     => $service->id,
        'booking_date'   => $date,
        'booking_time'   => $time,
    ];

    $this->actingAs($admin)->post('/admin/bookings', $payload('09:00'))->assertRedirect();

    // The admin has the customer in front of them and does not need telling.
    $this->actingAs($admin)->post('/admin/bookings', $payload('14:00'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Booking::count())->toBe(2);
});
