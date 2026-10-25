<?php

/**
 * The shop-wide default theme.
 *
 * default_theme used to be a write-only setting: validated, stored, read back
 * into its own form, and consumed by nothing. An admin could set Dark, be told
 * "Settings saved", reload, see the dropdown still reading Dark — and the page
 * was still light, because every view used
 * localStorage.getItem('apx-theme') || 'light'.
 *
 * It is a FALLBACK. A browser that has used the toggle has a stored value and
 * keeps it; the setting only decides what someone sees before they have ever
 * chosen. That distinction is what these pin.
 */

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function themeAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

/** The pages that decide a theme before first paint. */
dataset('themed pages', [
    'login'              => ['/login', null],
    'register'           => ['/register', null],
    'customer dashboard' => ['/customer/dashboard', 'customer'],
    'admin dashboard'    => ['/admin/dashboard', 'admin'],
]);

it('puts the configured default into the page', function (string $url, ?string $role) {
    Setting::set('default_theme', 'dark');

    $request = $role
        ? test()->actingAs(User::factory()->create(['role' => $role]))
        : test();

    $html = $request->get($url)->assertOk()->getContent();

    // The fallback the browser uses when nothing is stored.
    expect($html)->toContain("var theme = 'dark'");
})->with('themed pages');

it('falls back to light when the setting says light', function (string $url, ?string $role) {
    Setting::set('default_theme', 'light');

    $request = $role
        ? test()->actingAs(User::factory()->create(['role' => $role]))
        : test();

    expect($request->get($url)->assertOk()->getContent())->toContain("var theme = 'light'");
})->with('themed pages');

it('never lets the default override a stored choice', function (string $url, ?string $role) {
    // The whole point. Whatever the shop sets, the script must read
    // localStorage first and only use the default when there is nothing there.
    Setting::set('default_theme', 'dark');

    $request = $role
        ? test()->actingAs(User::factory()->create(['role' => $role]))
        : test();

    $html = $request->get($url)->assertOk()->getContent();

    expect($html)->toContain("localStorage.getItem('apx-theme') || theme");
})->with('themed pages');

it('paints the admin theme before the page is drawn', function () {
    // The admin layout had no pre-paint script at all; the theme was applied
    // by the toggle's own script at the bottom of the body. Invisible while
    // light was the only default, a white flash on every navigation once the
    // shop can choose dark.
    Setting::set('default_theme', 'dark');

    $html = $this->actingAs(themeAdmin())->get('/admin/dashboard')->assertOk()->getContent();

    $head   = strpos($html, '</head>');
    $script = strpos($html, "classList.add('dark-mode')");

    expect($script)->not->toBeFalse();
    expect($script)->toBeLessThan($head);
});

it('is still saved and read back by the settings form', function () {
    $admin = themeAdmin();

    $this->actingAs($admin)->put('/admin/settings/system', [
        'default_theme' => 'dark',
    ])->assertRedirect();

    expect(Setting::get('default_theme'))->toBe('dark');
    $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('value="dark"', false);
});

it('refuses a theme that is not light or dark', function () {
    $this->actingAs(themeAdmin())->put('/admin/settings/system', [
        'default_theme' => 'chartreuse',
    ])->assertSessionHasErrors('default_theme');
});

it('does not say light is the default when the shop has chosen dark', function () {
    // The hint beside the control used to read "Light mode is the default for
    // all admin sessions", contradicting the dropdown next to it.
    Setting::set('default_theme', 'dark');

    $html = $this->actingAs(themeAdmin())->get('/admin/settings')->assertOk()->getContent();

    expect($html)->not->toContain('Light mode is the default for all admin sessions');
});
