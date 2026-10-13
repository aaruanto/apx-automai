<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Changing a password from inside the app.
 *
 * Customers had no way to do this at all: the Settings link pointed at
 * /forgot-password, which emails a reset link, and mail is unconfigured.
 */

function pwCustomer(string $password = 'OldPass!2345'): User
{
    return User::factory()->create(['role' => 'customer', 'password' => $password]);
}

function pwAdmin(string $password = 'OldPass!2345'): User
{
    return User::factory()->create(['role' => 'admin', 'password' => $password]);
}

// ── Customer ─────────────────────────────────────────────────────────────

it('lets a customer change their password', function () {
    $user = pwCustomer();

    $this->actingAs($user)->putJson('/customer/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'BrandNew!6789',
        'password_confirmation' => 'BrandNew!6789',
    ])->assertOk()->assertJson(['success' => true]);

    expect(Hash::check('BrandNew!6789', $user->fresh()->password))->toBeTrue();
});

it('requires the correct current password', function () {
    $user = pwCustomer();

    $this->actingAs($user)->putJson('/customer/password', [
        'current_password'      => 'WrongPass!1',
        'password'              => 'BrandNew!6789',
        'password_confirmation' => 'BrandNew!6789',
    ])->assertStatus(422);

    expect(Hash::check('OldPass!2345', $user->fresh()->password))->toBeTrue();
});

it('rejects a weak new password', function (string $weak) {
    $user = pwCustomer();

    $this->actingAs($user)->putJson('/customer/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => $weak,
        'password_confirmation' => $weak,
    ])->assertStatus(422);

    expect(Hash::check('OldPass!2345', $user->fresh()->password))->toBeTrue();
})->with([
    'too short'   => ['Ab1!'],
    'no symbol'   => ['Abcdefgh1'],
    'no number'   => ['Abcdefgh!'],
    'no mixed case' => ['abcdefgh1!'],
]);

it('rejects reusing the current password', function () {
    $user = pwCustomer();

    $this->actingAs($user)->putJson('/customer/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'OldPass!2345',
        'password_confirmation' => 'OldPass!2345',
    ])->assertStatus(422);
});

it('requires the confirmation to match', function () {
    $user = pwCustomer();

    $this->actingAs($user)->putJson('/customer/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'BrandNew!6789',
        'password_confirmation' => 'Different!6789',
    ])->assertStatus(422);
});

it('never returns the password in the response', function () {
    $user = pwCustomer();

    $response = $this->actingAs($user)->putJson('/customer/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'BrandNew!6789',
        'password_confirmation' => 'BrandNew!6789',
    ])->assertOk();

    expect($response->getContent())->not->toContain('BrandNew!6789');
    expect($response->getContent())->not->toContain('OldPass!2345');
});

it('keeps the endpoint behind auth', function () {
    $this->putJson('/customer/password', [
        'current_password' => 'x', 'password' => 'y', 'password_confirmation' => 'y',
    ])->assertStatus(401);
});

it('rate limits repeated attempts', function () {
    $user = pwCustomer();

    // throttle:6,1 — the seventh within a minute is refused.
    foreach (range(1, 6) as $i) {
        $this->actingAs($user)->putJson('/customer/password', [
            'current_password' => 'WrongPass!1', 'password' => 'BrandNew!6789',
            'password_confirmation' => 'BrandNew!6789',
        ])->assertStatus(422);
    }

    $this->actingAs($user)->putJson('/customer/password', [
        'current_password' => 'WrongPass!1', 'password' => 'BrandNew!6789',
        'password_confirmation' => 'BrandNew!6789',
    ])->assertStatus(429);
});

// ── Admin ────────────────────────────────────────────────────────────────

it('lets an admin change their password', function () {
    $admin = pwAdmin();

    $this->actingAs($admin)->put('/admin/profile/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'BrandNew!6789',
        'password_confirmation' => 'BrandNew!6789',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('BrandNew!6789', $admin->fresh()->password))->toBeTrue();
});

it('holds the admin to the same strength rules', function () {
    $admin = pwAdmin();

    // "password123" passed the old min:8|confirmed rule.
    $this->actingAs($admin)->put('/admin/profile/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('OldPass!2345', $admin->fresh()->password))->toBeTrue();
});

it('stops an admin reusing their current password', function () {
    $admin = pwAdmin();

    $this->actingAs($admin)->put('/admin/profile/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'OldPass!2345',
        'password_confirmation' => 'OldPass!2345',
    ])->assertSessionHasErrors('password');
});

// ── Stored form ──────────────────────────────────────────────────────────

it('always stores a bcrypt hash, never the plain value', function () {
    $user = pwCustomer();

    $this->actingAs($user)->putJson('/customer/password', [
        'current_password'      => 'OldPass!2345',
        'password'              => 'BrandNew!6789',
        'password_confirmation' => 'BrandNew!6789',
    ])->assertOk();

    $stored = $user->fresh()->password;
    expect($stored)->not->toBe('BrandNew!6789');
    expect($stored)->toMatch('/^\$2[aby]\$\d{2}\$/');
});
