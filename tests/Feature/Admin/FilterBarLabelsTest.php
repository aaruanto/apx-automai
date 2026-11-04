<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Every filter control says what it filters.
 *
 * The bookings filter bars leaned on their first option to do it — "All
 * Statuses", "All Services", "All Mechanics" — which is exactly the text that
 * disappears the moment a value is chosen. So a filtered list could not tell
 * you what it had been filtered by, which is when you most need to know.
 *
 * The dates were worse than unlabelled. Two identical empty mm/dd/yyyy boxes
 * sat side by side, the from/to distinction carried in a title attribute on
 * the All Bookings page and nowhere at all on the Cancelled one. A tooltip
 * only helps someone who already suspects there is something to hover.
 *
 * The label itself is not new: the admin dashboard's filter bar has always had
 * one. It was defined privately in that view, so the bookings screens could
 * not use it without copying it. It now lives in layouts/admin.blade.php
 * beside .filter-input and .filter-select, and the dashboard uses the shared
 * one — a move, not a second copy.
 */

function labelAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('labels every control on the All Bookings filter bar', function () {
    Service::create(['name' => 'Labelled Service', 'price' => 900, 'duration' => 30]);

    $html = $this->actingAs(labelAdmin())->get('/admin/bookings')->assertOk()->getContent();

    foreach ([
        'searchInput'    => 'Search',
        'filterStatus'   => 'Status',
        'filterService'  => 'Service',
        'filterMechanic' => 'Mechanic',
        'filterDateFrom' => 'Date from',
        'filterDateTo'   => 'Date to',
    ] as $id => $label) {
        expect($html)->toContain('<label class="filter-label" for="'.$id.'">'.$label.'</label>');
    }
});

it('tells the two date boxes apart without a tooltip', function () {
    $html = $this->actingAs(labelAdmin())->get('/admin/bookings')->assertOk()->getContent();

    // The title attribute was the only thing distinguishing them. It is a
    // tooltip, not a label, and it is gone.
    expect($html)->not->toContain('title="Date from"');
    expect($html)->not->toContain('title="Date to"');
});

it('labels the Cancelled filter bar the same way', function () {
    Service::create(['name' => 'Cancelled Service', 'price' => 900, 'duration' => 30]);

    $html = $this->actingAs(labelAdmin())->get('/admin/bookings/cancelled')->assertOk()->getContent();

    expect($html)->toContain('<label class="filter-label" for="searchInput">Search</label>');
    expect($html)->toContain('<label class="filter-label" for="filterService">Service</label>');

    // Named for what they actually compare. The rows carry booking_date, which
    // is the Scheduled For column — not the date the booking was cancelled.
    expect($html)->toContain('<label class="filter-label" for="filterDateFrom">Scheduled from</label>');
    expect($html)->toContain('<label class="filter-label" for="filterDateTo">Scheduled to</label>');
});

it('keeps one definition of the filter label for every admin screen', function () {
    $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
    $dash   = file_get_contents(resource_path('views/dashboard/admin-dashboard.blade.php'));

    // Defined once, in the shared layout.
    expect($layout)->toContain('.filter-label {');

    // And the dashboard uses it rather than its own near-copy.
    expect($dash)->toContain('class="filter-label"');
    expect($dash)->not->toContain('.dash-f-label {');
    expect($dash)->not->toContain('class="dash-f-label"');
});

it('still labels the dashboard filter bar it came from', function () {
    $html = $this->actingAs(labelAdmin())->get('/admin/dashboard')->assertOk()->getContent();

    foreach (['From', 'To', 'Status', 'Service', 'Group by'] as $label) {
        expect($html)->toContain('<label class="filter-label">'.$label.'</label>');
    }
});
