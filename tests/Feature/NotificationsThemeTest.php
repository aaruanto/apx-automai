<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * /notifications follows the app's theme toggle, not the operating system.
 *
 * It is one standalone page served to admins and customers alike, so it does
 * not extend the admin layout — and it had been left out of the convention
 * every other page follows. Its only dark rule was a prefers-color-scheme
 * block guarded by :root:not([data-theme="light"]), and nothing in this
 * application has ever set data-theme. The guard was dead, so the media query
 * won outright and the page followed the laptop: an admin working in light
 * mode on a dark machine clicked the bell and got a black page.
 *
 * Checked in a browser too, both roles, both themes, with the OS forced to
 * dark. These guard the mechanism so it cannot quietly revert.
 */

function ntUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

it('reads the theme the rest of the app stores', function () {
    $html = $this->actingAs(ntUser('admin'))->get('/notifications')->assertOk()->getContent();

    // Same key and same class as layouts/admin.blade.php, or the toggle on the
    // page they came from does not carry over.
    expect($html)->toContain("localStorage.getItem('apx-theme')");
    expect($html)->toContain("classList.add('dark-mode')");
    expect($html)->toContain('html.dark-mode');
});

it('does not theme itself off the operating system', function () {
    $html = $this->actingAs(ntUser('customer'))->get('/notifications')->assertOk()->getContent();

    expect($html)->not->toContain('prefers-color-scheme');
    expect($html)->not->toContain('data-theme');
});

it('survives a browser that refuses localStorage', function () {
    $html = $this->actingAs(ntUser('admin'))->get('/notifications')->assertOk()->getContent();

    // It throws outright in a private window, and an uncaught throw here runs
    // before the page has painted anything.
    expect($html)->toMatch('/try\s*\{[^}]*apx-theme[^}]*\}\s*catch/');
});

it('is given the shops own default theme to fall back on', function () {
    // The composer is scoped to a list of views and this one is standalone, so
    // being absent from that list meant $defaultTheme was undefined here and
    // the page fell back to light whatever the shop had chosen.
    $html = $this->actingAs(ntUser('admin'))->get('/notifications')->assertOk()->getContent();

    expect($html)->toMatch("/var theme = '(light|dark)'/");
});

it('gives the bell panel its own foreground colour', function () {
    // The component sets its own background, so it has to set its own text
    // colour: it read correctly only because both host pages happen to put
    // color on body, which the next page to embed it need not do.
    $html = $this->actingAs(ntUser('admin'))->get('/admin/dashboard')->assertOk()->getContent();

    expect($html)->toContain('color:var(--text,#1a1d23)');
});
