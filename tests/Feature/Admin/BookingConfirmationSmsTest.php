<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Confirming a booking texts the customer, as well as emailing them.
 *
 * Until this existed the two channels were asymmetric for no stated reason:
 * email was wired into the booking lifecycle and SMS was confined to the two
 * scheduled reminder commands. So the one message a customer is actively
 * waiting for — "your slot is real" — arrived only by email, while three
 * reminders they had not asked for arrived by both.
 *
 * The driver stays on `log` throughout. Every rule still runs and an sms_logs
 * row is still written, which is what these assert against; nothing is sent
 * and no credit is spent. That is the whole point of the log driver.
 */
beforeEach(function () {
    config()->set('services.sms.driver', 'log');

    // The array transport, not Mail::fake(): MailFake::sendMail returns early
    // for anything that is not a Mailable, so a faked suite reads zero however
    // much mail was sent. See CLAUDE.md.
    config()->set('mail.default', 'array');
    Mail::purge('array');

    // Start every test from the shipped defaults rather than whatever a
    // previous run left in storage.
    MessageTemplate::save(MessageTemplate::defaults());
});

afterEach(function () {
    MessageTemplate::save(MessageTemplate::defaults());
});

function confAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function confBooking(string $status = 'pending', ?string $phone = '09171234567'): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create([
        'role'  => 'customer',
        'phone' => $phone,
        'email' => 'conf'.$unique.'@gmail.com',
        'name'  => 'Juan dela Cruz',
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'CNF '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Confirm Service', 'price' => 1500, 'duration' => 45]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        'status' => $status, 'notes' => 'Original note.', 'duration' => 45,
        'reference_number' => 'APX-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => $service->price]);

    return $booking;
}

/** PUT the edit form with the status changed. */
function confirmVia(Booking $booking, string $status = 'confirmed')
{
    return test()->actingAs(confAdmin())->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => [$booking->service_id],
        'booking_date' => $booking->booking_date instanceof \DateTimeInterface
            ? $booking->booking_date->format('Y-m-d')
            : $booking->booking_date,
        'booking_time' => '10:00',
        'status'       => $status,
        'notes'        => 'Original note.',
    ]);
}

it('texts the customer when a booking is confirmed', function () {
    $booking = confBooking('pending');

    confirmVia($booking)->assertRedirect();

    $log = SmsLog::where('trigger', 'confirm_booking')->first();

    expect($log)->not->toBeNull()
        ->and($log->recipient)->toBe('+639171234567')
        ->and($log->booking_id)->toBe($booking->id)
        ->and($log->content)->toContain($booking->reference_number)
        ->and($log->content)->toContain('Juan');
});

it('still emails as well as texting, so neither replaced the other', function () {
    $booking = confBooking('pending');

    confirmVia($booking)->assertRedirect();

    expect(SmsLog::where('trigger', 'confirm_booking')->count())->toBe(1)
        ->and(Mail::getSymfonyTransport()->messages()->count())->toBe(1);
});

it('sends nothing when the confirm_booking trigger is switched off', function () {
    $t = MessageTemplate::defaults();
    $t['triggers']['confirm_booking'] = false;
    MessageTemplate::save($t);

    confirmVia(confBooking('pending'))->assertRedirect();

    expect(SmsLog::where('trigger', 'confirm_booking')->count())->toBe(0);
});

it('sends nothing when the SMS channel is switched off', function () {
    $t = MessageTemplate::defaults();
    $t['channels']['send_sms'] = false;
    MessageTemplate::save($t);

    confirmVia(confBooking('pending'))->assertRedirect();

    expect(SmsLog::where('trigger', 'confirm_booking')->count())->toBe(0);
});

/**
 * The guard that makes this exactly-once. A reminder needs a ReminderLedger
 * claim because its date window stays true all day; a confirmation rides on a
 * status transition, so re-saving a booking that is already confirmed must not
 * text again.
 */
it('does not text again when the booking was already confirmed', function () {
    $booking = confBooking('confirmed');

    confirmVia($booking, 'confirmed')->assertRedirect();

    expect(SmsLog::where('trigger', 'confirm_booking')->count())->toBe(0);
});

it('skips a customer with no number on file rather than failing the save', function () {
    $booking = confBooking('pending', null);

    confirmVia($booking)->assertRedirect();

    expect(SmsLog::where('trigger', 'confirm_booking')->count())->toBe(0)
        ->and($booking->fresh()->status)->toBe('confirmed');
});

/**
 * One segment, measured on the text that is actually sent. A template that
 * quietly grows to two costs double on every confirmation, and nothing else in
 * the app would reveal it.
 */
it('fits the confirmation into a single GSM-7 segment', function () {
    confirmVia(confBooking('pending'))->assertRedirect();

    $log = SmsLog::where('trigger', 'confirm_booking')->first();
    $parts = app(App\Services\SmsService::class)->segments($log->content);

    expect($parts['encoding'])->toBe('GSM-7')
        ->and($parts['segments'])->toBe(1)
        ->and($log->segments)->toBe(1);
});

/**
 * The shop has to be able to edit the thing. A body with no field to change it
 * is a hardcoded message with extra steps.
 */
it('gives the admin a field for the confirmation text and a trigger for it', function () {
    $html = test()->actingAs(confAdmin())->get('/admin/templates')->assertOk()->getContent();

    expect($html)->toContain('id="confirmationSms"')
        ->and($html)->toContain('Booking Confirmation SMS')
        ->and($html)->toContain('name="confirm_booking"')
        ->and($html)->toContain('Booking confirmed');
});

/** A saved body must survive the round trip, or the field is decorative. */
it('saves an edited confirmation body', function () {
    test()->actingAs(confAdmin())->postJson('/admin/templates/save', array_merge(
        MessageTemplate::defaults(),
        ['confirmation_sms' => 'Hi {first_name}, confirmed. Ref {reference}.']
    ))->assertOk();

    expect(MessageTemplate::load()['confirmation_sms'])
        ->toBe('Hi {first_name}, confirmed. Ref {reference}.');
});