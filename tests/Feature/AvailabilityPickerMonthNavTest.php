<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The month arrows do nothing while no service is chosen.
 *
 * The picker draws no days until a service is picked, because how long the
 * work takes decides which slots are open. The arrows, though, kept working
 * against that empty grid: _changeMonth() advanced this.month, _monthChrome()
 * returned false before any fetch, and the calendar stayed blank — so it read
 * as a calendar that would not load, while the month moved underneath anyway.
 * The first month it finally drew, once a service was ticked, was wherever the
 * paging had left it. Two clicks while deciding and the booking opened on
 * December with no indication the current month had been skipped.
 *
 * The arrows now go dead in that state and _changeMonth() refuses as well, so
 * the keyboard and any direct caller are covered too. nextBtn has to be put
 * back on the chosen path: nothing else in the file ever disables it, so
 * without that it would stay dead for the rest of the session.
 *
 * ── What these tests are, honestly ──────────────────────────────────────
 * The guard is client-side and this project has no JavaScript test runner, so
 * the first three assert on the shipped asset rather than on behaviour. They
 * are change-detectors: they will catch the guard being deleted or reworded
 * away, not a subtle logic error inside it. The behaviour itself was checked
 * in a browser against all of: load with nothing chosen, two clicks of next,
 * a direct _changeMonth(1), then ticking a service — the calendar opened on
 * the current month, and paging forward and back still worked afterwards.
 *
 * The same shape as DefaultThemeTest, which pins a before-first-paint script
 * by its rendered content for the same reason.
 *
 * The last test is a real one: it pins that all three booking forms still load
 * this shared file, so the guard actually reaches them. That is the part worth
 * protecting — the bug exists once, in one component, and must not be fixed
 * per page.
 */

function pickerSource(): string
{
    return file_get_contents(public_path('assets/js/availability-picker.js'));
}

it('refuses to change month while nothing is chosen', function () {
    $js = pickerSource();

    // The guard sits at the top of _changeMonth, before this.month is touched.
    expect($js)->toMatch('/_changeMonth = function \(delta\) \{.*?if \(!this\._serviceIds\(\)\.length\) return;.*?this\.month \+= delta;/s');
});

it('switches both month arrows off when no service is chosen', function () {
    $js = pickerSource();

    // Inside the !_serviceIds().length branch of _monthChrome.
    expect($js)->toMatch('/if \(!this\._serviceIds\(\)\.length\) \{.*?this\.els\.prevBtn\.disabled = true;.*?this\.els\.nextBtn\.disabled = true;/s');
});

it('switches the next arrow back on once a service is chosen', function () {
    $js = pickerSource();

    // Without this the arrow stays dead for the rest of the session.
    expect($js)->toContain('this.els.nextBtn.disabled = false;');
});

it('serves the one shared picker to every booking form', function () {
    Service::create(['name' => 'Picker Service', 'price' => 1200, 'duration' => 45, 'is_active' => true]);

    // With a version on it. The file is served straight from public/ under a
    // name that never changes, so a browser that has been to the site before
    // keeps serving itself the copy it already has — and this fix lives
    // entirely inside that file. Every stylesheet here is already versioned
    // this way; the picker script was the one that was not, so changing it
    // would have reached nobody who had already visited.
    $asset = 'assets/js/availability-picker.js?v=';

    // Landing page (guest booking modal).
    expect($this->get('/')->assertOk()->getContent())->toContain($asset);

    // Admin new booking.
    $admin = User::factory()->create(['role' => 'admin']);
    expect($this->actingAs($admin)->get('/admin/bookings/create')->assertOk()->getContent())
        ->toContain($asset);
});

it('serves the same shared picker to the customer dashboard', function () {
    Service::create(['name' => 'Picker Service 2', 'price' => 1200, 'duration' => 45, 'is_active' => true]);

    // Its own test: actingAs persists for the whole test, so this cannot share
    // one with the admin above.
    $customer = User::factory()->create(['role' => 'customer']);

    expect($this->actingAs($customer)->get('/customer/dashboard')->assertOk()->getContent())
        ->toContain('assets/js/availability-picker.js?v=');
});
