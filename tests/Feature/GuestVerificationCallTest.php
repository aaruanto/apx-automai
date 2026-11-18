<?php

use App\Enums\BookingStatus;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * A guest is told we will ring them to verify the booking.
 *
 * A guest booking is the only kind with nothing behind it: no account, no
 * password, an email address nobody has checked and a plate the shop has not
 * seen. The shop rings to confirm it is real — so the customer should be told
 * that is coming, and should be shown the number it is coming to, because the
 * moment to notice a wrong digit is while the confirmation is still on screen.
 *
 * Guests only. A signed-in customer has already proved they hold the address
 * they booked with, and an unexpected "we will call you" would read as though
 * something were wrong with their booking.
 *
 * The array transport, not Mail::fake(), for the reason CLAUDE.md gives.
 */
beforeEach(function () {
    config()->set('mail.default', 'array');
    Mail::purge('array');
    Setting::flushCache();
});

afterEach(function () {
    Carbon::setTestNow();
});

function verifyService(): Service
{
    static $n = 1;

    return Service::create([
        'name' => 'Verify Svc '.$n++, 'price' => 1300, 'duration' => 30, 'is_active' => true,
    ]);
}

function verifyPayload(array $overrides = []): array
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -4);

    return array_merge([
        'guest_name'    => 'Juan dela Cruz',
        'guest_email'   => 'verify'.$unique.'@gmail.com',
        'guest_phone'   => '09181234567',
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
        'service_ids'   => [verifyService()->id],
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'VFY '.$unique,
        'vehicle_type'  => 'car',
    ], $overrides);
}

function verifyMailbox(): string
{
    return collect(Mail::getSymfonyTransport()->messages()->all())
        ->map(function ($message) {
            $email = $message->getOriginalMessage();

            return ($email->getHtmlBody() ?? '')."\n".($email->getTextBody() ?? '');
        })
        ->implode("\n");
}

// ── on screen ────────────────────────────────────────────────────────────

it('tells a guest on screen that we will call the number they gave', function () {
    verifyService();

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('We will call you on')
        ->and($html)->toContain('id="apx-success-phone"')
        // Why we are calling, not just that we are: the booking details, and
        // that the number itself is reachable.
        ->and($html)->toContain('confirm your booking details')
        ->and($html)->toContain('check that we can reach you');
});

it('fills that notice with the number the booking was saved with', function () {
    verifyService();

    $html = $this->get('/')->assertOk()->getContent();

    // From the server's response rather than echoed from the input, so what
    // the customer is shown is what was actually stored against the booking.
    expect($html)->toContain("document.getElementById('apx-success-phone').textContent");
    expect($html)->toContain('data.phone');
});

it('returns the saved number to the success screen', function () {
    $this->postJson('/booking/guest', verifyPayload(['guest_phone' => '0918 765 4321']))
        ->assertOk()
        // Tidied, and the same value the booking holds — that is the point of
        // reading it back rather than repeating what was typed.
        ->assertJsonPath('phone', '09187654321');

    expect(Booking::sole()->user->phone)->toBe('09187654321');
});

it('reads well when the booking is also waiting for review', function () {
    verifyService();

    $html = $this->get('/')->assertOk()->getContent();

    // Both notices land on the same screen when today's limit holds a booking
    // back, so the pending wording leans on the call rather than talking past
    // it: the call is where the customer finds out whether they can still be
    // fitted in today.
    expect($html)->toContain('Today is fully booked, so <strong>this request is waiting for our review</strong>');
    expect($html)->toContain('We will tell you on the call above');
});

it('says the booking is confirmed when it was accepted on the spot', function () {
    verifyService();

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain("document.getElementById('apx-success-title').textContent = 'Booking confirmed!'");
    // And still no reference number on screen — that is the email's job.
    expect($html)->toContain('We have emailed you <strong>your reference number');
});

// ── in the email ─────────────────────────────────────────────────────────

it('repeats the promise in the guest confirmation email', function () {
    $this->postJson('/booking/guest', verifyPayload(['guest_phone' => '09181112222']))->assertOk();

    expect(Booking::sole()->status)->toBe(BookingStatus::BookingConfirmed);

    $whole = verifyMailbox();

    expect($whole)->toContain('We will call you on')
        ->and($whole)->toContain('09181112222');
});

it('repeats the promise in the acknowledgment when the booking is held for review', function () {
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 1);

    // The day filled up by a booking that already exists, created rather than
    // booked through a first request: afterResponse work re-runs on the next
    // request in the same test, which would double what is in the mailbox.
    $filler = User::factory()->create(['role' => 'customer']);

    Booking::create([
        'user_id'      => $filler->id,
        'vehicle_id'   => Vehicle::create([
            'user_id' => $filler->id, 'make' => 'Toyota', 'model' => 'Vios',
            'year' => 2020, 'plate_number' => 'VFL 1001', 'vehicle_type' => 'car',
        ])->id,
        'service_id'       => verifyService()->id,
        'booking_date'     => $today,
        'booking_time'     => '09:00',
        'duration'         => 30,
        'status'           => 'booking_confirmed',
        'reference_number' => 'VFY-0001',
    ]);

    $this->postJson('/booking/guest', verifyPayload([
        'booking_date' => $today,
        'booking_time' => '11:00',
        'guest_phone'  => '09183334444',
    ]))->assertOk();

    expect(Booking::whereDate('booking_date', $today)->latest('id')->first()->status)
        ->toBe(BookingStatus::PendingReview);

    $whole = verifyMailbox();

    // One partial, included by both emails, so the promise cannot be worded
    // differently depending on which one the guest happened to get.
    expect($whole)->toContain('We will call you on')
        ->and($whole)->toContain('09183334444');
});

// ── and not for a customer with an account ───────────────────────────────

it('says nothing of the kind to a signed-in customer', function () {
    $customer = User::factory()->create([
        'role' => 'customer', 'is_guest' => false, 'phone' => '09195556666',
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'VFC 2002', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [verifyService()->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertOk();

    expect(Booking::sole()->status)->toBe(BookingStatus::BookingConfirmed);

    $whole = verifyMailbox();

    // The confirmation reached them — this is not an empty mailbox passing by
    // accident — and it carries no verification call.
    expect($whole)->toContain(Booking::sole()->reference_number);
    expect($whole)->not->toContain('We will call you on');
});

it('leaves the line out of a mailable for a customer, and keeps it for a guest', function () {
    // The partial asked directly, so the rule holds for any future caller and
    // not only the paths that exist today.
    $guest = User::factory()->create(['role' => 'customer', 'is_guest' => true, 'phone' => '09171234567']);
    $known = User::factory()->create(['role' => 'customer', 'is_guest' => false, 'phone' => '09171234567']);

    foreach ([[$guest, true], [$known, false]] as [$user, $expected]) {
        $booking = Booking::create([
            'user_id'          => $user->id,
            'vehicle_id'       => Vehicle::create([
                'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
                'plate_number' => 'VFM '.$user->id, 'vehicle_type' => 'car',
            ])->id,
            'service_id'       => verifyService()->id,
            'booking_date'     => nextWeekday(),
            'booking_time'     => '10:00',
            'duration'         => 30,
            'status'           => 'booking_confirmed',
            'reference_number' => 'VFM-'.$user->id,
        ]);

        $rendered = (new BookingConfirmed($booking->fresh(['user', 'service', 'vehicle'])))->render();

        expect(str_contains($rendered, 'We will call you on'))->toBe($expected);
    }
});

it('promises no call when the booking has no number on record', function () {
    // Older guest bookings predate the required field. Promising a call to a
    // number we do not hold is worse than saying nothing.
    $guest = User::factory()->create(['role' => 'customer', 'is_guest' => true, 'phone' => null]);

    $booking = Booking::create([
        'user_id'          => $guest->id,
        'vehicle_id'       => Vehicle::create([
            'user_id' => $guest->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
            'plate_number' => 'VFN 3003', 'vehicle_type' => 'car',
        ])->id,
        'service_id'       => verifyService()->id,
        'booking_date'     => nextWeekday(),
        'booking_time'     => '10:00',
        'duration'         => 30,
        'status'           => 'booking_confirmed',
        'reference_number' => 'VFN-0001',
    ]);

    expect((new BookingConfirmed($booking->fresh(['user', 'service', 'vehicle'])))->render())
        ->not->toContain('We will call you on');
});
