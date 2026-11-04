<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * There is no button on the bookings list that does nothing.
 *
 * A "Filter" button sat in the card header beside Export CSV. It had no
 * onclick, no id and no listener anywhere in the file — decoration inherited
 * from the template, sitting next to a control that does something. The
 * filters below apply as you type, so there was never anything for it to do.
 *
 * The tester read the gap between it and Clear as the defect and asked for
 * Clear to be moved up beside it. That would have paired the control that
 * works with the one that does not. Clear belongs with the fields it clears,
 * which is where it already was, so the dead button is what went.
 */

function deadFilterAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('has no decorative Filter button on the bookings list', function () {
    Service::create(['name' => 'Dead Filter Service', 'price' => 800, 'duration' => 30]);

    $html = $this->actingAs(deadFilterAdmin())->get('/admin/bookings')->assertOk()->getContent();

    expect($html)->not->toContain('<button class="btn btn-ghost btn-sm"><i class="fas fa-filter"></i> Filter</button>');

    // The control beside it that always worked is untouched.
    expect($html)->toContain('Export CSV');
});

it('keeps Clear with the fields it clears', function () {
    Service::create(['name' => 'Dead Filter Service 2', 'price' => 800, 'duration' => 30]);

    $html = $this->actingAs(deadFilterAdmin())->get('/admin/bookings')->assertOk()->getContent();

    // Inside the filters bar, not hoisted into the card header.
    expect($html)->toMatch('/filters-bar.*clearFilters\(\).*<\/div>/s');
});

it('still filters as you type, which is why the button had no job', function () {
    Service::create(['name' => 'Dead Filter Service 3', 'price' => 800, 'duration' => 30]);

    $html = $this->actingAs(deadFilterAdmin())->get('/admin/bookings')->assertOk()->getContent();

    expect($html)->toContain("addEventListener('input', applyFilters)");
});
