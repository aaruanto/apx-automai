<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Maintenance mode closes the customer portal while leaving the admin a way
 * back in to switch it off.
 */

function maintenanceOn(): void
{
    Setting::setMany(['maintenance' => true]);
}

function mCustomer(): User
{
    return User::factory()->create(['role' => 'customer', 'password' => 'GoodPass!2345']);
}

function mAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'password' => 'GoodPass!2345']);
}

// ── Customers are shut out ───────────────────────────────────────────────

it('stops a customer logging in', function () {
    $customer = mCustomer();
    maintenanceOn();

    $this->post('/login', [
        'email'    => $customer->email,
        'password' => 'GoodPass!2345',
    ])->assertSessionHasErrors('email');

    // Authenticated briefly to learn the role, then signed back out.
    $this->assertGuest();
});

it('closes registration entirely', function () {
    maintenanceOn();

    $this->get('/register')->assertStatus(503);

    $this->post('/register', [
        'first_name' => 'New', 'last_name' => 'Person',
        'email' => 'newperson@gmail.com',
        'password' => 'GoodPass!2345', 'password_confirmation' => 'GoodPass!2345',
    ])->assertStatus(503);

    expect(User::where('email', 'newperson@gmail.com')->exists())->toBeFalse();
});

it('shows the maintenance page to a customer already signed in', function () {
    $customer = mCustomer();
    maintenanceOn();

    $this->actingAs($customer)->get('/customer/dashboard')
        ->assertStatus(503)
        ->assertSee('We\'ll be back shortly', false);
});

it('gives that page a working logout', function () {
    $customer = mCustomer();
    maintenanceOn();

    $this->actingAs($customer)->get('/customer/dashboard')
        ->assertStatus(503)
        ->assertSee('Log out');

    // The route is reachable, and it really ends the session.
    $this->actingAs($customer)->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

it('blocks guest booking', function () {
    maintenanceOn();

    $this->postJson('/booking/guest', [])->assertStatus(503);
});

// ── The admin keeps a way in ─────────────────────────────────────────────

it('still lets an admin log in', function () {
    $admin = mAdmin();
    maintenanceOn();

    $this->post('/login', [
        'email'    => $admin->email,
        'password' => 'GoodPass!2345',
    ])->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});

it('keeps the login page reachable so the admin is never locked out', function () {
    maintenanceOn();

    // Closing this page would strand the only account that can switch
    // maintenance off, since the role is unknown before credentials are checked.
    $this->get('/login')->assertOk()->assertSee('temporarily unavailable', false);
});

it('leaves the admin dashboard and settings working', function () {
    $admin = mAdmin();
    maintenanceOn();

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    $this->actingAs($admin)->get('/admin/settings')->assertOk();
});

it('lets the admin switch maintenance back off', function () {
    $admin = mAdmin();
    maintenanceOn();

    $this->actingAs($admin)->put('/admin/settings/system', [
        'default_theme'  => 'light',
        'show_id_prefix' => '1',
        // maintenance omitted = off
    ])->assertSessionHasNoErrors();

    expect(Setting::get('maintenance'))->toBeFalse();

    // And the portal reopens. Sign the admin out first: POST /login from an
    // authenticated session hits the guest middleware and redirects.
    $this->post('/logout');

    $customer = mCustomer();
    $this->post('/login', [
        'email' => $customer->email, 'password' => 'GoodPass!2345',
    ])->assertRedirect(route('customer.dashboard', absolute: false));
});

it('turns away an account left on the removed staff role', function () {
    // The staff role and its portal are gone. A row still carrying that value
    // must be treated as an ordinary customer — turned away by maintenance,
    // and never redirected to a route that no longer exists.
    $stale = User::factory()->create(['role' => 'staff', 'password' => 'GoodPass!2345']);
    maintenanceOn();

    $this->post('/login', [
        'email' => $stale->email, 'password' => 'GoodPass!2345',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

// ── Off is off ───────────────────────────────────────────────────────────

it('changes nothing while maintenance is off', function () {
    $customer = mCustomer();

    $this->get('/register')->assertOk();
    $this->get('/login')->assertOk()->assertDontSee('temporarily unavailable', false);

    $this->post('/login', [
        'email' => $customer->email, 'password' => 'GoodPass!2345',
    ])->assertRedirect(route('customer.dashboard', absolute: false));
});
