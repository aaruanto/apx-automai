<?php

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The floating quote panel cannot be shut.
 *
 * .apx-sticky-card is 300x430 fixed over the right of every screen wider than
 * 1200px. It used to carry a close button that remembered the dismissal in
 * localStorage; the client wants the panel permanent, so the affordance was
 * removed outright rather than hidden with CSS — a display:none button is one
 * stylesheet edit away from being back, and a hidden one is still focusable.
 *
 * These tests assert the absence of each piece, because that is the part that
 * rots: the markup, the class the rules hung off, and the storage key. Anyone
 * reintroducing a close control has to delete a test that says why not.
 *
 * Visitors who dismissed the panel before still have apx-sticky-card-dismissed
 * in their browser. With nothing left to read it the key is inert and the card
 * comes back for them, which is the intended outcome — there is no migration.
 */

function stickyPage(): string
{
    Service::firstOrCreate(
        ['name' => 'Sticky Service'],
        ['price' => 1200, 'duration' => 45, 'is_active' => true]
    );

    return test()->get('/')->assertOk()->getContent();
}

it('still renders the floating panel', function () {
    $html = stickyPage();

    expect($html)->toContain('id="apxStickyCard"');
    expect($html)->toContain('class="apx-sticky-card"');

    // The panel is only worth keeping because it books. Its own controls and
    // the vehicle list behind them are untouched by the removal.
    expect($html)->toContain('id="apx-sc-vtype"');
    expect($html)->toContain('id="apx-sc-make"');
    expect($html)->toContain('id="apx-sc-model"');
    expect($html)->toContain('apxStickyBook()');
});

it('offers no way to dismiss the panel', function () {
    $html = stickyPage();

    // The button, by id and by the class every rule for it was written against.
    expect($html)->not->toContain('apxStickyClose');
    expect($html)->not->toContain('apx-sc-close');

    // And no close control of any other spelling inside the card.
    $card = substr(
        $html,
        strpos($html, 'id="apxStickyCard"'),
        strpos($html, '</div>', strpos($html, 'apx-sc-trust'))
            - strpos($html, 'id="apxStickyCard"')
    );
    expect($card)->not->toContain('&times;');
    expect($card)->not->toContain('aria-label="Close this panel"');
});

it('keeps no record of a dismissal', function () {
    $html = stickyPage();

    // The storage key, the constant that held it, and either half of the
    // before-first-paint script that used to hide the card.
    expect($html)->not->toContain('apx-sticky-card-dismissed');
    expect($html)->not->toContain('SC_DISMISSED');
    expect($html)->not->toContain('localStorage.setItem');
    expect($html)->not->toContain('localStorage.getItem');
});

it('hides the panel only by width', function () {
    $html = stickyPage();

    // The 300px rail is wrong on a phone, so this rule stays. It is now the
    // only thing that takes the card off the screen.
    expect($html)->toMatch('/@media \(max-width: 1200px\)[^}]*\.apx-sticky-card \{\s*display: none;/s');

    // And .apx-mobile-cta, which replaces it there, has no close control of
    // its own to inherit the problem.
    expect($html)->toContain('apx-mobile-cta');
});
