<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The scrollbar thumb can be seen against its track.
 *
 * The thumb took its colour from --surface-3. A surface token is by
 * definition a near neighbour of the background it sits on, which is the
 * opposite of what a control you have to find needs, so a 6px bar was being
 * drawn in almost exactly the colour behind it. Measured before the fix:
 *
 *   admin light     #e6e8ec on #f4f5f7   1.12:1
 *   admin dark      #272b35 on #0f1117   1.33:1
 *   customer light  #d1d5db on #f4f5f7   1.35:1   (its own hard-coded value)
 *   customer dark   #222222 on #0A0A0A   1.24:1
 *
 * All four failed WCAG 2.1 SC 1.4.11, which asks 3:1 of a control that is not
 * text. The admin light case is the default an admin sees on a first visit.
 *
 * The styling is the author's, which is what brings the criterion into play
 * at all — an unstyled scrollbar is a user-agent default and out of scope. So
 * the alternative was deleting the custom styling and taking the browser's.
 * Keeping it and giving the thumb its own token holds the look the rest of
 * the panel has.
 *
 * These parse the themes out of the stylesheets and do the arithmetic, rather
 * than asserting the hex values, so the test states the requirement and not
 * the answer: pick any colour that passes and it keeps passing.
 */

/** WCAG relative luminance. */
function srgbLuminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    $c   = [];

    foreach ([0, 2, 4] as $i) {
        $v    = hexdec(substr($hex, $i, 2)) / 255;
        $c[] = $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }

    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function contrastRatio(string $a, string $b): float
{
    $x = srgbLuminance($a);
    $y = srgbLuminance($b);

    return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

/** Pull a custom property out of a named CSS block. */
function tokenIn(string $css, string $block, string $property): ?string
{
    // The block, up to its closing brace.
    if (! preg_match('/'.preg_quote($block, '/').'\s*\{(.*?)\}/s', $css, $m)) {
        return null;
    }

    if (! preg_match('/'.preg_quote($property, '/').'\s*:\s*(#[0-9a-fA-F]{6})/', $m[1], $t)) {
        return null;
    }

    return strtolower($t[1]);
}

dataset('themes', [
    'admin light' => [
        'resources/views/layouts/admin.blade.php', ':root',
    ],
    'admin dark' => [
        'resources/views/layouts/admin.blade.php', 'html.dark-mode',
    ],
    'customer dark' => [
        'public/assets/css/customer-dashboard.css', ':root',
    ],
    'customer light' => [
        'resources/views/dashboard/customer-dashboard.blade.php', 'html.light-mode',
    ],
]);

it('keeps the thumb readable against its own track', function (string $path, string $block) {
    $css = file_get_contents(base_path($path));

    $thumb = tokenIn($css, $block, '--scrollbar-thumb');
    $track = tokenIn($css, $block, '--black');

    expect($thumb)->not->toBeNull("no --scrollbar-thumb in {$block} of {$path}");
    expect($track)->not->toBeNull("no --black in {$block} of {$path}");

    // SC 1.4.11, non-text contrast.
    expect(contrastRatio($thumb, $track))->toBeGreaterThanOrEqual(3.0);
})->with('themes');

it('draws the thumb from its own token rather than a surface colour', function () {
    $shared = file_get_contents(public_path('assets/css/dashboard.css'));
    $admin  = file_get_contents(resource_path('views/layouts/admin.blade.php'));

    expect($shared)->toContain('background: var(--scrollbar-thumb, var(--surface-3))');
    expect($admin)->toContain('background: var(--scrollbar-thumb);');

    // The customer dashboard's hard-coded light thumb is gone with it.
    $customer = file_get_contents(resource_path('views/dashboard/customer-dashboard.blade.php'));
    expect($customer)->not->toContain('html.light-mode ::-webkit-scrollbar-thumb { background: #d1d5db; }');
});

it('uses the same token for the bundle rail a customer has to drag', function () {
    $customer = file_get_contents(resource_path('views/dashboard/customer-dashboard.blade.php'));

    // --border-hover is defined nowhere, so this rail was always falling back
    // to a 35% grey — and it is the one scrollbar on the page that has to be
    // dragged, because the bundle strip does not scroll any other way.
    expect($customer)->not->toContain('var(--border-hover, rgba(128,128,128,0.35))');
    expect($customer)->toContain('.bundle-strip-rail::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb);');
});

it('proves the arithmetic against the values that failed', function () {
    // Guards the helper itself: if these stopped reading as failures the
    // tests above would pass on anything.
    expect(contrastRatio('#e6e8ec', '#f4f5f7'))->toBeLessThan(3.0);
    expect(contrastRatio('#222222', '#0a0a0a'))->toBeLessThan(3.0);
    expect(contrastRatio('#000000', '#ffffff'))->toBeGreaterThan(20.0);
});
