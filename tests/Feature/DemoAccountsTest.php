<?php

/**
 * The temporary demo logins: the two sample accounts the capstone panel signs
 * in with, provisioned by apx:demo-accounts and printed on /login.
 *
 * The thing worth protecting here is that the page and the provisioner never
 * disagree. A credential printed on screen that was never created, or created
 * with a different role from the one the page claims, is worse than no panel:
 * it fails in front of the audience, or it hands an admin session to someone
 * who was told they were a customer.
 *
 * Also pinned: it is OFF unless asked for. The default has to be that a fresh
 * clone and the test suite publish nothing.
 */

use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Support\Facades\Hash;

/**
 * Switch the demo on with two throwaway accounts.
 *
 * Deliberately not the real credentials. A test carrying those would commit
 * them, which is the one thing this feature must not do.
 */
function demoConfig(array $overrides = []): void
{
    config()->set('apx.demo', array_merge([
        'enabled'  => true,
        'accounts' => [
            [
                'label'    => 'Administrator',
                'role'     => 'admin',
                'name'     => 'Demo Admin',
                'email'    => 'demo-admin@example.test',
                'password' => 'DemoAdminPw!2026',
            ],
            [
                'label'    => 'Customer',
                'role'     => 'customer',
                'name'     => 'Demo Customer',
                'email'    => 'demo-customer@example.test',
                'password' => 'DemoCustomerPw!2026',
            ],
        ],
    ], $overrides));
}

it('publishes nothing and provisions nothing by default', function () {
    // config/apx.php reads DEMO_ACCOUNTS, which the test environment does not
    // set. A fresh clone must look like this.
    expect(DemoAccounts::enabled())->toBeFalse()
        ->and(DemoAccounts::all())->toBe([]);

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    expect(User::count())->toBe(0);

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('Sample Accounts');
});

it('prints every configured login on the sign-in page', function () {
    demoConfig();

    $this->get('/login')
        ->assertOk()
        ->assertSee('Sample Accounts')
        ->assertSee('demo-admin@example.test')
        ->assertSee('DemoAdminPw!2026', false)
        ->assertSee('demo-customer@example.test')
        ->assertSee('DemoCustomerPw!2026', false);
});

it('creates accounts that can actually sign in with the printed password', function () {
    demoConfig();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    // The page says it, so it has to work. This is the assertion that catches
    // the provisioner and the panel drifting apart.
    $this->post('/login', [
        'email'    => 'demo-admin@example.test',
        'password' => 'DemoAdminPw!2026',
    ])->assertRedirect(route('admin.dashboard'));
});

// A live session persists for the rest of the test, so the customer sign-in is
// its own test rather than a second assertion added to the one above.
it('signs the demo customer in to the customer dashboard', function () {
    demoConfig();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    $this->post('/login', [
        'email'    => 'demo-customer@example.test',
        'password' => 'DemoCustomerPw!2026',
    ])->assertRedirect(route('customer.dashboard'));
});

it('forces the role the page advertises onto an existing account', function () {
    // The real demo customer address was already an admin locally. An account
    // the panel calls a customer must not hold the admin side of the system.
    User::factory()->create([
        'email' => 'demo-customer@example.test',
        'role'  => 'admin',
    ]);

    demoConfig();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    expect(User::where('email', 'demo-customer@example.test')->value('role'))->toBe('customer');
});

it('is safe to run on every container start', function () {
    demoConfig();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);
    $this->artisan('apx:demo-accounts')->assertExitCode(0);
    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    expect(User::where('email', 'demo-admin@example.test')->count())->toBe(1)
        ->and(User::where('email', 'demo-customer@example.test')->count())->toBe(1);
});

it('recovers a soft-deleted account instead of colliding with its email', function () {
    // A trashed row still occupies the unique index, so create() would throw.
    $user = User::factory()->create(['email' => 'demo-admin@example.test']);
    $user->delete();

    demoConfig();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    $fresh = User::where('email', 'demo-admin@example.test')->first();

    expect($fresh)->not->toBeNull()
        ->and($fresh->role)->toBe('admin')
        ->and(Hash::check('DemoAdminPw!2026', $fresh->password))->toBeTrue();
});

it('restores a password changed in the app, so the printed one keeps working', function () {
    demoConfig();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    User::where('email', 'demo-admin@example.test')
        ->first()
        ->forceFill(['password' => 'SomethingElse!2026'])
        ->save();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    expect(Hash::check('DemoAdminPw!2026', User::where('email', 'demo-admin@example.test')->value('password')))
        ->toBeTrue();
});

it('skips an account whose password is not configured', function () {
    demoConfig(['accounts' => [
        ['label' => 'Administrator', 'role' => 'admin', 'name' => 'A', 'email' => 'demo-admin@example.test', 'password' => ''],
        ['label' => 'Customer', 'role' => 'customer', 'name' => 'C', 'email' => 'demo-customer@example.test', 'password' => 'DemoCustomerPw!2026'],
    ]]);

    expect(DemoAccounts::all())->toHaveCount(1);

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    expect(User::where('email', 'demo-admin@example.test')->exists())->toBeFalse()
        ->and(User::where('email', 'demo-customer@example.test')->exists())->toBeTrue();

    $this->get('/login')->assertDontSee('demo-admin@example.test');
});

it('cannot mint an admin from an unrecognised role', function () {
    // A typo in a deploy variable must not be a privilege escalation.
    demoConfig(['accounts' => [
        ['label' => 'Staff', 'role' => 'superuser', 'name' => 'S', 'email' => 'demo-staff@example.test', 'password' => 'DemoStaffPw!2026'],
    ]]);

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    expect(User::where('email', 'demo-staff@example.test')->value('role'))->toBe('customer');
});

it('retires the printed passwords when pruned', function () {
    demoConfig();

    $this->artisan('apx:demo-accounts')->assertExitCode(0);
    $this->artisan('apx:demo-accounts', ['--prune' => true])->assertExitCode(0);

    // Nothing is deleted — the customer account may hold real bookings.
    expect(User::where('email', 'demo-customer@example.test')->exists())->toBeTrue();

    $this->post('/login', [
        'email'    => 'demo-admin@example.test',
        'password' => 'DemoAdminPw!2026',
    ])->assertSessionHasErrors();
});

it('refuses to prune when it no longer knows which accounts were advertised', function () {
    config()->set('apx.demo.enabled', false);

    $this->artisan('apx:demo-accounts', ['--prune' => true])->assertExitCode(1);
});

it('normalises the configured address the same way the page prints it', function () {
    demoConfig(['accounts' => [
        ['label' => 'Customer', 'role' => 'customer', 'name' => 'C', 'email' => '  Demo-Customer@Example.TEST ', 'password' => 'DemoCustomerPw!2026'],
    ]]);

    $this->artisan('apx:demo-accounts')->assertExitCode(0);

    // Printed, provisioned and looked up as one address. Otherwise the panel
    // advertises a login the unique index stored under a different spelling.
    expect(User::where('email', 'demo-customer@example.test')->exists())->toBeTrue();

    $this->get('/login')->assertSee('demo-customer@example.test');
});
