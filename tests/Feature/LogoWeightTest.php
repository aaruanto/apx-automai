<?php

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The logo in the public navbar is a navbar-sized file.
 *
 * apx-black-logo.png is 1024x1024 and 1.3MB, and it sat in the navbar of all
 * seven public pages, above the fold. It is displayed at 100px wide. On a
 * free-tier host after a cold start, on mobile data, it was comfortably the
 * heaviest thing on the page — about 1.3MB of every page load spent on an
 * image rendered at a tenth of its size.
 *
 * The replacement is 240x240 and 6KB: 2.4x the displayed width, so it stays
 * sharp on a retina screen. Rendered into the navbar's 100x120 box the two
 * differ on 1.45% of pixels, with an average difference of 0.5 out of 765 —
 * anti-aliasing on the edges and nothing else.
 *
 * A new filename, not a replacement of the old one: browsers cache images by
 * name, so overwriting it would have gone on serving the 1.3MB copy to
 * everyone who had already visited. The original stays in the repo as the
 * master; it is simply no longer served.
 *
 * The display attributes are deliberately untouched. This change is about
 * what gets downloaded, not about how the logo looks, and the last test here
 * is what holds that line.
 */

function logoPage(string $url): string
{
    Service::firstOrCreate(
        ['name' => 'Logo Service'],
        ['price' => 1000, 'duration' => 30, 'is_active' => true]
    );

    return test()->get($url)->assertOk()->getContent();
}

dataset('public pages', ['/', '/about', '/service', '/contact', '/booking', '/privacy', '/terms']);

it('serves the navbar-sized logo on every public page', function (string $url) {
    expect(logoPage($url))->toContain('/assets/img/apx-black-logo-240.png');
})->with('public pages');

it('no longer serves the 1.3MB original anywhere', function (string $url) {
    // The old name is a prefix of the new one, so match the closing quote.
    expect(logoPage($url))->not->toContain('/assets/img/apx-black-logo.png"');
})->with('public pages');

it('keeps the served logo small enough for a navbar', function () {
    $served = public_path('assets/img/apx-black-logo-240.png');

    expect(file_exists($served))->toBeTrue();

    // Generous: the file is about 6KB. This is here to catch someone dropping
    // a full-resolution export back in under the same name.
    expect(filesize($served))->toBeLessThan(100 * 1024);
});

it('keeps the original as the master, unserved', function () {
    // Not deleted. It is the highest-resolution copy of the mark there is,
    // and there is no artwork behind it to regenerate from.
    expect(file_exists(public_path('assets/img/apx-black-logo.png')))->toBeTrue();
});

it('does not change how the logo is displayed', function (string $url) {
    // The whole point: this was a weight fix, not a design change. The box
    // stays 100x120, including the stretch that comes with it — altering the
    // scale of the brand mark was explicitly not part of this.
    expect(logoPage($url))->toMatch('/<img src="\/assets\/img\/apx-black-logo-240\.png" alt="apx logo" width="100" height="120">/');
})->with('public pages');
