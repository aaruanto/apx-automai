<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function owner(string $name = 'Vehicle Owner'): User
{
    return User::factory()->create(['role' => 'customer', 'name' => $name]);
}

function vehicleFor(User $user, string $plate, array $extra = []): Vehicle
{
    return Vehicle::create(array_merge([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => $plate, 'color' => 'Red',
    ], $extra));
}

// ── Task 9: editing a vehicle ────────────────────────────────────────────

it('lets a customer edit their own vehicle', function () {
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Honda', 'model' => 'Civic', 'plate' => 'BBB 2222',
        'year' => 2023, 'color' => 'Blue', 'vehicle_type' => 'car',
    ])->assertOk()->assertJson(['success' => true]);

    $vehicle->refresh();
    expect($vehicle->make)->toBe('Honda');
    expect($vehicle->plate_number)->toBe('BBB 2222');
    expect($vehicle->year)->toBe(2023);
});

it('uppercases the plate on save', function () {
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Toyota', 'plate' => 'ccc 3333', 'year' => 2020,
    ])->assertOk();

    expect($vehicle->refresh()->plate_number)->toBe('CCC 3333');
});

it('lets a vehicle keep its own plate when other fields change', function () {
    // The uniqueness check must not treat the vehicle as colliding with itself.
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Toyota', 'plate' => 'AAA 1111', 'year' => 2022, 'color' => 'Green',
    ])->assertOk();

    expect($vehicle->refresh()->color)->toBe('Green');
    expect($vehicle->year)->toBe(2022);
});

it('refuses a plate another vehicle already has', function () {
    $user  = owner();
    $mine  = vehicleFor($user, 'AAA 1111');
    vehicleFor($user, 'BBB 2222');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$mine->id}", [
        'brand' => 'Toyota', 'plate' => 'BBB 2222', 'year' => 2020,
    ])->assertStatus(422);

    expect($mine->refresh()->plate_number)->toBe('AAA 1111');
});

it('rejects a malformed plate', function (string $plate) {
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Toyota', 'plate' => $plate, 'year' => 2020,
    ])->assertStatus(422);
})->with(['', '12345', 'AB 1234', 'ABCD 1234', 'ABC 12345']);

it('will not let a customer edit somebody elses vehicle', function () {
    // Scoped by owner, so another customer's id is a 404, not an edit.
    $mine     = owner('Me');
    $theirs   = owner('Them');
    $vehicle  = vehicleFor($theirs, 'ZZZ 9999');

    $this->actingAs($mine)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Stolen', 'plate' => 'XXX 1234', 'year' => 2020,
    ])->assertStatus(404);

    expect($vehicle->refresh()->make)->toBe('Toyota');
    expect($vehicle->plate_number)->toBe('ZZZ 9999');
});

it('will not let a customer delete somebody elses vehicle', function () {
    $mine    = owner('Me');
    $theirs  = owner('Them');
    $vehicle = vehicleFor($theirs, 'ZZZ 9999');

    $this->actingAs($mine)->deleteJson("/customer/vehicles/{$vehicle->id}")->assertStatus(404);

    expect(Vehicle::find($vehicle->id))->not->toBeNull();
});

it('requires a signed-in customer', function () {
    $vehicle = vehicleFor(owner(), 'AAA 1111');

    $this->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Honda', 'plate' => 'BBB 2222', 'year' => 2020,
    ])->assertStatus(401);
});

// ── The primary flag, which the booking form depends on ──────────────────

it('makes the first vehicle primary so booking has a default', function () {
    $user = owner();

    $this->actingAs($user)->postJson('/customer/vehicles', [
        'brand' => 'Toyota', 'plate' => 'AAA 1111', 'year' => 2020,
    ])->assertOk();

    expect(Vehicle::where('user_id', $user->id)->first()->is_primary)->toBeTrue();
});

it('does not make later vehicles primary as well', function () {
    $user = owner();

    foreach (['AAA 1111', 'BBB 2222'] as $plate) {
        $this->actingAs($user)->postJson('/customer/vehicles', [
            'brand' => 'Toyota', 'plate' => $plate, 'year' => 2020,
        ])->assertOk();
    }

    expect(Vehicle::where('user_id', $user->id)->where('is_primary', true)->count())->toBe(1);
});

it('keeps the primary flag when a vehicle is edited', function () {
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111', ['is_primary' => true]);

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Honda', 'plate' => 'BBB 2222', 'year' => 2021,
    ])->assertOk();

    expect($vehicle->refresh()->is_primary)->toBeTrue();
});

// ── Task 8: vehicle count in the admin customer list ─────────────────────

it('shows a vehicle count rather than one plate', function () {
    $user = owner('Many Vehicles');
    Customer::create(['user_id' => $user->id]);
    vehicleFor($user, 'AAA 1111');
    vehicleFor($user, 'BBB 2222');
    vehicleFor($user, 'CCC 3333');

    $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/customers')->assertOk();

    $response->assertSee('Vehicles');
    expect($response->viewData('customers')->first()->vehicle_count)->toBe(3);

    // A single plate must not stand in for all three.
    $response->assertDontSee('AAA 1111');
});

it('still finds a customer by any of their plates', function () {
    $user = owner('Searchable');
    Customer::create(['user_id' => $user->id]);
    vehicleFor($user, 'AAA 1111');
    vehicleFor($user, 'BBB 2222');

    $row = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/customers')->assertOk()
        ->viewData('customers')->first();

    // Search is client-side over data-search, which must carry every plate.
    expect($row->plate_search)->toContain('AAA 1111');
    expect($row->plate_search)->toContain('BBB 2222');
});

it('reads zero vehicles without breaking', function () {
    $user = owner('No Vehicles');
    Customer::create(['user_id' => $user->id]);

    $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/customers')->assertOk();

    expect($response->viewData('customers')->first()->vehicle_count)->toBe(0);
    $response->assertSee('none registered');
});
