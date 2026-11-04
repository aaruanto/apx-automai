<?php

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The floating quote panel can be shut.
 *
 * .apx-sticky-card is 300x430 fixed over the right of every screen wider than
 * 1200px, and there was no way to close it. It sits on top of the page it is
 * advertising — the photographs of the shop among it — and a visitor who did
 * not want it could only scroll underneath it forever.
 *
 * Dismissing it is remembered, because a close button that forgets by the
 * next page is not a close button, and it is applied before first paint so a
 * returning visitor does not watch the panel appear and then vanish on every
 * page they open.
 *
 * The icon is a literal multiplication sign rather than fa-xmark. This page
 * loads Font Awesome 5 and fa-xmark is a 6 name; the admin layout loads 6,
 * which is what makes it look available. The first attempt used it and
 * rendered a correctly sized, perfectly invisible button.
 */

function stickyPage(): string
{
    Service::firstOrCreate(
        ['name' => 'Sticky Service'],
        ['price' => 1200, 'duration' => 45, 'is_active' => true]
    );

    return test()->get('/')->assertOk()->getContent();
}

it('gives the floating panel a close control', function () {
    $html = stickyPage();

    expect($html)->toContain('id="apxStickyClose"');
    expect($html)->toContain('aria-label="Close this panel"');

    // Addressable, so the handler and the before-paint script can find it.
    expect($html)->toContain('id="apxStickyCard"');
});

it('does not draw the close control with an icon this page cannot render', function () {
    $html = stickyPage();

    // Font Awesome 5 is what the public pages load.
    expect($html)->toContain('font-awesome/5.10.0');

    // So the button is a character, not a 6-only glyph name.
    expect($html)->toMatch('/id="apxStickyClose"[^>]*>&times;<\/button>/');
});

it('remembers the panel was dismissed', function () {
    $html = stickyPage();

    expect($html)->toContain("localStorage.setItem(SC_DISMISSED, '1')");
});

it('hides a dismissed panel before the page paints', function () {
    $html = stickyPage();

    // The check has to run where the element already exists and before paint.
    // Leaving it to the click handler's script would flash the panel up and
    // take it away again on every visit.
    expect($html)->toContain("localStorage.getItem('apx-sticky-card-dismissed') === '1'");

    $beforePaint = strpos($html, "localStorage.getItem('apx-sticky-card-dismissed')");
    $cardMarkup  = strpos($html, 'id="apxStickyCard"');

    expect($cardMarkup)->toBeLessThan($beforePaint);
});

it('survives a browser that refuses local storage', function () {
    $html = stickyPage();

    // Private windows throw on access. A panel that will not close is a worse
    // outcome than one that forgets, so every access is wrapped.
    expect($html)->toMatch('/try \{[^}]*localStorage\.setItem\(SC_DISMISSED/s');
    expect($html)->toMatch('/try \{\s*if \(localStorage\.getItem\(\'apx-sticky-card-dismissed\'\)/s');
});
