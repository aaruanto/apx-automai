<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingCancellation;
use App\Support\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * A cancelled booking texts the customer, by every route to a cancellation.
 *
 * The consequences of a cancellation already lived in one place —
 * BookingCancellation — precisely because three of the four ways of
 * cancelling had drifted apart before. The text goes in there for the same
 * reason: the shop cancelling, the customer cancelling and the no-show sweep
 * must not be able to disagree about whether the customer was told.
 *
 * Driver stays on `log`: every rule runs, an sms_logs row is written, nothing
 * is sent and no credit is spent.
 */
beforeEach(function () {
    config()->set('services.sms.driver', 'log');
    config()->set('mail.default', 'array');
    Mail::purge('array');
    MessageTemplate::save(MessageTemplate::defaults());
});

afterEach(function () {
    MessageTemplate::save(MessageTemplate::defaults());
});

function cxAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function cxBooking(?string $phone = '09171234567', string $status = 'confirmed'): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create([
        'role' => 'customer', 'phone' => $phone,
        'email' => 'cx'.$unique.'@gmail.com', 'name' => 'Juan dela Cruz',
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'CXL '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Cancel Service', 'price' => 1500, 'duration' => 45]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        'status' => $status, 'duration' => 45, 'reference_number' => 'APX-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => $service->price]);

    return $booking;
}

function cxTexts(): int
{
    return SmsLog::where('trigger', 'cancel_booking')->count();
}

it('texts the customer when the shop cancels', function () {
    $booking = cxBooking();

    test()->actingAs(cxAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Mechanic is unavailable that day.'])
        ->assertOk();

    $log = SmsLog::where('trigger', 'cancel_booking')->first();

    expect($log)->not->toBeNull()
        ->and($log->recipient)->toBe('+639171234567')
        ->and($log->booking_id)->toBe($booking->id)
        ->and($log->content)->toContain($booking->reference_number)
        ->and($log->content)->toContain('CANCELLED');
});

it('texts the customer when they cancel it themselves', function () {
    $booking = cxBooking();

    test()->actingAs($booking->user)
        ->patchJson("/customer/bookings/{$booking->id}/cancel", ['reason' => 'Something came up.'])
        ->assertOk();

    expect(cxTexts())->toBe(1);
});

/**
 * The sweep cancels without a human present, which makes the text the only
 * way the customer finds out before turning up.
 */
it('texts the customer when the no-show sweep cancels', function () {
    $booking = cxBooking();
    $booking->update(['booking_date' => today()->subDay(), 'booking_time' => '09:00']);

    test()->artisan('bookings:cancel-no-shows')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and(cxTexts())->toBe(1);
});

/**
 * notify: false already means "this customer should not be told" — it is how
 * the account-deletion sweep stays quiet. SMS must honour the same flag
 * without the caller having to know SMS exists.
 */
it('stays silent when the caller passed notify false', function () {
    $booking = cxBooking();

    app(BookingCancellation::class)->cancel($booking, 'Account deleted', null, false);

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and(cxTexts())->toBe(0);
});

it('sends nothing when the cancel_booking trigger is switched off', function () {
    $t = MessageTemplate::defaults();
    $t['triggers']['cancel_booking'] = false;
    MessageTemplate::save($t);

    app(BookingCancellation::class)->cancel(cxBooking(), 'Shop closed');

    expect(cxTexts())->toBe(0);
});

it('sends nothing when the SMS channel is switched off', function () {
    $t = MessageTemplate::defaults();
    $t['channels']['send_sms'] = false;
    MessageTemplate::save($t);

    app(BookingCancellation::class)->cancel(cxBooking(), 'Shop closed');

    expect(cxTexts())->toBe(0);
});

it('cancels normally for a customer with no number on file', function () {
    $booking = cxBooking(null);

    app(BookingCancellation::class)->cancel($booking, 'Shop closed');

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and(cxTexts())->toBe(0);
});

/**
 * The first draft of this body ran to 163 characters against the longest
 * service name on file — two segments, double cost, on every cancellation.
 */
it('fits the cancellation into a single GSM-7 segment', function () {
    app(BookingCancellation::class)->cancel(cxBooking(), 'Shop closed');

    $log   = SmsLog::where('trigger', 'cancel_booking')->first();
    $parts = app(App\Services\SmsService::class)->segments($log->content);

    expect($parts['encoding'])->toBe('GSM-7')
        ->and($parts['segments'])->toBe(1)
        ->and($log->segments)->toBe(1);
});

it('gives the admin a field for the cancellation text and a trigger for it', function () {
    $html = test()->actingAs(cxAdmin())->get('/admin/templates')->assertOk()->getContent();

    expect($html)->toContain('id="cancellationSms"')
        ->and($html)->toContain('Booking Cancellation SMS')
        ->and($html)->toContain('name="cancel_booking"');
});
