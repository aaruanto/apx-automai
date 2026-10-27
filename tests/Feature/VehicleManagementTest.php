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

it('rejects a plate that is not a plate', function (string $plate) {
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Toyota', 'plate' => $plate, 'year' => 2020,
    ])->assertStatus(422);
})->with(['', 'A', 'ABC!1234', 'ABC/1234', 'ABCDEFGHIJKLMNOPQRST']);

it('accepts the formats the old rule refused', function (string $plate) {
    // AB 1234, ABCD 1234 and ABC 12345 were all rejected by the previous
    // /^[A-Z]{3} \d{3,4}$/ rule, which only described a car plate.
    // Motorcycle, trailer and special plates do not look like that, and a
    // wrong rejection stops a real booking.
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Toyota', 'plate' => $plate, 'year' => 2020,
    ])->assertOk();

    expect($vehicle->refresh()->plate_number)->toBe($plate);
})->with(['AB 1234', 'ABCD 1234', 'ABC 12345', '123 ABC']);

it('refuses a plate of digits alone, which no customer vehicle carries', function () {
    // 12345 was accepted while the rule tested shape only. Requiring a letter
    // and a digit is what stops "ABC" and "AB", and a digits-only plate is the
    // one real format that goes with them: protocol and government plates.
    // A service centre does not see those, and admin can still enter the
    // vehicle directly if it ever does.
    $user    = owner();
    $vehicle = vehicleFor($user, 'AAA 1111');

    $this->actingAs($user)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Toyota', 'plate' => '12345', 'year' => 2020,
    ])->assertStatus(422);

    expect($vehicle->refresh()->plate_number)->toBe('AAA 1111');
});

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

    // The column is a count. The plates themselves now travel in the view
    // modal payload, which is where an admin reads them.
    $response->assertSee('data-plates=', false);
    // Count and label render on separate lines, so assert the label only;
    // the count itself is checked through viewData above.
    $response->assertSee('vehicles', false);
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

it('stores an optional mileage reading and dates it', function () {
    $customer = \App\Models\User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->postJson('/customer/vehicles', [
        'brand'   => 'Toyota',
        'plate'   => 'MIL 1234',
        'year'    => 2021,
        'mileage' => 42000,
    ])->assertOk();

    $vehicle = \App\Models\Vehicle::where('plate_number', 'MIL 1234')->sole();

    expect($vehicle->mileage)->toBe(42000);
    expect($vehicle->mileage_updated_at->toDateString())->toBe(now()->toDateString());
});

it('accepts a vehicle with no mileage at all', function () {
    $customer = \App\Models\User::factory()->create(['role' => 'customer']);

    // Most customers do not know their odometer reading. A blank field must
    // not become 0, which every distance rule would read as a brand new car.
    $this->actingAs($customer)->postJson('/customer/vehicles', [
        'brand'   => 'Toyota',
        'plate'   => 'MIL 5678',
        'year'    => 2021,
        'mileage' => null,
    ])->assertOk();

    $vehicle = \App\Models\Vehicle::where('plate_number', 'MIL 5678')->sole();

    expect($vehicle->mileage)->toBeNull();
    expect($vehicle->mileage_updated_at)->toBeNull();
});

it('does not wipe a stored mileage when the field is left blank on edit', function () {
    $customer = \App\Models\User::factory()->create(['role' => 'customer']);

    $vehicle = \App\Models\Vehicle::create([
        'user_id'      => $customer->id,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2021,
        'plate_number' => 'MIL 9012',
        'vehicle_type' => 'car',
        'mileage'      => 30000,
    ]);

    $this->actingAs($customer)->patchJson("/customer/vehicles/{$vehicle->id}", [
        'brand' => 'Toyota',
        'plate' => 'MIL 9012',
        'year'  => 2021,
        'color' => 'Blue',
    ])->assertOk();

    expect($vehicle->fresh()->mileage)->toBe(30000);
});

it('rejects a negative mileage', function () {
    $customer = \App\Models\User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->postJson('/customer/vehicles', [
        'brand'   => 'Toyota',
        'plate'   => 'MIL 3456',
        'year'    => 2021,
        'mileage' => -5,
    ])->assertStatus(422);
});
