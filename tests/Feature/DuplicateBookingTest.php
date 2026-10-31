<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * One vehicle, one window.
 *
 * Nothing enforced this. The only gate was the three-mechanic capacity rule,
 * so the same account could book the same vehicle for the same service at the
 * same date and time three times before the fourth was refused — and the
 * refusal, when it came, said the slot was full rather than that they already
 * had a booking.
 *
 * The guard lives in BookingAvailability::reserve(), which every booking
 * creator goes through, so all three entry points inherit it and two
 * simultaneous submissions cannot both pass: it runs inside the transaction
 * that has already taken lockForUpdate() on the day's active bookings.
 *
 * Overlap, not an exact time match. The same car at 09:00 and again at 09:30
 * for a 60-minute job is the same mistake.
 */
beforeEach(function () {
    Mail::fake();
});

function dupService(string $name = 'Oil Change', int $minutes = 60): Service
{
    return Service::create(['name' => $name.' '.uniqid(), 'price' => 1500, 'duration' => $minutes]);
}

function dupCustomer(): User
{
    return User::factory()->create(['role' => 'customer', 'is_active' => true, 'is_guest' => false]);
}

function dupVehicle(User $user, string $plate): Vehicle
{
    return Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => $plate, 'color' => 'Blue', 'vehicle_type' => 'car',
    ]);
}

/** Book through the customer dashboard, the shortest of the three paths. */
/**
 * $confirmSameDay answers the "this car is already booked today — book this
 * one as well?" question the endpoint now asks before a second booking on one
 * date. It is scaffolding here: these tests are about the overlap rule, and
 * several of them need a second same-day booking to exist before the thing
 * they actually assert. See SameDayBookingWarningTest for the question itself.
 */
function dupBook(User $user, Vehicle $vehicle, Service $service, string $date, string $time, bool $confirmSameDay = false)
{
    return test()->actingAs($user)->postJson('/customer/bookings', [
        'service_ids'      => [$service->id],
        'vehicle_id'       => $vehicle->id,
        'booking_date'     => $date,
        'booking_time'     => $time,
        'confirm_same_day' => $confirmSameDay,
    ]);
}

// ── The rule ──────────────────────────────────────────────────────────────

it('refuses the same vehicle in an identical slot', function () {
    $user    = dupCustomer();
    $vehicle = dupVehicle($user, 'DUP 0001');
    $service = dupService();
    $date    = nextWeekday();

    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();
    dupBook($user, $vehicle, $service, $date, '10:00')->assertStatus(422);

    expect(Booking::count())->toBe(1);
});

it('refuses the same vehicle in a merely overlapping slot', function () {
    // 10:00 + 60 minutes runs to 11:00, so 10:30 is inside it. An exact-match
    // check would let this through, which is why the rule is overlap.
    $user    = dupCustomer();
    $vehicle = dupVehicle($user, 'OVL 0001');
    $service = dupService(minutes: 60);
    $date    = nextWeekday();

    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();
    dupBook($user, $vehicle, $service, $date, '10:30')->assertStatus(422);

    expect(Booking::count())->toBe(1);
});

it('names the booking already held, rather than claiming the slot is full', function () {
    // The difference between "you already have one" and "try another time" is
    // the difference between a customer checking their bookings and a customer
    // thinking the system is broken.
    $user    = dupCustomer();
    $vehicle = dupVehicle($user, 'MSG 0001');
    $service = dupService();
    $date    = nextWeekday();

    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();
    $clash = Booking::sole();

    $message = dupBook($user, $vehicle, $service, $date, '10:00')
        ->assertStatus(422)
        ->json('message');

    expect($message)->toContain('already has a booking')
        ->and($message)->toContain($clash->reference_number)
        ->and($message)->toContain('10:00 AM');
});

it('lets the slot be taken again once the earlier booking is cancelled', function () {
    // Cancelled is not an active status, so it occupies nothing — this is the
    // reason the guard is a query over ACTIVE_STATUSES and not a unique index.
    $user    = dupCustomer();
    $vehicle = dupVehicle($user, 'CAN 0001');
    $service = dupService();
    $date    = nextWeekday();

    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();
    Booking::sole()->update(['status' => 'cancelled']);

    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();

    expect(Booking::where('status', 'pending')->count())->toBe(1);
});

it('allows a different vehicle on the same account at the same time', function () {
    // A household with two cars booking both in for the morning is normal.
    $user    = dupCustomer();
    $first   = dupVehicle($user, 'TWO 0001');
    $second  = dupVehicle($user, 'TWO 0002');
    $service = dupService();
    $date    = nextWeekday();

    dupBook($user, $first, $service, $date, '10:00')->assertOk();
    dupBook($user, $second, $service, $date, '10:00')->assertOk();

    expect(Booking::count())->toBe(2);
});

it('allows the same vehicle at a time that does not overlap', function () {
    $user    = dupCustomer();
    $vehicle = dupVehicle($user, 'SEP 0001');
    $service = dupService(minutes: 60);
    $date    = nextWeekday();

    // 10:00–11:00 then 11:00–12:00. Touching, not overlapping.
    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();

    // The overlap rule still allows it — a job ending at 11:00 and one
    // starting at 11:00 do not clash, which is what this test is about.
    // What changed is that the endpoint now asks first, because one car
    // twice on one day is as often a mis-submission as it is deliberate.
    // The rule is unchanged; the question sits in front of it.
    dupBook($user, $vehicle, $service, $date, '11:00')->assertStatus(409);
    dupBook($user, $vehicle, $service, $date, '11:00', confirmSameDay: true)->assertOk();

    expect(Booking::count())->toBe(2);
});

// ── One guard, three entry points ─────────────────────────────────────────

it('refuses a duplicate booked through the admin screen', function () {
    $admin   = User::factory()->create(['role' => 'admin']);
    $service = dupService();
    $date    = nextWeekday();

    $payload = [
        'customer_name'  => 'Walk In',
        'customer_phone' => '09171234567',
        'plate'          => 'ADM 0001',
        'service_ids'    => [$service->id],
        'booking_date'   => $date,
        'booking_time'   => '10:00',
    ];

    $this->actingAs($admin)->post('/admin/bookings', $payload)->assertRedirect();
    expect(Booking::count())->toBe(1);

    // Same phone, so the same customer and the same vehicle are resolved.
    $this->actingAs($admin)->post('/admin/bookings', $payload)
        ->assertSessionHasErrors('booking_time');

    expect(Booking::count())->toBe(1);
});

it('refuses a duplicate booked through the guest form', function () {
    $service = dupService();
    $date    = nextWeekday();

    $payload = [
        'guest_name'    => 'Guest Person',
        'guest_email'   => 'guest.duplicate@gmail.com',
        'guest_phone'   => '09171234567',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'GST 0001',
        'vehicle_type'  => 'car',
        'service_ids'   => [$service->id],
        'booking_date'  => $date,
        'booking_time'  => '10:00',
    ];

    $this->postJson('/booking/guest', $payload)->assertOk();
    expect(Booking::count())->toBe(1);

    $this->postJson('/booking/guest', $payload)
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(Booking::count())->toBe(1);
});

// ── The capacity rule is untouched ────────────────────────────────────────

it('still fills a slot with three different vehicles, and refuses the fourth', function () {
    // The guard must not change what capacity means. Three cars fill three
    // bays; a fourth car is refused for capacity, not as a duplicate.
    $service = dupService(minutes: 60);
    $date    = nextWeekday();

    foreach (range(1, 3) as $i) {
        $user = dupCustomer();
        dupBook($user, dupVehicle($user, 'CAP 000'.$i), $service, $date, '10:00')->assertOk();
    }

    $fourth  = dupCustomer();
    $message = dupBook($fourth, dupVehicle($fourth, 'CAP 0004'), $service, $date, '10:00')
        ->assertStatus(422)
        ->json('message');

    expect(Booking::count())->toBe(3)
        ->and($message)->toContain('That time slot is unavailable')
        ->and($message)->not->toContain('already has a booking');
});

// ── Rescheduling onto a window the vehicle already holds ──────────────────

it('refuses moving a booking onto a window its own vehicle already holds', function () {
    $user    = dupCustomer();
    $vehicle = dupVehicle($user, 'MOV 0001');
    $service = dupService(minutes: 60);
    $date    = nextWeekday();

    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();

    // Two bookings on one day is the setup, not the subject — this is about
    // what happens when the afternoon one is moved onto the morning one.
    dupBook($user, $vehicle, $service, $date, '13:00', confirmSameDay: true)->assertOk();

    $afternoon = Booking::where('booking_time', 'like', '13:00%')->sole();

    expect(fn () => app(App\Services\BookingAvailability::class)
        ->reschedule($afternoon, $date, '10:30', 60))
        ->toThrow(App\Exceptions\SlotUnavailableException::class, 'already has a booking');
});

it('lets a booking be rescheduled within its own window', function () {
    // A booking is allowed to overlap itself, or no move would ever be legal
    // — the guard excludes the booking being moved.
    $user    = dupCustomer();
    $vehicle = dupVehicle($user, 'SLF 0001');
    $service = dupService(minutes: 60);
    $date    = nextWeekday();

    dupBook($user, $vehicle, $service, $date, '10:00')->assertOk();
    $booking = Booking::sole();

    expect(fn () => app(App\Services\BookingAvailability::class)
        ->reschedule($booking, $date, '10:30', 60))
        ->not->toThrow(App\Exceptions\SlotUnavailableException::class);
});
