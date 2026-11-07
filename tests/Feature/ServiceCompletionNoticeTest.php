<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SmsService;
use App\Support\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Telling the customer the work is done and the car can be collected.
 *
 * Completion used to write a bell entry and nothing else, deliberately: the
 * argument was that it "tells a customer something they learned at the
 * counter ten minutes earlier". That holds for a customer who waited. It is
 * exactly wrong for one who dropped the vehicle off and left, who has no way
 * to know it is ready and is the only person the message is for.
 *
 * Mail::fake() is no use here — MailFake::sendMail returns early for anything
 * that is not a Mailable, so a count reads zero however much mail went out.
 * The array transport captures the composed message instead.
 */
beforeEach(function () {
    config(['mail.default' => 'array']);
});

/** @return \Illuminate\Support\Collection<int, \Symfony\Component\Mailer\SentMessage> */
function completionMail(): \Illuminate\Support\Collection
{
    return collect(Mail::getSymfonyTransport()->messages());
}

function completionBody(\Symfony\Component\Mailer\SentMessage $message): string
{
    // Decoded: quoted-printable soft-wraps long lines, and where the break
    // lands depends on the length of everything before it. Asserting against
    // raw MIME tests the encoder, not the wording.
    return quoted_printable_decode($message->getOriginalMessage()->toString());
}

function completionBooking(array $userAttributes = [], string $status = 'confirmed'): Booking
{
    static $n = 1;

    $user = User::factory()->create(array_merge([
        'role'  => 'customer',
        'phone' => '0917000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'email' => 'done.customer'.$n.'@gmail.com',
    ], $userAttributes));

    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'CPL 000'.$n, 'color' => 'Blue', 'vehicle_type' => 'car',
    ]);

    $service = Service::create(['name' => 'Oil Change '.$n, 'price' => 1500, 'duration' => 60]);

    $booking = Booking::create([
        'user_id'          => $user->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => nextWeekday(),
        'booking_time'     => '09:00',
        'status'           => $status,
        'reference_number' => 'BK-90'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
    ]);

    $booking->services()->attach($service->id, ['price' => 1500]);

    $n++;

    return $booking;
}

function completeViaAdmin(Booking $booking, string $status = 'completed')
{
    $admin = User::factory()->create(['role' => 'admin']);

    return test()->actingAs($admin)->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => $booking->services()->pluck('services.id')->all(),
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => $status,
    ]);
}

// ── The transition tells the customer, both ways ─────────────────────────

it('emails the customer when the admin marks the work complete', function () {
    $booking = completionBooking();

    completeViaAdmin($booking);

    expect($booking->fresh()->status)->toBe('completed')
        ->and(completionMail())->toHaveCount(1);

    $body = completionBody(completionMail()->first());

    expect($body)->toContain('Your Vehicle Is Ready')
        ->and($body)->toContain('ready for pickup')
        ->and($body)->toContain($booking->reference_number);
});

it('texts the customer when the admin marks the work complete', function () {
    $booking = completionBooking();

    completeViaAdmin($booking);

    $log = SmsLog::where('trigger', 'complete_booking')->first();

    expect($log)->not->toBeNull()
        ->and($log->content)->toContain('COMPLETE')
        ->and($log->content)->toContain('ready for pickup')
        ->and($log->segments)->toBe(1)
        ->and($log->booking_id)->toBe($booking->id);
});

// ── And only on a real transition ────────────────────────────────────────

it('says nothing when a booking that is already complete is saved again', function () {
    // The prior state is created, not produced by a first request. Two
    // requests in one test re-run the first one's afterResponse callbacks,
    // which makes any mail count spanning them meaningless.
    $booking = completionBooking(status: 'completed');

    completeViaAdmin($booking);

    expect(completionMail())->toHaveCount(0)
        ->and(SmsLog::where('trigger', 'complete_booking')->count())->toBe(0);
});

it('says nothing when the status does not reach completed', function () {
    $booking = completionBooking();

    completeViaAdmin($booking, 'in_progress');

    expect(completionMail())->toHaveCount(0)
        ->and(SmsLog::where('trigger', 'complete_booking')->count())->toBe(0);
});

// ── Walk-ins have no real address ────────────────────────────────────────

it('does not email a walk-in placeholder address, but still texts it', function () {
    // Admin-created walk-ins get name@apxautomai.local so the account can
    // hold bookings. Nobody reads it and a real SMTP server fails slowly on
    // it. The phone number on those accounts is real, so the text still goes.
    $booking = completionBooking(['email' => 'walkin.9@apxautomai.local']);

    completeViaAdmin($booking);

    expect(completionMail())->toHaveCount(0)
        ->and(SmsLog::where('trigger', 'complete_booking')->count())->toBe(1);
});

// ── Cost ─────────────────────────────────────────────────────────────────

it('keeps the completion text to one GSM-7 segment', function () {
    // One non-GSM-7 character forces the whole message to UCS-2 and cuts a
    // segment from 160 characters to 70, so a stray em-dash in this template
    // triples the cost of every completed booking. Measured after gsmSafe(),
    // because that is the text that actually goes out.
    $booking = completionBooking();
    $sms     = app(SmsService::class);

    $filled = MessageTemplate::fill(
        MessageTemplate::defaults()['completion_sms'],
        $booking
    );

    $parts = $sms->segments($sms->gsmSafe($filled));

    expect($parts['encoding'])->toBe('GSM-7')
        ->and($parts['segments'])->toBe(1);
});

// ── The channel toggle still governs it ──────────────────────────────────

it('sends no text when the SMS channel is switched off', function () {
    $booking = completionBooking();

    MessageTemplate::save(['channels' => ['send_email' => true, 'send_sms' => false]]);

    completeViaAdmin($booking);

    expect(SmsLog::where('trigger', 'complete_booking')->count())->toBe(0)
        // The email is transactional and does not read the channel toggles,
        // the same as a cancellation or a reschedule. Someone who switched
        // the reminder stream off still needs to know their car is ready.
        ->and(completionMail())->toHaveCount(1);
});
