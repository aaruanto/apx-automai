<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Pages that stay inside the width of the screen.
 *
 * Three separate causes, found by measuring every public and signed-in page
 * at ten widths rather than by looking at the ones that felt wrong.
 *
 *  1. service.blade.php never closed its navbar-brand anchor. An unclosed
 *     anchor is a formatting element, so the parser reopens it inside the
 *     block that follows — leaving an empty copy of .navbar-brand, padding
 *     and all, as the first child of .navbar-nav. 96px of px-lg-5 nobody
 *     asked for, which pushed the Login button off the screen and made the
 *     document 128px too wide at 1024 and 159px at 992. The page looked
 *     fine at 1440 and fine on a phone.
 *
 *  2. The customer dashboard had no min-width:0 on its flex and grid items,
 *     so it kept its intrinsic width instead of letting its columns narrow.
 *     A band rather than a cliff — 8px at 1100, 84px at 1024, 108px at 1000,
 *     then correct again at 992 where the breakpoint folds the grids. The
 *     admin layout has carried this rule, and the reasoning beside it, for
 *     some time; this side never got it.
 *
 *  3. The landing page's step badges anchored to the right edge of their
 *     column rather than to the circle they number, and the photo caption
 *     hung 24px off a photo that already reached both edges of a phone.
 *     Together, the 12px CLAUDE.md records at phone width.
 *
 * Widths cannot be asserted from here — there is no browser — so these pin
 * the structural causes. The measurements above were taken with headless
 * Chrome at 1440, 1200, 1100, 1024, 992, 900, 768, 430, 390 and 360.
 */

it('closes the navbar brand before the toggler on every public page', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    // Everything between the brand anchor and the toggler that follows it.
    // On /service this span had no closing tag, and the parser's repair is
    // what widened the page.
    preg_match('/<a[^>]*navbar-brand.*?<button[^>]*navbar-toggler/s', $html, $m);

    expect($m)->not->toBeEmpty("no navbar found on {$url}");
    expect($m[0])->toContain('</a>');
})->with(['/', '/about', '/service', '/contact', '/booking']);

it('lets the customer dashboard narrow instead of widening the page', function () {
    $css = file_get_contents(public_path('assets/css/customer-dashboard.css'));

    // Unconditional, not inside a breakpoint — a rule that only applies below
    // some width is how the 993-1100px band came to be missed in the first
    // place.
    expect($css)->toMatch('/\.main-content,\s*main,\s*\.card\s*\{\s*min-width:\s*0;\s*max-width:\s*100%;/');
    expect($css)->toMatch('/\.cards-grid > \*,\s*\.content-grid > \*\s*\{\s*min-width:\s*0;/');
});

it('keeps the same rule on the admin side it came from', function () {
    $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

    expect($layout)->toMatch('/\.main-content,\s*main,\s*\.card,\s*\.table-wrap\s*\{\s*min-width:\s*0;/');
});

it('pins the landing page step badges to their circle', function () {
    Service::create(['name' => 'Overflow Service', 'price' => 900, 'duration' => 30]);

    $html = $this->get('/')->assertOk()->getContent();

    // end-0 anchored the badge to the column, not the 72px circle it numbers.
    expect($html)->not->toContain('position-absolute top-0 end-0 me-n2');
    expect($html)->toContain('left:50%; transform:translate(20px,-20%);');
});

it('keeps the About row inside a full-bleed container', function () {
    Service::create(['name' => 'Overflow Service 3', 'price' => 900, 'duration' => 30]);

    $html = $this->get('/')->assertOk()->getContent();

    // g-5 is -24px of row margin against 12px of container padding. Above
    // 576px the container's max-width absorbs the difference; below it there
    // is no slack and 12px hangs off each edge. This was the real source of
    // the 12px CLAUDE.md attributes to the template's badges.
    expect($html)->toContain('<div class="row g-4 g-sm-5">');
});

it('gives the landing navbar room before it expands', function () {
    Service::create(['name' => 'Overflow Service 4', 'price' => 900, 'duration' => 30]);

    $html = $this->get('/')->assertOk()->getContent();

    // Six links, a padded brand and a Login button need more than 992px.
    expect($html)->toContain('navbar navbar-expand-xl');
    expect($html)->not->toContain('navbar navbar-expand-lg bg-white');
});

it('keeps the photo caption overhang only where there is room for it', function () {
    Service::create(['name' => 'Overflow Service 2', 'price' => 900, 'duration' => 30]);

    $html = $this->get('/')->assertOk()->getContent();

    // The overhang is the intended look on a desktop and 24px off the side of
    // the screen on a phone, where the photo already reaches both edges.
    expect($html)->not->toContain('mt-n4 me-n4 py-4 px-5');
    expect($html)->toContain('mt-n4 me-0 me-lg-n4 py-4 px-5');
});
