<?php

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Support\EmailRule;

test('changing an email address goes through the strict rule', function () {
    // Wiring, not behaviour, and deliberately so. EmailRule resolves the
    // domain's MX/A records in production but returns plain email:rfc under
    // test, because the suite would otherwise need working DNS and
    // example.com has no MX record. In Laravel 12 'email' and 'email:rfc'
    // are the same validator, so no assertion about a rejected address can
    // tell the two apart here — only production can.
    //
    // What went wrong is worth guarding anyway: registration and guest
    // booking resolved the domain and this screen did not, so an address
    // refused at sign-up could be set afterwards by editing the profile.
    // This fails if someone puts the bare rule back.
    $user = User::factory()->create();

    $request = new ProfileUpdateRequest();
    $request->setUserResolver(fn () => $user);

    expect($request->rules()['email'])->toContain(EmailRule::strict());
});

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();

    // Deletion is two-stage: the account is soft-deleted (and so recoverable
    // for 30 days) rather than removed outright, because bookings cascade
    // from users and a hard delete would take the shop's revenue history.
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
