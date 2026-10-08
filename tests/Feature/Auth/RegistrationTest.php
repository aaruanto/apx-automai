<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'customer']);
    $response->assertRedirect(route('login', absolute: false));
});

test('registering does not sign the user in', function () {
    // Creating an account and authenticating are deliberately separate steps:
    // the customer signs in with the credentials they just chose, which
    // confirms they recorded the password.
    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
});

test('registration confirms the account was created', function () {
    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHas('status');

    // The message has to actually reach the page, not just the session.
    $this->get('/login')->assertOk()->assertSee('Account created successfully', false);
});

test('the credentials chosen at registration work at login', function () {
    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'correct-horse-battery',
    ])->assertRedirect(route('customer.dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('claiming a guest account does not sign the user in either', function () {
    // Guest bookings create an unverified placeholder account, which the
    // customer later claims by registering with the same email.
    $guest = User::create([
        'name'     => 'Guest Person',
        'email'    => 'guest@example.com',
        'role'     => 'customer',
        'password' => 'unknown-to-anyone',
    ]);

    $this->post('/register', [
        'first_name' => 'Guest',
        'last_name'  => 'Person',
        'email'      => 'guest@example.com',
        'password'   => 'chosen-at-claim',
        'password_confirmation' => 'chosen-at-claim',
    ])->assertRedirect(route('login', absolute: false));

    $this->assertGuest();

    // The claim replaced the placeholder password rather than adding a user.
    expect(User::where('email', 'guest@example.com')->count())->toBe(1);

    $this->post('/login', [
        'email' => 'guest@example.com',
        'password' => 'chosen-at-claim',
    ]);
    $this->assertAuthenticated();

    expect($guest->fresh()->name)->toBe('Guest Person');
});
