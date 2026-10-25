<?php

/**
 * A guest turning into a real customer.
 *
 * A guest booking creates a users row with is_guest = true and a random
 * password nobody has seen, and emails a "set up your account" link. Following
 * that link used to set the password and nothing else, so the customer could
 * sign in but stayed is_guest forever — refused the account discount by
 * BookingPricing, with nothing on screen explaining why. Registering with the
 * same address hit the unique rule and told them to use "Forgot password?",
 * which produced exactly that half-account.
 *
 * These pin the whole path: claim, keep the history, gain the discount, and
 * refuse to do any of it for an address the claimer cannot read mail for.
 */

use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

function claimService(float $price = 1500): Service
{
    return Service::create(['name' => 'Claim Svc '.uniqid(), 'price' => $price, 'duration' => 45]);
}

/** Book as a guest and hand back the row the flow created. */
function bookAsGuest(string $email, string $plate = 'CLM 1234'): User
{
    test()->postJson('/booking/guest', [
        'guest_name' => 'Juan Dela Cruz', 'guest_email' => $email,
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => $plate, 'vehicle_type' => 'car',
        'service_ids' => [claimService()->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    return User::where('email', $email)->sole();
}

// ── The claim itself ─────────────────────────────────────────────────────

it('turns a guest into a real customer when the emailed link is used', function () {
    $guest = bookAsGuest('claim-ok@example.com');
    expect($guest->is_guest)->toBeTrue();

    $token = Password::createToken($guest);

    $this->post('/reset-password', [
        'token' => $token, 'email' => $guest->email,
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ])->assertRedirect(route('login'));

    $guest->refresh();
    expect($guest->is_guest)->toBeFalse();
    expect(Hash::check('Str0ng-Passw0rd!', $guest->password))->toBeTrue();
});

it('keeps the bookings made as a guest', function () {
    // The whole point: the history is why claiming matters. Nothing is
    // migrated — it is the same row — so this is really asserting that the
    // claim does not create a second account alongside the first.
    $guest = bookAsGuest('claim-history@example.com');
    expect(Booking::where('user_id', $guest->id)->count())->toBe(1);

    $this->post('/reset-password', [
        'token' => Password::createToken($guest), 'email' => $guest->email,
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ])->assertRedirect(route('login'));

    expect(User::where('email', 'claim-history@example.com')->count())->toBe(1);
    expect(Booking::where('user_id', $guest->id)->count())->toBe(1);
});

it('lets the claimed customer sign in and see that booking on their dashboard', function () {
    $guest   = bookAsGuest('claim-dash@example.com', 'CLM 2222');
    $booking = Booking::where('user_id', $guest->id)->sole();

    $this->post('/reset-password', [
        'token' => Password::createToken($guest), 'email' => $guest->email,
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ]);

    $this->post('/login', ['email' => $guest->email, 'password' => 'Str0ng-Passw0rd!'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($guest->fresh());
    $this->get('/customer/dashboard')->assertOk()->assertSee($booking->reference_number);
});

it('grants the account discount it refused while they were a guest', function () {
    Setting::set('account_discount_enabled', true);
    Setting::set('account_discount_percent', 5);

    $guest = bookAsGuest('claim-discount@example.com', 'CLM 3333');
    $pricing = app(BookingPricing::class);

    expect($pricing->discountPercentFor($guest))->toBe(0);

    $this->post('/reset-password', [
        'token' => Password::createToken($guest), 'email' => $guest->email,
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ]);

    expect($pricing->discountPercentFor($guest->fresh()))->toBe(5);
});

it('cannot be replayed on an account that has already been claimed', function () {
    $guest = bookAsGuest('claim-replay@example.com', 'CLM 4444');
    $token = Password::createToken($guest);

    $this->post('/reset-password', [
        'token' => $token, 'email' => $guest->email,
        'password' => 'First-Passw0rd!', 'password_confirmation' => 'First-Passw0rd!',
    ])->assertRedirect(route('login'));

    // Same token again: the broker consumed it, so this changes nothing.
    $this->post('/reset-password', [
        'token' => $token, 'email' => $guest->email,
        'password' => 'Second-Passw0rd!', 'password_confirmation' => 'Second-Passw0rd!',
    ])->assertSessionHasErrors('email');

    $guest->refresh();
    expect(Hash::check('First-Passw0rd!', $guest->password))->toBeTrue();
    expect($guest->is_guest)->toBeFalse();
});

// ── Registration with an address somebody already holds ──────────────────

it('creates nothing when registering with a guest address, and emails the claim link', function () {
    config(['mail.default' => 'array']);
    $guest = bookAsGuest('claim-register@example.com', 'CLM 5555');
    $before = count(app('mailer')->getSymfonyTransport()->messages());

    $this->from('/register')->post('/register', [
        'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
        'email' => 'claim-register@example.com', 'phone' => '09171234567',
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ])->assertSessionHasErrors('email');

    // No second account, and the guest row is untouched by an unauthenticated
    // request — the link in their inbox is the only way in.
    expect(User::where('email', 'claim-register@example.com')->count())->toBe(1);
    expect($guest->fresh()->is_guest)->toBeTrue();
    expect(count(app('mailer')->getSymfonyTransport()->messages()))->toBeGreaterThan($before);
});

it('still refuses a genuinely registered account', function () {
    User::factory()->create(['email' => 'claim-real@example.com', 'role' => 'customer']);

    $this->from('/register')->post('/register', [
        'first_name' => 'Someone', 'last_name' => 'Else',
        'email' => 'claim-real@example.com', 'phone' => '09171234567',
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ])->assertSessionHasErrors('email');

    expect(User::where('email', 'claim-real@example.com')->count())->toBe(1);
});

it('says the same thing for a guest address as for a registered one', function () {
    // Otherwise the form is an email enumerator: type an address, find out
    // whether that person has booked at this shop.
    bookAsGuest('claim-same-guest@example.com', 'CLM 6666');
    User::factory()->create(['email' => 'claim-same-real@example.com', 'role' => 'customer']);

    $messageFor = function (string $email) {
        $this->from('/register')->post('/register', [
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => $email,
            'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        return session('errors')->first('email');
    };

    $guestMessage = $messageFor('claim-same-guest@example.com');
    $realMessage  = $messageFor('claim-same-real@example.com');

    expect($guestMessage)->toBe($realMessage);
    expect($guestMessage)->not->toContain('guest');
    expect($guestMessage)->not->toContain('Forgot password');
});

// ── The guard that decides who may book as a guest ───────────────────────

it('still lets an unclaimed guest book again as a guest', function () {
    bookAsGuest('claim-rebook@example.com', 'CLM 7777');

    $this->postJson('/booking/guest', [
        'guest_name' => 'Juan Dela Cruz', 'guest_email' => 'claim-rebook@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Honda', 'vehicle_model' => 'Click', 'vehicle_year' => 2022,
        'vehicle_plate' => 'CLM 8888', 'vehicle_type' => 'motorcycle',
        'service_ids' => [claimService()->id],
        'booking_date' => nextWeekdayAfter(1), 'booking_time' => '11:00',
    ])->assertOk();

    expect(User::where('email', 'claim-rebook@example.com')->count())->toBe(1);
});

it('turns away a guest booking once the account has been claimed', function () {
    $guest = bookAsGuest('claim-noguest@example.com', 'CLM 9999');

    $this->post('/reset-password', [
        'token' => Password::createToken($guest), 'email' => $guest->email,
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ]);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Juan Dela Cruz', 'guest_email' => 'claim-noguest@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Honda', 'vehicle_model' => 'Click', 'vehicle_year' => 2022,
        'vehicle_plate' => 'CLM 1010', 'vehicle_type' => 'motorcycle',
        'service_ids' => [claimService()->id],
        'booking_date' => nextWeekdayAfter(1), 'booking_time' => '11:00',
    ])->assertStatus(422);
});
