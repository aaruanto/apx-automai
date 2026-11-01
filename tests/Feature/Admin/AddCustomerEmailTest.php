<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The Add Customer form agrees with the server about the email field.
 *
 * store() has always had 'email' => ['required', EmailRule::strict(),
 * 'unique:users,email']. The form said otherwise three times over: the label
 * carried no asterisk while every other required field on it does, the input
 * said "optional" and had no required attribute, and FormValidate was given
 * name, phone and plate but never email. So an admin left it blank in good
 * faith and the form came back refused.
 *
 * The server rule was the correct one. These pin the three signals to it.
 */

function aceAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('marks the email field required on the create form', function () {
    $html = $this->actingAs(aceAdmin())->get('/admin/customers/create')->assertOk()->getContent();

    // The same asterisk markup the other three required fields use.
    expect($html)->toMatch('/Email Address\s*<span style="color:var\(--red\)">\*<\/span>/');
    expect($html)->toMatch('/id="custCreateEmail"[^>]*/');
    expect($html)->toContain('required />');
    expect($html)->not->toContain('placeholder="optional"');
});

it('checks the address in the browser with the shared rule', function () {
    $html = $this->actingAs(aceAdmin())->get('/admin/customers/create')->assertOk()->getContent();

    // FormValidate.rules.email() is written to refuse what EmailRule::strict()
    // refuses. A second regex here would drift from it.
    expect($html)->toContain("FormValidate.register(custCreateEmail");
    expect($html)->toContain('FormValidate.rules.email()');
    expect($html)->toContain("FormValidate.rules.required('Email address is required.')");
});

it('leaves the email read-only when editing a customer', function () {
    $customer = Customer::create([
        'user_id' => User::factory()->create(['role' => 'customer', 'email' => 'existing@gmail.com'])->id,
        'phone'   => '09170001111',
    ]);

    $html = $this->actingAs(aceAdmin())
        ->get('/admin/customers/'.$customer->id.'/edit')
        ->assertOk()->getContent();

    // The address is the identity they sign in with, and update() refuses the
    // field anyway. No asterisk, no input, and the locked block instead.
    expect($html)->toContain('aria-readonly="true"');
    expect($html)->not->toContain('id="custCreateEmail"');
    expect($html)->not->toMatch('/Email Address\s*<span style="color:var\(--red\)">\*<\/span>/');
});

it('still refuses a blank address on the server', function () {
    $this->actingAs(aceAdmin())
        ->from('/admin/customers/create')
        ->post('/admin/customers', [
            'name'  => 'No Email Given',
            'phone' => '09170002222',
            'plate' => 'ACE 0001',
        ])
        ->assertRedirect('/admin/customers/create')
        ->assertSessionHasErrors('email');

    expect(User::where('name', 'No Email Given')->exists())->toBeFalse();
});
