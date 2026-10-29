<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Registration must never reach an existing account.
 *
 * It used to: a "claim your guest account" path overwrote the existing user's
 * password whenever that account was unverified and its name began with
 * whatever first_name the registrant typed. Nothing in this app sets
 * email_verified_at, so that covered almost every account, and str_starts_with
 * matches a single letter.
 */

it('rejects registering with an email that already exists', function () {
    $victim = User::create([
        'name' => 'Gelo Ruanto', 'email' => 'victim@example.com',
        'role' => 'customer', 'password' => 'VictimSecret!1',
    ]);

    $this->post('/register', [
        'first_name' => 'Gelo',
        'last_name'  => 'Ruanto',
        'email'      => 'victim@example.com',
        'password'   => 'AttackerChosen!1',
        'password_confirmation' => 'AttackerChosen!1',
    ])->assertSessionHasErrors('email');

    // The victim's password must be untouched.
    expect(Hash::check('VictimSecret!1', $victim->fresh()->password))->toBeTrue();
    expect(Hash::check('AttackerChosen!1', $victim->fresh()->password))->toBeFalse();
    expect(User::where('email', 'victim@example.com')->count())->toBe(1);
});

it('rejects it even when only the first letter of the name is guessed', function () {
    // str_starts_with matched a single character, so the attacker needed
    // almost nothing: the email, and one letter.
    $victim = User::create([
        'name' => 'Gelo Ruanto', 'email' => 'victim@example.com',
        'role' => 'customer', 'password' => 'VictimSecret!1',
    ]);

    $this->post('/register', [
        'first_name' => 'G',
        'last_name'  => 'X',
        'email'      => 'victim@example.com',
        'password'   => 'AttackerChosen!1',
        'password_confirmation' => 'AttackerChosen!1',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('VictimSecret!1', $victim->fresh()->password))->toBeTrue();
});

it('rejects a duplicate that differs only by case', function () {
    User::create([
        'name' => 'Gelo Ruanto', 'email' => 'victim@example.com',
        'role' => 'customer', 'password' => 'VictimSecret!1',
    ]);

    $this->post('/register', [
        'first_name' => 'Someone',
        'last_name'  => 'Else',
        'email'      => 'Victim@Example.COM',
        'password'   => 'AttackerChosen!1',
        'password_confirmation' => 'AttackerChosen!1',
    ])->assertSessionHasErrors('email');

    expect(User::count())->toBe(1);
});

it('rejects a duplicate padded with whitespace', function () {
    User::create([
        'name' => 'Gelo Ruanto', 'email' => 'victim@example.com',
        'role' => 'customer', 'password' => 'VictimSecret!1',
    ]);

    $this->post('/register', [
        'first_name' => 'Someone',
        'last_name'  => 'Else',
        'email'      => '  victim@example.com  ',
        'password'   => 'AttackerChosen!1',
        'password_confirmation' => 'AttackerChosen!1',
    ])->assertSessionHasErrors('email');

    expect(User::count())->toBe(1);
});

it('protects admin and staff accounts the same way', function (string $role) {
    $target = User::create([
        'name' => 'Admin Person', 'email' => 'staffer@example.com',
        'role' => $role, 'password' => 'TheirSecret!1',
    ]);

    $this->post('/register', [
        'first_name' => 'Admin',
        'last_name'  => 'Person',
        'email'      => 'staffer@example.com',
        'password'   => 'AttackerChosen!1',
        'password_confirmation' => 'AttackerChosen!1',
    ])->assertSessionHasErrors('email');

    $target->refresh();
    expect(Hash::check('TheirSecret!1', $target->password))->toBeTrue();
    // Privilege must not be downgraded to customer either.
    expect($target->role)->toBe($role);
})->with(['admin', 'staff']);

it('still lets a genuinely new email register', function () {
    $this->post('/register', [
        'first_name' => 'Brand',
        'last_name'  => 'New',
        'email'      => 'brandnew@example.com',
        'password'   => 'GoodPass!1234',
        'password_confirmation' => 'GoodPass!1234',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login', absolute: false));

    expect(User::where('email', 'brandnew@example.com')->count())->toBe(1);
});

it('stores the email lowercased and trimmed', function () {
    $this->post('/register', [
        'first_name' => 'Mixed',
        'last_name'  => 'Case',
        'email'      => '  MixedCase@Example.COM ',
        'password'   => 'GoodPass!1234',
        'password_confirmation' => 'GoodPass!1234',
    ])->assertSessionHasNoErrors();

    // Normalised on the way in, so a later case variant cannot slip past.
    expect(User::whereNotNull('email')->latest('id')->first()->email)->toBe('mixedcase@example.com');
});

// ── Guest booking must not reach an existing account either ─────────────

it('blocks a guest booking on an email that belongs to a real account', function () {
    // The gate tested email_verified_at, which nothing sets, so this never
    // fired: the booking was filed straight into the real owner's account.
    $owner = User::create([
        'name' => 'Real Owner', 'email' => 'owner@example.com',
        'role' => 'customer', 'password' => 'OwnerSecret!1', 'is_guest' => false,
    ]);

    $service = App\Models\Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 45]);

    $this->postJson('/booking/guest', [
        'guest_name'    => 'Not The Owner',
        'guest_email'   => 'owner@example.com',
        'guest_phone'   => '09171234567',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'GST 1234',
        'service_ids'   => [$service->id],
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
    ])->assertStatus(422);

    // Nothing of the owner's was touched, and no booking was filed to them.
    expect($owner->fresh()->name)->toBe('Real Owner');
    expect(App\Models\Booking::where('user_id', $owner->id)->count())->toBe(0);
});

it('still lets a previous guest book again', function () {
    // Their account was created for them and they have never set a password,
    // so "please log in" would strand them.
    $guest = User::create([
        'name' => 'Repeat Guest', 'email' => 'repeat@example.com',
        'role' => 'customer', 'password' => 'random', 'is_guest' => true,
    ]);

    $service = App\Models\Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 45]);

    $this->postJson('/booking/guest', [
        'guest_name'    => 'Repeat Guest',
        'guest_email'   => 'repeat@example.com',
        'guest_phone'   => '09171234567',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'RPT 1234',
        'service_ids'   => [$service->id],
        'booking_date'  => nextWeekday(),
        'booking_time'  => '13:00',
    ])->assertOk();

    expect(App\Models\Booking::where('user_id', $guest->id)->count())->toBe(1);
    expect(User::where('email', 'repeat@example.com')->count())->toBe(1);
});

it('marks a new guest account as a guest', function () {
    $service = App\Models\Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 45]);

    $this->postJson('/booking/guest', [
        'guest_name'    => 'Brand New Guest',
        'guest_email'   => 'NewGuest@Example.COM',
        'guest_phone'   => '09171234567',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'NWG 1234',
        'service_ids'   => [$service->id],
        'booking_date'  => nextWeekday(),
        'booking_time'  => '14:00',
    ])->assertOk();

    // Normalised on the way in, and flagged so a later booking still works.
    $user = User::where('email', 'newguest@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->is_guest)->toBeTrue();
});

/*
 * A guest booking must never reach a registered account. firstOrCreate on the
 * email alone found the real customer, attached the booking to them, and then
 * overwrote their name and phone with whatever the form said.
 */

function guestBooking(array $overrides = []): array
{
    $service = App\Models\Service::create([
        'name' => 'Svc '.uniqid(), 'price' => 1500, 'duration' => 45,
    ]);

    return array_merge([
        'guest_name'    => 'Someone Else',
        'guest_email'   => 'victim@example.com',
        'guest_phone'   => '09170000000',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'GST 4321',
        'service_ids'   => [$service->id],
        'booking_date'  => nextWeekday(),
        'booking_time'  => '14:00',
    ], $overrides);
}

it('refuses a guest booking that uses a registered account email', function () {
    // Already guarded before this phase; pinned here because the protection
    // is easy to lose and the consequence is renaming a real customer.
    $victim = App\Models\User::factory()->create([
        'name' => 'Real Customer', 'email' => 'victim@example.com',
        'phone' => '09171111111', 'role' => 'customer', 'is_guest' => false,
    ]);

    $this->postJson('/booking/guest', guestBooking())
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    // The account is untouched: this used to rename the real customer.
    expect($victim->fresh()->name)->toBe('Real Customer');
    expect($victim->fresh()->phone)->toBe('09171111111');
    expect(App\Models\Booking::where('user_id', $victim->id)->count())->toBe(0);
});

it('still lets a returning guest reuse their own guest record', function () {
    $this->postJson('/booking/guest', guestBooking([
        'guest_email' => 'returning@example.com', 'vehicle_plate' => 'RET 1111',
    ]))->assertOk();

    // A different time on the return visit. Both used to sit at the helper's
    // default 14:00, which the duplicate guard now refuses — one car cannot
    // hold the same window twice. The point here is the account, not the slot:
    // a returning guest reuses their record rather than growing a second one.
    $this->postJson('/booking/guest', guestBooking([
        'guest_email' => 'returning@example.com', 'vehicle_plate' => 'RET 1111',
        'booking_time' => '16:00',
    ]))->assertOk();

    // One guest account, two bookings — not a second row each time.
    expect(App\Models\User::where('email', 'returning@example.com')->count())->toBe(1);
});
