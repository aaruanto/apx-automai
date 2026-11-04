<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The Register a New Vehicle form does not contradict itself.
 *
 * Make carried a motorcycle icon and Model a car one, on a form whose Vehicle
 * Type defaults to Car — so a motorcycle sat directly beneath a Car selection
 * and read as the wrong type having been chosen. The placeholders agreed with
 * the icon rather than the selection: "Honda" and "Click 125i", a scooter,
 * suggested under Car.
 *
 * The suggestions behind Make and Model are deliberately both vehicle types at
 * once — see x-vehicle-datalists, which says so and explains why — so neither
 * field should be asserting one. They get icons describing what they are: a
 * manufacturer and a model name.
 *
 * Vehicle Type is the field that does state the type, so its icon follows the
 * selection instead of being a car permanently.
 */

function vehicleFormCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function vehicleFormHtml(): string
{
    Service::firstOrCreate(
        ['name' => 'Vehicle Form Service'],
        ['price' => 1000, 'duration' => 30, 'is_active' => true]
    );

    return test()->actingAs(vehicleFormCustomer())
        ->get('/customer/dashboard')->assertOk()->getContent();
}

it('does not put a motorcycle under the Make field', function () {
    $html = vehicleFormHtml();

    expect($html)->not->toContain('<i class="fas fa-motorcycle"></i><input class="m-control" id="avfMake"');
    expect($html)->toContain('<i class="fas fa-industry"></i><input class="m-control" id="avfMake"');
});

it('gives Model an icon that is not a vehicle type either', function () {
    $html = vehicleFormHtml();

    expect($html)->not->toContain('<i class="fas fa-car-side"></i><input class="m-control" id="avfModel"');
    expect($html)->toContain('<i class="fas fa-tag"></i><input class="m-control" id="avfModel"');
});

it('suggests an example that matches the type the form opens on', function () {
    $html = vehicleFormHtml();

    // Honda Click 125i is a scooter, offered while Car was selected.
    expect($html)->not->toContain('placeholder="e.g. Click 125i"');
    expect($html)->toContain('placeholder="e.g. Civic"');
});

it('lets the Vehicle Type icon follow the type that is chosen', function () {
    $html = vehicleFormHtml();

    expect($html)->toContain('id="avfTypeIcon"');
    expect($html)->toContain('onchange="avfSyncTypeIcon()"');
    expect($html)->toContain("icon.className = 'fas ' + (isMoto ? 'fa-motorcycle' : 'fa-car')");
});

it('syncs the icon wherever the type is set, not only on change', function () {
    $html = vehicleFormHtml();

    // Opening the form resets the type to Car and editing a vehicle adopts
    // that vehicle's type. Neither fires a change event, so both would have
    // left the previous icon sitting there.
    expect(substr_count($html, 'avfSyncTypeIcon();'))->toBeGreaterThanOrEqual(2);
});
