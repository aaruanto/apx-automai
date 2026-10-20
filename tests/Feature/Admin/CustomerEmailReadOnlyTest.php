<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A customer's email address is theirs, not the shop's.
 *
 * It is the identity they sign in with and the address every booking
 * confirmation and reminder is sent to, so an admin changing it from the
 * customer screen would quietly lock someone out of their own account and
 * redirect their mail.
 *
 * The edit form used to render it as an ordinary editable input. The server
 * already refused the change, but the form invited one and then reported a
 * failure, which reads like a bug rather than a rule. These tests hold both
 * halves: the field is not offered, and it would not be honoured if it were.
 */
function emailAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function customerWithEmail(string $email): Customer
{
    $user = User::factory()->create(['role' => 'customer', 'email' => $email]);

    return Customer::create([
        'user_id' => $user->id,
        'phone'   => '09171234567',
        'address' => 'Quezon City',
    ]);
}

it('shows the address on the edit screen without offering to change it', function () {
    $customer = customerWithEmail('owner@gmail.com');

    $html = $this->actingAs(emailAdmin())
        ->get("/admin/customers/{$customer->id}/edit")
        ->assertOk()
        ->getContent();

    // Readable, because staff need to know who they are looking at...
    expect($html)->toContain('owner@gmail.com');

    // ...but not as a field, and not as a link that opens a mail client.
    expect($html)->not->toContain('name="email"');
    expect($html)->not->toContain('mailto:');
});

it('still offers the field when creating a customer, which has no address to protect', function () {
    $html = $this->actingAs(emailAdmin())
        ->get('/admin/customers/create')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('name="email"');
});

it('does not change the address even when one is posted directly', function () {
    $customer = customerWithEmail('owner@gmail.com');

    // Removing the input is not the guard. This is the guard.
    $this->actingAs(emailAdmin())->put("/admin/customers/{$customer->id}", [
        'name'  => 'Renamed Person',
        'phone' => '09998887777',
        'email' => 'attacker@evil.test',
    ]);

    expect($customer->user->fresh()->email)->toBe('owner@gmail.com');
});

it('does not quietly accept the rest of the form either', function () {
    $customer = customerWithEmail('owner@gmail.com');

    $this->actingAs(emailAdmin())->put("/admin/customers/{$customer->id}", [
        'name'  => 'Renamed Person',
        'phone' => '09998887777',
    ])->assertRedirect()->assertSessionHas('error');

    expect($customer->user->fresh()->name)->not->toBe('Renamed Person');
    expect($customer->fresh()->phone)->toBe('09171234567');
});

it('keeps the customer screen away from customers', function () {
    $customer = customerWithEmail('owner@gmail.com');
    $other    = User::factory()->create(['role' => 'customer']);

    $this->actingAs($other)
        ->get("/admin/customers/{$customer->id}/edit")
        ->assertForbidden();
});
