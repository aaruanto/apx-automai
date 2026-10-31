<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The sign-in forms must not be restorable from the back/forward cache.
 *
 * NoCacheAuthenticated covers pages rendered for a signed-in user and
 * deliberately early-returns for everyone else, so that public pages and
 * assets keep caching normally. That left the login form — the one page in
 * this application someone types a password into — served with nothing but
 * "no-cache, private".
 *
 * no-cache is not no-store. It means "revalidate before reusing", and a page
 * carrying it is still eligible for bfcache: pressing Back restores the live
 * DOM, typed values and all, without asking the server anything. Measured in a
 * browser profile with no saved passwords: the typed password came back, and a
 * JavaScript variable set before leaving came back with it, which is how you
 * can tell the page was restored rather than re-fetched. After this change the
 * marker is gone, the password field is empty, and Chrome reports the reason
 * as CacheControlNoStoreHTTPOnlyCookieModified.
 */

it('sends no-store on every page that takes a password or an address', function (string $url) {
    $header = $this->get($url)->assertOk()->headers->get('Cache-Control');

    // no-store is the one that takes a page out of bfcache. The rest of the
    // set is for older browsers and proxies that predate it.
    expect($header)->toContain('no-store');
    expect($header)->toContain('no-cache');
})->with([
    'login'           => ['/login'],
    'register'        => ['/register'],
    'forgot password' => ['/forgot-password'],
    'reset password'  => ['/reset-password/token-placeholder?email=someone@example.com'],
]);

it('leaves the landing page caching as it was', function () {
    // The landing page and its images are the heaviest thing this app serves,
    // on a free instance. Blanket no-store across the web group would have
    // been the easy fix and the wrong one.
    $header = $this->get('/')->assertOk()->headers->get('Cache-Control');

    expect($header)->not->toContain('no-store');
});

it('still sends no-store on a signed-in page', function () {
    // The original reason the middleware exists: Back after logout must not
    // re-render a dashboard on a shared machine.
    $customer = User::factory()->create(['role' => 'customer']);

    $header = $this->actingAs($customer)->get('/customer/dashboard')->headers->get('Cache-Control');

    expect($header)->toContain('no-store');
});

it('never puts a password back into the login form', function () {
    // The server side of the report, asserted rather than assumed: a failed
    // login hands back the address and nothing else. Laravel's dontFlash
    // covers password, password_confirmation and current_password.
    $user = User::factory()->create(['email' => 'someone@gmail.com']);

    $html = $this->from('/login')
        ->followingRedirects()
        ->post('/login', ['email' => 'someone@gmail.com', 'password' => 'wrong-password-entirely'])
        ->assertOk()
        ->getContent();

    expect($html)->toContain('value="someone@gmail.com"');
    expect($html)->not->toContain('wrong-password-entirely');

    // And the password input carries no value attribute at all.
    expect($html)->toMatch('/name="password" type="password"\s*\n?\s*placeholder="[^"]*" required/');
});

it('hands back nothing at all after a clean logout', function () {
    $user = User::factory()->create(['email' => 'someone@gmail.com']);

    $this->actingAs($user)->post('/logout')->assertRedirect();

    // destroy() invalidates the session, which takes the flash bag with it, so
    // old('email') is empty. Anything still appearing on the page after this
    // is the browser's own doing, not the application's.
    $html = $this->get('/login')->assertOk()->getContent();

    expect($html)->toContain('name="email"');
    expect($html)->toContain('value=""');
    expect($html)->not->toContain('someone@gmail.com');
});

// ── autocomplete: a decision, not an omission ────────────────────────────

/**
 * The forms carried no autocomplete attribute at all, so browsers applied
 * their sign-in defaults by guessing from the field types and names. Marking
 * them up correctly does not suppress autofill — Chrome, Firefox and Safari
 * deliberately ignore autocomplete="off" on password fields, on the grounds
 * that password managers make people safer — it makes autofill put the right
 * credential in the right field and save it against the right site.
 *
 * reset-password already did this; login, register and forgot-password did
 * not, so the four forms disagreed.
 */
it('tells the browser which credential belongs in which field', function (string $url, array $expected) {
    $html = str_replace(["\n", "\r"], ' ', $this->get($url)->assertOk()->getContent());

    foreach ($expected as $field => $token) {
        expect($html)->toMatch('/name="'.$field.'"[^>]{0,250}autocomplete="'.$token.'"/');
    }
})->with([
    'login'    => ['/login', ['email' => 'username', 'password' => 'current-password']],
    'register' => ['/register', ['email' => 'username', 'password' => 'new-password', 'password_confirmation' => 'new-password']],
    'forgot'   => ['/forgot-password', ['email' => 'username']],
    'reset'    => ['/reset-password/tok?email=a@b.com', ['email' => 'username', 'password' => 'new-password']],
]);

it('does not pretend it can switch autofill off', function () {
    // autocomplete="off" on a password field is ignored by every major
    // browser, so putting it there would promise something untrue. A form
    // claiming to suppress the password manager and not doing so is worse
    // than one that says nothing.
    foreach (['login', 'register', 'forgot-password', 'reset-password'] as $view) {
        $source = file_get_contents(resource_path('views/auth/'.$view.'.blade.php'));

        expect($source)->not->toContain('autocomplete="off"');
    }
});
