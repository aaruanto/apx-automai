<?php

use App\Models\Booking;
use App\Models\RescheduleRequest;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * "Has the slot moved?" asked of a value the database spells differently.
 *
 * booking_date and booking_time are not cast, so they come back exactly as
 * the driver returns them. PostgreSQL's `time` type gives 09:00:00; the form
 * posts 09:00. Every comparison of the two was a string comparison, so on the
 * live site pressing Confirm reported the booking had "moved from 09:00:00 to
 * 09:00:00" and emailed the customer about it.
 *
 * None of this reproduces on SQLite, which stores whatever string it was
 * given — ten rows locally all held five characters, so the comparison
 * matched and the suite stayed green while production kept sending the email.
 *
 * So these tests write the stored value as PostgreSQL hands it back before
 * acting. That is the only way to exercise the production shape without a
 * PostgreSQL server; see the report for what it therefore does not prove.
 */
beforeEach(function () {
    config(['mail.default' => 'array']);
});

function slotAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

/**
 * A booking whose stored time has seconds, the way PostgreSQL returns it.
 */
function slotBooking(string $storedTime = '09:00:00', string $status = 'pending'): Booking
{
    static $n = 1;

    $user = User::factory()->create([
        'role' => 'customer', 'is_guest' => false, 'is_active' => true,
        'email' => 'slot.customer'.$n.'@gmail.com',
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'SLT 000'.$n, 'vehicle_type' => 'car',
    ]);

    $service = Service::create(['name' => 'Oil Change '.$n, 'price' => 1500, 'duration' => 60]);

    $booking = Booking::create([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekdayAfter(4), 'booking_time' => '09:00', 'duration' => 60,
        'status' => $status, 'reference_number' => 'APX-SLT-'.$n++,
    ]);

    $booking->services()->sync([$service->id => ['price' => 1500, 'duration' => 60]]);

    // The whole point: store it the way PostgreSQL would.
    DB::table('bookings')->where('id', $booking->id)->update(['booking_time' => $storedTime]);

    return $booking->fresh();
}

function slotSave(User $admin, Booking $booking, array $overrides = [])
{
    return test()->actingAs($admin)->put('/admin/bookings/'.$booking->id, array_merge([
        'service_ids'  => $booking->services->pluck('id')->all(),
        'booking_date' => $booking->booking_date,
        'booking_time' => '09:00',            // what the form posts
        'status'       => $booking->status,
        'notes'        => $booking->notes,
    ], $overrides));
}

function slotMail(): \Illuminate\Support\Collection
{
    return collect(Mail::getSymfonyTransport()->messages());
}

// ── The model reads one shape whatever is stored ──────────────────────────

it('reads a stored time the same whether it carries seconds or not', function () {
    $withSeconds = slotBooking('09:00:00');
    $without     = slotBooking('09:00');

    expect($withSeconds->booking_time)->toBe('09:00')
        ->and($without->booking_time)->toBe('09:00')
        // And the raw column is untouched — nothing was migrated.
        ->and(DB::table('bookings')->where('id', $withSeconds->id)->value('booking_time'))
        ->toBe('09:00:00');
});

it('does not call two spellings of the same slot a move', function () {
    $booking = slotBooking('09:00:00');

    expect($booking->slotMatches($booking->booking_date, '09:00'))->toBeTrue()
        ->and($booking->slotMatches($booking->booking_date, '09:00:00'))->toBeTrue()
        // A genuine change is still a change.
        ->and($booking->slotMatches($booking->booking_date, '14:00'))->toBeFalse()
        ->and($booking->slotMatches(nextWeekdayAfter(9), '09:00'))->toBeFalse();
});

// ── Confirming must not announce a move ───────────────────────────────────

it('sends exactly one email when a booking is confirmed', function () {
    // The reported bug: two emails and two bell entries from pressing
    // Confirm, the second saying the booking moved from 09:00:00 to 09:00:00.
    $admin   = slotAdmin();
    $booking = slotBooking('09:00:00');

    slotSave($admin, $booking, ['status' => 'confirmed'])->assertSessionHasNoErrors();

    $events = $booking->user->fresh()->notifications->pluck('data.event')->all();

    expect($events)->toBe([BookingNotification::BOOKING_CONFIRMED])
        ->and(slotMail())->toHaveCount(1)
        ->and($booking->fresh()->status)->toBe('confirmed');
});

it('writes no reschedule audit row when nothing moved', function () {
    $admin   = slotAdmin();
    $booking = slotBooking('09:00:00');

    slotSave($admin, $booking, ['status' => 'confirmed']);

    expect(App\Models\AuditLog::where('action', 'booking.rescheduled')->count())->toBe(0)
        ->and(App\Models\AuditLog::where('action', 'booking.reschedule_proposed')->count())->toBe(0);
});

it('does not raise a reschedule proposal when the admin only changed the status', function () {
    // With the proposal flow in place the same comparison had a worse effect:
    // the save was refused outright, telling the admin to give a reason for
    // moving a booking they had not moved.
    $admin   = slotAdmin();
    $booking = slotBooking('09:00:00');

    slotSave($admin, $booking, ['status' => 'confirmed'])->assertSessionHasNoErrors();

    expect(RescheduleRequest::count())->toBe(0);
});

it('saves notes without claiming the slot changed', function () {
    $admin   = slotAdmin();
    $booking = slotBooking('09:00:00');

    slotSave($admin, $booking, ['notes' => 'Customer will drop keys early'])
        ->assertSessionHasNoErrors();

    expect($booking->fresh()->notes)->toBe('Customer will drop keys early')
        ->and(RescheduleRequest::count())->toBe(0)
        ->and(slotMail())->toHaveCount(0);
});

// ── A real move still behaves ─────────────────────────────────────────────

it('still treats a genuine change of time as a move', function () {
    $admin   = slotAdmin();
    $booking = slotBooking('09:00:00');

    slotSave($admin, $booking, [
        'booking_time'      => '14:00',
        'reschedule_reason' => 'Mechanic unavailable that morning',
    ])->assertSessionHasNoErrors();

    expect(RescheduleRequest::count())->toBe(1)
        ->and(RescheduleRequest::sole()->proposed_time)->toContain('14:00')
        // Proposed, so the booking itself has not moved.
        ->and($booking->fresh()->booking_time)->toBe('09:00');
});

it('still treats a genuine change of date as a move', function () {
    $admin   = slotAdmin();
    $booking = slotBooking('09:00:00');

    slotSave($admin, $booking, [
        'booking_date'      => nextWeekdayAfter(11),
        'reschedule_reason' => 'Shop closed that day',
    ])->assertSessionHasNoErrors();

    expect(RescheduleRequest::count())->toBe(1);
});

it('sends exactly one reschedule email when a move is applied', function () {
    $admin   = slotAdmin();
    $booking = slotBooking('09:00:00');

    slotSave($admin, $booking, [
        'booking_time'      => '14:00',
        'reschedule_reason' => 'Mechanic unavailable that morning',
    ]);

    // One so far: the proposal.
    expect(slotMail())->toHaveCount(1);

    test()->actingAs($admin)->patch('/admin/bookings/'.$booking->id.'/reschedule/apply');

    $events = $booking->user->fresh()->notifications->pluck('data.event')->all();

    expect(slotMail())->toHaveCount(2)
        ->and($events)->toContain(BookingNotification::BOOKING_RESCHEDULED)
        // Named once, not once per save.
        ->and(collect($events)->filter(fn ($e) => $e === BookingNotification::BOOKING_RESCHEDULED))
        ->toHaveCount(1);
});
