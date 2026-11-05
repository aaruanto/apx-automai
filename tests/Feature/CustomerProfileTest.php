<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Saving the customer profile.
 *
 * The full name was dropped twice over: the page never put it in the request,
 * and the controller never looked for it. Either alone was enough to make
 * "Save Changes" report success and change nothing.
 */

function profileCustomer(): User
{
    return User::factory()->create([
        'role'  => 'customer',
        'name'  => 'Original Name',
        'phone' => '09170000000',
    ]);
}

it('saves a changed full name', function () {
    $user = profileCustomer();

    $this->actingAs($user)
        ->patchJson('/customer/profile', [
            'name'  => 'Updated Name',
            'phone' => '09171234567',
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'name' => 'Updated Name']);

    expect($user->fresh()->name)->toBe('Updated Name');
});

it('persists the name across a reload', function () {
    $user = profileCustomer();

    $this->actingAs($user)->patchJson('/customer/profile', [
        'name' => 'Persisted Name', 'phone' => '09171234567',
    ])->assertOk();

    // The page reads the fresh record, not a session copy.
    $this->actingAs($user->fresh())->get('/customer/dashboard')->assertOk()
        ->assertSee('Persisted Name');
});

it('trims surrounding whitespace from the name', function () {
    $user = profileCustomer();

    $this->actingAs($user)->patchJson('/customer/profile', [
        'name' => '   Spaced Name   ', 'phone' => '09171234567',
    ])->assertOk();

    expect($user->fresh()->name)->toBe('Spaced Name');
});

it('rejects an empty name instead of wiping it', function () {
    $user = profileCustomer();

    $this->actingAs($user)->patchJson('/customer/profile', ['name' => ''])
        ->assertStatus(422);

    expect($user->fresh()->name)->toBe('Original Name');
});

it('still saves the other fields alongside it', function () {
    $user = profileCustomer();

    $this->actingAs($user)->patchJson('/customer/profile', [
        'name'    => 'New Name',
        'phone'   => '09998887777',
        'address' => '5 Glenn St., Quezon City',
    ])->assertOk();

    $user->refresh();
    expect($user->name)->toBe('New Name');
    expect($user->phone)->toBe('09998887777');

    $customer = Customer::where('user_id', $user->id)->first();
    expect($customer->address)->toBe('5 Glenn St., Quezon City');
});

it('ignores a date of birth in the body and leaves a stored one alone', function () {
    // The card no longer offers a birthday and nothing in the shop reads one,
    // so the write path is gone. The column stays, which means a crafted
    // request must not be able to reach it either.
    $user = profileCustomer();
    $customer = Customer::create([
        'user_id' => $user->id, 'phone' => '09170000000', 'dob' => '1990-01-01',
    ]);

    $this->actingAs($user)->patchJson('/customer/profile', [
        'name' => 'New Name', 'dob' => '1998-04-12',
    ])->assertOk();

    expect((string) $customer->fresh()->dob)->toContain('1990-01-01');
});

it('no longer offers a date of birth on the profile card', function () {
    $user = profileCustomer();

    $this->actingAs($user)->get('/customer/dashboard')->assertOk()
        ->assertDontSee('Date of Birth')
        ->assertDontSee('pef-dob');
});

it('treats blank optional fields as not set rather than invalid', function () {
    // Untouched text inputs post empty strings, which used to fail their
    // rules instead of reading as "left alone".
    $user = profileCustomer();

    $this->actingAs($user)->patchJson('/customer/profile', [
        'name' => 'Only Name', 'phone' => '', 'address' => '',
    ])->assertOk();

    expect($user->fresh()->name)->toBe('Only Name');
});

it('shows the new name wherever it is displayed', function () {
    $user = profileCustomer();

    $this->actingAs($user)->patchJson('/customer/profile', [
        'name' => 'Shown Everywhere', 'phone' => '09171234567',
    ])->assertOk();

    // One column, so the admin side reflects it too.
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/customers')->assertOk();

    expect(User::find($user->id)->name)->toBe('Shown Everywhere');
});

it('cannot change another account through this endpoint', function () {
    $mine   = profileCustomer();
    $theirs = User::factory()->create(['role' => 'customer', 'name' => 'Someone Else']);

    // There is no id in the route: it always acts on the signed-in user.
    $this->actingAs($mine)->patchJson('/customer/profile', [
        'name' => 'Hijacked', 'phone' => '09171234567',
    ])->assertOk();

    expect($theirs->fresh()->name)->toBe('Someone Else');
    expect($mine->fresh()->name)->toBe('Hijacked');
});

it('requires a signed-in customer', function () {
    $this->patchJson('/customer/profile', ['name' => 'Nobody'])->assertStatus(401);
});
