<?php

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\RescheduleRequest;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use App\Services\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * An admin could move a customer's appointment outright — change the date on
 * the edit form, save, and the booking was on a different day. The customer
 * found out afterwards, if at all.
 *
 * Now the move is proposed: the booking keeps its date, its time and its
 * place in the day, the customer is emailed, and only the shop confirming
 * they agreed applies it.
 *
 * The proposal is its own row, not a booking status. Status is what
 * BookingAvailability::ACTIVE_STATUSES reads to decide whether a booking
 * occupies a mechanic, so a status like "awaiting approval" would have freed
 * the slot the customer still holds.
 */
beforeEach(function () {
    config(['mail.default' => 'array']);
});

function rsAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function rsBooking(array $attributes = []): Booking
{
    static $n = 1;

    $user = User::factory()->create([
        'role' => 'customer', 'is_active' => true, 'is_guest' => false,
        'email' => 'rs.customer'.$n.'@gmail.com',
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'RSX 000'.$n, 'color' => 'Blue', 'vehicle_type' => 'car',
    ]);

    $service = Service::create(['name' => 'Oil Change '.$n, 'price' => 1500, 'duration' => 60]);

    $booking = app(BookingAvailability::class)->reserve(array_merge([
        'user_id'      => $user->id,
        'vehicle_id'   => $vehicle->id,
        'service_ids'  => [$service->id],
        'booking_date' => nextWeekdayAfter(3),
        'booking_time' => '10:00',
        'status'       => 'confirmed',
    ], $attributes));

    $n++;

    return $booking->fresh();
}

/** Submit the admin edit form with a new date, as an admin would. */
function rsProposeMove(User $admin, Booking $booking, string $date, string $time = '14:00', ?string $reason = 'Mechanic called in sick')
{
    return test()->actingAs($admin)->put('/admin/bookings/'.$booking->id, array_filter([
        'service_ids'        => $booking->services->pluck('id')->all(),
        'booking_date'       => $date,
        'booking_time'       => $time,
        'status'             => $booking->status,
        'notes'              => $booking->notes,
        'reschedule_reason'  => $reason,
    ], fn ($v) => $v !== null));
}

function rsMail(): \Illuminate\Support\Collection
{
    return collect(Mail::getSymfonyTransport()->messages());
}

// ── A proposal does not move the booking ──────────────────────────────────

it('leaves the booking exactly where it was when a change is proposed', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    $originalDate = $booking->booking_date;
    $originalTime = $booking->booking_time;

    rsProposeMove($admin, $booking, nextWeekdayAfter(10));

    $fresh = $booking->fresh();

    expect($fresh->booking_date)->toBe($originalDate)
        ->and($fresh->booking_time)->toBe($originalTime)
        // The status is untouched, which is what keeps the slot held.
        ->and($fresh->status)->toBe('confirmed')
        ->and($fresh->hasPendingReschedule())->toBeTrue();
});

it('keeps the original slot occupied while a change is pending', function () {
    // The trap this design exists to avoid: a booking awaiting approval must
    // still count against capacity, or somebody else takes the time the
    // customer is still holding.
    $admin   = rsAdmin();
    $booking = rsBooking();

    $date = $booking->booking_date;
    $time = $booking->booking_time;

    rsProposeMove($admin, $booking, nextWeekdayAfter(10));

    $availability = app(BookingAvailability::class);
    [$start, $end] = $availability->windowFor($date, $time, 60);

    // Fill the other two bays, then a fourth must be refused — proving the
    // pending booking is still one of the three.
    foreach (range(1, 2) as $i) {
        rsBooking(['booking_date' => $date, 'booking_time' => $time]);
    }

    $extra = User::factory()->create(['role' => 'customer']);
    $veh   = Vehicle::create(['user_id' => $extra->id, 'make' => 'Honda', 'model' => 'City',
        'year' => 2020, 'plate_number' => 'RSX 9999', 'vehicle_type' => 'car']);
    $svc   = Service::create(['name' => 'Extra', 'price' => 900, 'duration' => 60]);

    expect(fn () => $availability->reserve([
        'user_id' => $extra->id, 'vehicle_id' => $veh->id, 'service_ids' => [$svc->id],
        'booking_date' => $date, 'booking_time' => $time, 'status' => 'pending',
    ]))->toThrow(App\Exceptions\SlotUnavailableException::class);
});

it('emails the customer that nothing has changed yet', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    rsProposeMove($admin, $booking, nextWeekdayAfter(10), '14:00', 'Mechanic called in sick');

    expect(rsMail())->toHaveCount(1);

    $body = quoted_printable_decode(rsMail()->first()->getOriginalMessage()->toString());

    expect($body)->toContain('Mechanic called in sick')
        // The customer must not think their slot has already gone.
        ->and($body)->toContain('Nothing has changed yet');
});

it('refuses to propose a move without a reason', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    rsProposeMove($admin, $booking, nextWeekdayAfter(10), '14:00', reason: null)
        ->assertSessionHasErrors('reschedule_reason');

    expect($booking->fresh()->hasPendingReschedule())->toBeFalse();
});

// ── The admin cannot keep changing it ─────────────────────────────────────

it('refuses a second proposal while one is still waiting', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    rsProposeMove($admin, $booking, nextWeekdayAfter(10));
    expect($booking->fresh()->hasPendingReschedule())->toBeTrue();

    rsProposeMove($admin, $booking, nextWeekdayAfter(12))
        ->assertSessionHasErrors('booking_date');

    // Still one, and still the first one: the customer is not holding two
    // emails proposing different days.
    expect(RescheduleRequest::count())->toBe(1)
        ->and(RescheduleRequest::sole()->proposed_date->toDateString())
        ->toBe(nextWeekdayAfter(10));
});

it('stops the booking being walked across the calendar', function () {
    // Each move looks reasonable on its own; the sequence is the problem.
    $admin   = rsAdmin();
    $booking = rsBooking();

    foreach ([10, 12] as $offset) {
        rsProposeMove($admin, $booking, nextWeekdayAfter($offset));
        test()->actingAs($admin)->patch('/admin/bookings/'.$booking->id.'/reschedule/apply');
    }

    expect($booking->fresh()->timesRescheduled())->toBe(RescheduleRequest::MAX_APPLIED_PER_BOOKING);

    // The third is refused outright.
    rsProposeMove($admin, $booking, nextWeekdayAfter(14))
        ->assertSessionHasErrors('booking_date');

    expect(RescheduleRequest::where('status', RescheduleRequest::PENDING)->count())->toBe(0);
});

it('does not count a withdrawn proposal against the limit', function () {
    // Raising one and taking it back costs the customer nothing, because the
    // booking never moved.
    $admin   = rsAdmin();
    $booking = rsBooking();

    rsProposeMove($admin, $booking, nextWeekdayAfter(10));
    test()->actingAs($admin)->patch('/admin/bookings/'.$booking->id.'/reschedule/withdraw');

    expect($booking->fresh()->timesRescheduled())->toBe(0)
        ->and($booking->fresh()->rescheduleBlockedReason())->toBeNull();

    // So another may be proposed.
    rsProposeMove($admin, $booking, nextWeekdayAfter(12));

    expect($booking->fresh()->hasPendingReschedule())->toBeTrue();
});

// ── Applying, and withdrawing ─────────────────────────────────────────────

it('moves the booking only when the change is applied', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();
    $target  = nextWeekdayAfter(10);

    rsProposeMove($admin, $booking, $target, '14:00');
    expect($booking->fresh()->booking_date)->not->toBe($target);

    test()->actingAs($admin)->patch('/admin/bookings/'.$booking->id.'/reschedule/apply');

    $fresh = $booking->fresh();

    expect($fresh->booking_date)->toBe($target)
        ->and(substr((string) $fresh->booking_time, 0, 5))->toBe('14:00')
        ->and($fresh->hasPendingReschedule())->toBeFalse();
});

it('changes nothing when the proposal is withdrawn', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    $date = $booking->booking_date;
    $time = $booking->booking_time;

    rsProposeMove($admin, $booking, nextWeekdayAfter(10));
    test()->actingAs($admin)->patch('/admin/bookings/'.$booking->id.'/reschedule/withdraw');

    $fresh = $booking->fresh();

    expect($fresh->booking_date)->toBe($date)
        ->and($fresh->booking_time)->toBe($time)
        ->and($fresh->hasPendingReschedule())->toBeFalse()
        ->and(RescheduleRequest::sole()->status)->toBe(RescheduleRequest::WITHDRAWN);
});

it('fails cleanly, keeping the original booking, if the new slot has gone', function () {
    // The proposed slot is deliberately not held — holding it would mean one
    // booking occupying two at once. So it can be taken, and applying has to
    // cope rather than losing the booking.
    $admin   = rsAdmin();
    $booking = rsBooking();
    $target  = nextWeekdayAfter(10);

    rsProposeMove($admin, $booking, $target, '14:00');

    // Three other cars fill that window while the customer is deciding.
    foreach (range(1, 3) as $i) {
        rsBooking(['booking_date' => $target, 'booking_time' => '14:00']);
    }

    $originalDate = $booking->fresh()->booking_date;

    test()->actingAs($admin)->patch('/admin/bookings/'.$booking->id.'/reschedule/apply')
        ->assertSessionHasErrors('reschedule');

    $fresh = $booking->fresh();

    expect($fresh->booking_date)->toBe($originalDate)
        // Still open, so the shop can withdraw it and offer another time.
        ->and($fresh->hasPendingReschedule())->toBeTrue();
});

// ── The record of it ──────────────────────────────────────────────────────

it('audits the proposal and the outcome', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    rsProposeMove($admin, $booking, nextWeekdayAfter(10));
    test()->actingAs($admin)->patch('/admin/bookings/'.$booking->id.'/reschedule/apply');

    $actions = AuditLog::pluck('action')->all();

    expect($actions)->toContain('booking.reschedule_proposed')
        ->and($actions)->toContain('booking.reschedule_applied');
});

it('records the in-app notice as well as the email', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    rsProposeMove($admin, $booking, nextWeekdayAfter(10));

    expect($booking->user->notifications()->count())->toBe(1)
        ->and($booking->user->notifications()->first()->data['event'])
        ->toBe(BookingNotification::RESCHEDULE_PROPOSED);
});

// ── Everything else on the form still saves ───────────────────────────────

it('still saves notes and status while a slot change is only proposed', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();

    test()->actingAs($admin)->put('/admin/bookings/'.$booking->id, [
        'service_ids'       => $booking->services->pluck('id')->all(),
        'booking_date'      => nextWeekdayAfter(10),
        'booking_time'      => '14:00',
        'status'            => 'in_progress',
        'notes'             => 'Customer will drop keys early',
        'reschedule_reason' => 'Mechanic called in sick',
    ]);

    $fresh = $booking->fresh();

    expect($fresh->notes)->toBe('Customer will drop keys early')
        ->and($fresh->status)->toBe('in_progress')
        // but the slot has not moved
        ->and($fresh->hasPendingReschedule())->toBeTrue();
});

it('refuses to propose a change to a cancelled booking', function () {
    $admin   = rsAdmin();
    $booking = rsBooking();
    $booking->update(['status' => 'cancelled']);

    expect($booking->fresh()->rescheduleBlockedReason())
        ->toContain('cancelled booking cannot be rescheduled');
});
