<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;

uses(RefreshDatabase::class);

/**
 * Telling the customer their booking is off.
 *
 * BookingNotification::via() returned ['database'] for every event, so
 * cancelling wrote a bell entry and sent nothing. A customer who does not
 * open the app found out by arriving at a shop that was not expecting them.
 *
 * Mail::fake() is no use for asserting this: MailFake::sendMail returns early
 * for anything that is not a Mailable, so assertSentCount reads zero however
 * much mail went out. The array transport captures the composed message.
 */
beforeEach(function () {
    config(['mail.default' => 'array']);
});

/**
 * The readable text of a sent message.
 *
 * Decoded, because quoted-printable soft-wraps long lines with =
 and
 * where that break lands depends on the length of everything before it —
 * so asserting against the raw MIME tests the encoder, not the wording, and
 * breaks when a reference number changes length.
 */
function noticeBody(\Symfony\Component\Mailer\SentMessage $message): string
{
    return quoted_printable_decode($message->getOriginalMessage()->toString());
}

/** @return \Illuminate\Support\Collection<int, \Symfony\Component\Mailer\SentMessage> */
function noticeMail(): \Illuminate\Support\Collection
{
    return collect(Mail::getSymfonyTransport()->messages());
}

function noticeAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function noticeBooking(array $userAttributes = []): Booking
{
    static $n = 1;

    $user = User::factory()->create(array_merge([
        'role' => 'customer', 'is_active' => true, 'is_guest' => false,
        'email' => 'cancel.customer'.$n.'@gmail.com',
    ], $userAttributes));

    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'CNL 000'.$n, 'color' => 'Blue', 'vehicle_type' => 'car',
    ]);

    $service = Service::create(['name' => 'Oil Change '.$n, 'price' => 1500, 'duration' => 60]);

    return Booking::create([
        'user_id'          => $user->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => nextWeekday(),
        'booking_time'     => '10:00:00',
        'duration'         => 60,
        'status'           => 'confirmed',
        'reference_number' => 'APX-CNL-'.$n++,
    ]);
}

function noticeCancel(User $admin, Booking $booking, string $reason = 'Mechanic unavailable')
{
    return test()->actingAs($admin)
        ->patchJson('/admin/bookings/'.$booking->id.'/cancel', ['reason' => $reason]);
}

// ── The hole that was reported ────────────────────────────────────────────

it('emails the customer exactly once when an admin cancels', function () {
    $booking = noticeBooking();

    noticeCancel(noticeAdmin(), $booking)->assertOk();

    expect(noticeMail())->toHaveCount(1);

    $sent = noticeMail()->first();

    expect($sent->getOriginalMessage()->getTo()[0]->getAddress())
        ->toBe($booking->user->email);
});

it('says what happened, and why, in the email itself', function () {
    $booking = noticeBooking();

    noticeCancel(noticeAdmin(), $booking, 'Mechanic called in sick')->assertOk();

    $body = noticeBody(noticeMail()->first());

    expect($body)->toContain('Booking Cancelled')
        ->and($body)->toContain($booking->reference_number)
        // The reason the admin typed travels with it — the customer should not
        // have to sign in to learn why.
        ->and($body)->toContain('Mechanic called in sick');
});

it('still writes the in-app notification', function () {
    // Email is additional, not instead of. The bell entry is what the
    // dashboard reads.
    $booking = noticeBooking();

    noticeCancel(noticeAdmin(), $booking)->assertOk();

    expect($booking->user->notifications()->count())->toBe(1)
        ->and($booking->user->notifications()->first()->data['event'])
        ->toBe(BookingNotification::BOOKING_CANCELLED);
});

it('reaches the address a guest gave at booking', function () {
    // A guest booking creates a real account with the address they typed, so
    // the cancellation has somewhere to go without them ever signing in.
    $booking = noticeBooking(['email' => 'guest.booker@gmail.com', 'is_guest' => true]);

    noticeCancel(noticeAdmin(), $booking)->assertOk();

    expect(noticeMail()->first()->getOriginalMessage()->getTo()[0]->getAddress())
        ->toBe('guest.booker@gmail.com');
});

// ── Failure must not undo the cancellation ────────────────────────────────

it('leaves the booking cancelled when the mailer throws', function () {
    // The booking is cancelled and the points reversed before the notice goes
    // out. A dead SMTP connection must not turn that into a 500, or the admin
    // retries against a booking that is already cancelled.
    $booking = noticeBooking();

    Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP connection timed out'));
    Mail::shouldReceive('mailer')->andThrow(new RuntimeException('SMTP connection timed out'));

    noticeCancel(noticeAdmin(), $booking)->assertOk();

    expect($booking->fresh()->status)->toBe('cancelled');
});

// ── Which events mail, and which deliberately do not ──────────────────────

it('does not email the events that are mailed elsewhere or not worth mailing', function () {
    // confirmed already has its own BookingConfirmed mailable sent from the
    // admin controller, so mailing it here would send two. requested goes to
    // admins who watch the dashboard. completed tells someone what they
    // learned at the counter.
    $booking = noticeBooking();
    $user    = $booking->user;

    $user->notify(BookingNotification::confirmed($booking));
    $user->notify(BookingNotification::completed($booking));
    $user->notify(BookingNotification::requested($booking));

    expect(noticeMail())->toHaveCount(0)
        // All three still land in the bell.
        ->and($user->notifications()->count())->toBe(3);
});

it('emails a reschedule, which the approval flow will depend on', function () {
    $booking = noticeBooking();

    $booking->user->notify(BookingNotification::rescheduled($booking, '2026-11-01', '09:00'));

    expect(noticeMail())->toHaveCount(1)
        ->and(noticeBody(noticeMail()->first()))
        ->toContain('Booking Rescheduled');
});

it('does not try to email an admin-created walk-in placeholder address', function () {
    // Admin\BookingController invents name@apxautomai.local so the account can
    // hold bookings. Nobody reads it, and a real SMTP server fails slowly on
    // it — the same suffix BookingConfirmed already skips.
    $booking = noticeBooking(['email' => 'walkin@apxautomai.local']);

    noticeCancel(noticeAdmin(), $booking)->assertOk();

    expect(noticeMail())->toHaveCount(0)
        // The bell entry is still written, so the record exists.
        ->and($booking->user->notifications()->count())->toBe(1);
});

// ── The edit screen's own cancel control ──────────────────────────────────

it('cancels from the edit screen through the same path as the list', function () {
    // That screen used to carry its own dialog which posted nothing but a CSRF
    // token. The server requires a reason, so pressing "Yes, Cancel"
    // redirected away having cancelled nothing, with no sign anything had
    // failed. It now calls ApxBookingActions like the list does.
    $booking = noticeBooking();

    noticeCancel(noticeAdmin(), $booking, 'Customer rang to cancel')->assertOk();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($booking->fresh()->cancel_reason)->toBe('Customer rang to cancel')
        ->and(noticeMail())->toHaveCount(1);
});

it('refuses a cancellation with no reason, rather than quietly doing nothing', function () {
    $booking = noticeBooking();

    test()->actingAs(noticeAdmin())
        ->patchJson('/admin/bookings/'.$booking->id.'/cancel', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.reason.0', 'Please give a reason for cancelling this booking.');

    expect($booking->fresh()->status)->not->toBe('cancelled');
});

it('leaves no cancel dialog on the edit screen that posts without a reason', function () {
    // The markup guard. A form posting to the cancel route with no reason
    // field is the shape of the bug, and it looked perfectly reasonable.
    $markup = file_get_contents(resource_path('views/admin/bookings/create.blade.php'));

    expect($markup)->not->toContain("route('admin.bookings.cancel'");
});
