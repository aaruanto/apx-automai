<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('the sign-in page does not call itself the admin portal', function () {
    // The footer read "Admin Portal" on a page that is the way in for
    // customers too — and the only page where nobody has a role yet. The
    // admin layout still says it, correctly, which is why this asserts on
    // the string rather than on the absence of the word "Admin".
    $this->get('/login')
        ->assertStatus(200)
        ->assertDontSee('Admin Portal')
        ->assertSee('AutoMai &mdash; Portal', false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('customer.dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
