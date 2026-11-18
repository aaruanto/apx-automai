<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * A guest booking needs a phone number the shop can actually ring.
 *
 * The field was on the form, marked optional, validated in the browser by
 * FormValidate.rules.phonePH — and read on the server as
 * `$request->guest_phone ?? null`, with no rule at all. So the one guard was
 * the one a browser can skip, and a guest booking could be saved with no
 * number or with "dunno" in the field.
 *
 * That matters more here than anywhere else in the project. A guest booking
 * has no account behind it: no password, an email address nobody has checked,
 * and a plate the shop has not seen. The phone number is the only way to
 * confirm the booking is real, which is exactly what the success screen now
 * promises to do with it — see GuestVerificationCallTest.
 */
beforeEach(function () {
    Mail::fake();
});

function phoneService(): Service
{
    static $n = 1;

    return Service::create([
        'name' => 'Phone Svc '.$n++, 'price' => 1200, 'duration' => 30, 'is_active' => true,
    ]);
}

function phonePayload(array $overrides = []): array
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -4);

    return array_merge([
        'guest_name'    => 'Juan dela Cruz',
        'guest_email'   => 'phone'.$unique.'@gmail.com',
        'guest_phone'   => '09171234567',
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
        'service_ids'   => [phoneService()->id],
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'PHN '.$unique,
        'vehicle_type'  => 'car',
    ], $overrides);
}

// ── the server is the one that decides ───────────────────────────────────

it('refuses a guest booking with no phone number', function () {
    $this->postJson('/booking/guest', phonePayload(['guest_phone' => null]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('guest_phone');

    // Refused means nothing written — not the account, not the vehicle, not
    // the booking. The ordering in GuestBookingController is what makes this
    // true, and validation runs before any of it.
    expect(Booking::count())->toBe(0);
    expect(User::where('is_guest', true)->count())->toBe(0);
});

it('refuses a guest booking whose phone field was left blank', function () {
    $this->postJson('/booking/guest', phonePayload(['guest_phone' => '   ']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('guest_phone');

    expect(Booking::count())->toBe(0);
});

it('says what it wants when the number is missing', function () {
    $response = $this->postJson('/booking/guest', phonePayload(['guest_phone' => '']))
        ->assertStatus(422);

    // A message about the booking, not about a field name: this is read by
    // someone who has just pressed Confirm.
    expect($response->json('errors.guest_phone.0'))
        ->toContain('phone number')
        ->toContain('confirm your booking');
});

it('puts the reason where the modal will actually read it', function () {
    // The booking modal's fetch handler shows `e.message` from the response
    // body — not response.errors, which it never looks at. So a rule whose
    // message only reaches `errors` is a rule the customer is told nothing
    // about: they get "Server error (422)" and no idea which field to fix.
    $response = $this->postJson('/booking/guest', phonePayload(['guest_phone' => '']))
        ->assertStatus(422);

    expect($response->json('message'))->toContain('phone number');
});

it('keeps the rest of the booking intact when the number is refused', function () {
    // Nothing is re-rendered on this path: the modal is still open with every
    // field as the customer left it, because the booking is posted as JSON and
    // a refusal is answered with JSON. What has to be true on the server side
    // is that a refusal wrote nothing at all — no half-made account to come
    // back to, which is the fault GuestBookingNoOrphanTest exists for.
    $payload = phonePayload(['guest_phone' => 'nope']);

    $this->postJson('/booking/guest', $payload)->assertStatus(422);

    expect(Booking::count())->toBe(0);
    expect(User::where('email', $payload['guest_email'])->exists())->toBeFalse();
    expect(\App\Models\Vehicle::where('plate_number', $payload['vehicle_plate'])->exists())->toBeFalse();

    // And the same booking goes through once the number is corrected, with
    // everything else exactly as it was first submitted.
    $this->postJson('/booking/guest', [...$payload, 'guest_phone' => '09171234567'])->assertOk();

    expect(Booking::sole()->user->email)->toBe($payload['guest_email']);
    expect(Booking::sole()->vehicle->plate_number)->toBe($payload['vehicle_plate']);
});

it('refuses something that is not a mobile number', function (string $bad) {
    $this->postJson('/booking/guest', phonePayload(['guest_phone' => $bad]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('guest_phone');

    expect(Booking::count())->toBe(0);
})->with([
    'words'              => ['dunno'],
    'too short'          => ['0917123'],
    'too long'           => ['091712345678'],
    'a landline'         => ['0288881234'],
    'no leading zero'    => ['9171234567'],
    'the wrong country'  => ['+447911123456'],
    'digits and letters' => ['0917ABC4567'],
    'an email address'   => ['juan@gmail.com'],
]);

it('says what shape it wants when the number is wrong', function () {
    $response = $this->postJson('/booking/guest', phonePayload(['guest_phone' => 'dunno']))
        ->assertStatus(422);

    // The shape, by example, rather than the name of the rule.
    expect($response->json('errors.guest_phone.0'))->toContain('09171234567');
});

// ── what it accepts ──────────────────────────────────────────────────────

it('accepts both ways people write a Philippine mobile number', function (string $typed) {
    $this->postJson('/booking/guest', phonePayload(['guest_phone' => $typed]))->assertOk();

    expect(Booking::sole()->user->phone)->toBe($typed);
})->with([
    'local'         => ['09171234567'],
    'international' => ['+639171234567'],
]);

it('tidies the separators people type rather than refusing them', function (string $typed, string $stored) {
    // "0917 123 4567" is one number written the way people write it. The
    // pattern accepts no spaces, so cleaning first is the difference between
    // tidying it and turning a real customer away at the last step.
    $this->postJson('/booking/guest', phonePayload(['guest_phone' => $typed]))->assertOk();

    expect(Booking::sole()->user->phone)->toBe($stored);
})->with([
    'spaces'      => ['0917 123 4567', '09171234567'],
    'hyphens'     => ['0917-123-4567', '09171234567'],
    'mixed'       => [' +63 917 123 4567 ', '+639171234567'],
    'parentheses' => ['(0917) 123 4567', '09171234567'],
]);

it('keeps the number in the form its owner typed it', function () {
    // Deliberately NOT rewritten to +63 on the way in, which is the same rule
    // Mechanic::normalisedContact() follows: the shop reads back what the
    // customer wrote, and the verification notice shows them their own number
    // to check. SmsService::normalise() does the +63 conversion on the way out
    // to a gateway, which is the only place it is needed.
    $this->postJson('/booking/guest', phonePayload(['guest_phone' => '0917 123 4567']))->assertOk();

    expect(Booking::sole()->user->phone)->toBe('09171234567');
    expect(app(\App\Services\SmsService::class)->normalise('09171234567'))->toBe('+639171234567');
});

// ── the rule itself ──────────────────────────────────────────────────────

it('agrees with the pattern the browser enforces', function () {
    // The browser must never approve what the server will refuse. It cannot
    // here, because the server cleans separators first and so accepts
    // strictly more — which is the harmless direction.
    $client = file_get_contents(public_path('assets/js/form-validate.js'));

    expect($client)->toContain('^09\\d{9}$|^\\+639\\d{9}$');

    foreach (['09171234567', '+639171234567'] as $good) {
        expect(preg_match(PhoneNumber::PATTERN, $good))->toBe(1);
    }

    foreach (['0917123456', '639171234567', '09171234567 '] as $bad) {
        expect(preg_match(PhoneNumber::PATTERN, $bad))->toBe(0);
    }
});

// ── and the form says so ─────────────────────────────────────────────────

it('marks the phone label required on the public booking modal', function () {
    phoneService();

    $html = $this->get('/')->assertOk()->getContent();

    // The shared mechanism, not a hand-rolled asterisk — see
    // RequiredFieldMarkerTest, which pins the same thing from the other side.
    expect($html)->toMatch('~<label class="form-label required" for="apx-f-phone">~');
});

it('keeps the required attribute and the phone keypad on the input', function () {
    phoneService();

    $html = $this->get('/')->assertOk()->getContent();

    // required is what assistive technology announces and what stops the
    // browser submitting an empty field; a generated asterisk cannot carry
    // either. type and inputmode are what bring up a keypad instead of a
    // keyboard on a phone, which is what most of these bookings are made on.
    // One <input ...> tag, matched whole, so the attributes are proven to be
    // on the phone field rather than somewhere else on a 2,600-line page.
    expect($html)->toMatch('~<input type="tel"[^>]*id="apx-f-phone"[^>]*>~');

    expect($html)->toMatch('~id="apx-f-phone"[^>]*inputmode="tel"~');
    expect($html)->toMatch('~id="apx-f-phone"[^>]*required~');
    expect($html)->toMatch('~id="apx-f-phone"[^>]*pattern="\(09\[0-9\]\{9\}\|\\\\\+639\[0-9\]\{9\}\)"~');
});
