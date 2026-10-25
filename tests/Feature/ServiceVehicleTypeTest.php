<?php

/**
 * Which services a vehicle may be booked for.
 *
 * bundles and maintenance_rules have carried a vehicle_type since they were
 * created; services never did, so a guest booking a motorcycle was offered an
 * Aircon Performance Check and the shop found out when the bike arrived.
 *
 * The UI now hides what does not apply, but that is presentation. These pin
 * the part that matters: the server refuses it whatever the browser posts.
 */

use App\Models\Bundle;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function vtService(?string $vehicleType, float $price = 1500): Service
{
    return Service::create([
        'name' => 'VT Svc '.uniqid(), 'price' => $price, 'duration' => 45,
        'vehicle_type' => $vehicleType,
    ]);
}

function vtCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function vtVehicle(User $owner, string $type, string $plate): Vehicle
{
    return Vehicle::create([
        'user_id' => $owner->id, 'make' => 'Honda', 'model' => 'Click', 'year' => 2022,
        'plate_number' => $plate, 'color' => 'Red', 'vehicle_type' => $type,
    ]);
}

// ── The shared rule ──────────────────────────────────────────────────────

it('treats null as applying to every vehicle', function () {
    $any = vtService(null);

    expect($any->suitsVehicleType('car'))->toBeTrue();
    expect($any->suitsVehicleType('motorcycle'))->toBeTrue();
});

it('uses one implementation for services, bundles and rules', function () {
    // These had drifted into two slightly different copies before services
    // needed a third. A single trait is what stops them disagreeing.
    foreach ([Service::class, Bundle::class, \App\Models\MaintenanceRule::class] as $model) {
        expect(in_array(
            \App\Models\Concerns\AppliesToVehicleType::class,
            class_uses_recursive($model),
            true
        ))->toBeTrue("{$model} should use the shared trait");
    }
});

it('reads an empty string as every vehicle, not as a type nothing matches', function () {
    $svc = vtService(null);
    $svc->forceFill(['vehicle_type' => ''])->save();

    expect($svc->fresh()->suitsVehicleType('motorcycle'))->toBeTrue();
});

// ── Server-side enforcement: the signed-in path ─────────────────────────

it('refuses a car-only service booked against a motorcycle', function () {
    $carOnly  = vtService('car');
    $customer = vtCustomer();
    $bike     = vtVehicle($customer, 'motorcycle', 'VTT 1111');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [$carOnly->id], 'vehicle_id' => $bike->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422)->assertJsonFragment(['success' => false]);

    expect(\App\Models\Booking::count())->toBe(0);
});

it('allows the same service against a car', function () {
    $carOnly  = vtService('car');
    $customer = vtCustomer();
    $car      = vtVehicle($customer, 'car', 'VTT 2222');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [$carOnly->id], 'vehicle_id' => $car->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();
});

it('checks against the stored vehicle, not anything the request claims', function () {
    // The browser decides what to post. It does not decide what the vehicle is.
    $carOnly  = vtService('car');
    $customer = vtCustomer();
    $bike     = vtVehicle($customer, 'motorcycle', 'VTT 3333');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [$carOnly->id], 'vehicle_id' => $bike->id,
        'vehicle_type' => 'car',   // a lie
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(\App\Models\Booking::count())->toBe(0);
});

// ── Server-side enforcement: the guest path ─────────────────────────────

it('refuses a car-only service on a guest motorcycle booking', function () {
    $carOnly = vtService('car');

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'vt-guest@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Honda', 'vehicle_model' => 'Click', 'vehicle_year' => 2022,
        'vehicle_plate' => 'VTG 1111', 'vehicle_type' => 'motorcycle',
        'service_ids' => [$carOnly->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(\App\Models\Booking::count())->toBe(0);
});

it('names the offending service so the customer knows what to drop', function () {
    $carOnly = Service::create([
        'name' => 'Aircon Performance Check', 'price' => 600, 'duration' => 30,
        'vehicle_type' => 'car',
    ]);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'vt-guest2@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Honda', 'vehicle_model' => 'Click', 'vehicle_year' => 2022,
        'vehicle_plate' => 'VTG 2222', 'vehicle_type' => 'motorcycle',
        'service_ids' => [$carOnly->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422)
      ->assertJsonFragment(['message' => 'Aircon Performance Check is not offered for motorcycles. Please remove it and try again.']);
});

it('lets a guest book an unrestricted service on anything', function () {
    $any = vtService(null);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'vt-guest3@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Honda', 'vehicle_model' => 'Click', 'vehicle_year' => 2022,
        'vehicle_plate' => 'VTG 3333', 'vehicle_type' => 'motorcycle',
        'service_ids' => [$any->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();
});

it('does not second-guess a bundle, which carries its own restriction', function () {
    // The bundle gate ran a few lines earlier. Its contents are the shop's
    // choice, not the customer's, so they are not re-judged service by service.
    $bundle = Bundle::create(['name' => 'Bike Care', 'price' => 2000, 'is_active' => true]);
    $bundle->services()->attach([vtService(null, 1500)->id, vtService('car', 800)->id]);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'vt-guest4@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Honda', 'vehicle_model' => 'Click', 'vehicle_year' => 2022,
        'vehicle_plate' => 'VTG 4444', 'vehicle_type' => 'motorcycle',
        'bundle_id' => $bundle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();
});

// ── Admin ────────────────────────────────────────────────────────────────

it('lets the shop set and clear the restriction without a developer', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/settings/services', [
        'name' => 'Aircon Check', 'price' => 600, 'duration' => 30,
        'category' => 'Inspection', 'vehicle_type' => 'car',
    ])->assertRedirect();

    $svc = Service::where('name', 'Aircon Check')->sole();
    expect($svc->vehicle_type)->toBe('car');

    // Blank means every vehicle, and must land as null — an empty string
    // would be a vehicle type no vehicle has.
    $this->actingAs($admin)->put('/admin/settings/services/'.$svc->id, [
        'name' => 'Aircon Check', 'price' => 600, 'duration' => 30,
        'category' => 'Inspection', 'vehicle_type' => '',
    ])->assertRedirect();

    expect($svc->fresh()->vehicle_type)->toBeNull();
});

it('refuses a vehicle type that is not car or motorcycle', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/settings/services', [
        'name' => 'Boat Service', 'price' => 600, 'duration' => 30,
        'vehicle_type' => 'boat',
    ])->assertSessionHasErrors('vehicle_type');
});

it('warns when a bundle is narrower than something it contains', function () {
    // Nobody could book such a bundle: the bundle gate lets a bike through
    // and the service gate turns it away.
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/bundles', [
        'name' => 'Impossible Bundle', 'price' => 1000,
        'vehicle_type' => 'motorcycle',
        'service_ids' => [vtService(null, 900)->id, vtService('car', 800)->id],
        'is_active' => true,
    ])->assertRedirect();

    expect(session('warning'))->toContain('not. Nobody will be able to book it');
});

it('says nothing when a bundle and its services agree', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/bundles', [
        'name' => 'Fine Bundle', 'price' => 1000,
        'vehicle_type' => 'motorcycle',
        'service_ids' => [vtService(null, 900)->id, vtService('motorcycle', 800)->id],
        'is_active' => true,
    ])->assertRedirect();

    expect(session('warning'))->toBeNull();
});

// ── The policy helper itself ─────────────────────────────────────────────

it('refuses nothing when no vehicle has been identified yet', function () {
    $services = collect([vtService('car'), vtService('motorcycle')]);

    expect(BookingPolicy::servicesNotFor($services, null))->toBeEmpty();
    expect(BookingPolicy::servicesNotFor($services, ''))->toBeEmpty();
});
