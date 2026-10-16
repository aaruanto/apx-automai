<?php

use App\Models\Booking;
use App\Models\Bundle;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bundleAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function makeBundle(float $price, array $servicePrices, array $attrs = []): Bundle
{
    $bundle = Bundle::create(array_merge([
        'name'      => 'Bundle '.uniqid(),
        'price'     => $price,
        'is_active' => true,
    ], $attrs));

    foreach ($servicePrices as $p) {
        $bundle->services()->attach(
            Service::create(['name' => 'Svc '.uniqid(), 'price' => $p, 'duration' => 30])->id
        );
    }

    return $bundle->load('services');
}

// ── Price allocation: the part that must be exactly right ────────────────

it('spreads the bundle price across its services so the parts sum exactly', function () {
    $bundle = makeBundle(2000, [1500, 800]);   // list 2300

    $allocated = $bundle->allocatePrice();

    expect(round(array_sum($allocated), 2))->toBe(2000.0);
    // Proportional to list price: 1500/2300 of 2000.
    expect(array_values($allocated)[0])->toBe(1304.35);
    expect(array_values($allocated)[1])->toBe(695.65);
});

it('lands exactly on the bundle price however awkward the division', function (float $price, array $parts) {
    $allocated = makeBundle($price, $parts)->allocatePrice();

    // Rounding remainders go to the last service; the total must never drift.
    expect(round(array_sum($allocated), 2))->toBe($price);
})->with([
    [1000.0, [333, 333, 334]],
    [999.99, [100, 200, 300]],
    [0.03,   [1, 1, 1]],
    [1234.56, [999, 1, 7, 55]],
]);

it('splits evenly when the services have no list price to weight by', function () {
    $allocated = makeBundle(300, [0, 0, 0])->allocatePrice();

    expect(round(array_sum($allocated), 2))->toBe(300.0);
    expect(array_values($allocated))->each->toBe(100.0);
});

it('reports the saving against buying separately', function () {
    $bundle = makeBundle(2000, [1500, 800]);

    expect($bundle->listPrice())->toBe(2300.0);
    expect($bundle->savings())->toBe(300.0);
    expect($bundle->savingsPercent())->toBe(13);
});

// ── Availability ─────────────────────────────────────────────────────────

it('is not bookable when switched off', function () {
    expect(makeBundle(100, [60, 60], ['is_active' => false])->isBookable())->toBeFalse();
});

it('respects its run dates', function (?string $starts, ?string $ends, bool $expected) {
    $bundle = makeBundle(100, [60, 60], [
        'starts_at' => $starts ? today()->addDays((int) $starts)->toDateString() : null,
        'ends_at'   => $ends   ? today()->addDays((int) $ends)->toDateString()   : null,
    ]);

    expect($bundle->isBookable())->toBe($expected);
})->with([
    'open ended'      => [null, null, true],
    'starts tomorrow' => ['1', null, false],
    'ended yesterday' => [null, '-1', false],
    'running now'     => ['-1', '1', true],
]);

it('only offers bundles suited to the vehicle', function () {
    $carOnly = makeBundle(100, [60, 60], ['vehicle_type' => 'car']);

    expect($carOnly->suitsVehicleType('car'))->toBeTrue();
    expect($carOnly->suitsVehicleType('motorcycle'))->toBeFalse();
    expect(makeBundle(100, [60, 60])->suitsVehicleType('motorcycle'))->toBeTrue();
});

// ── Admin management ─────────────────────────────────────────────────────

it('creates a bundle from the admin form', function () {
    $a = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 30]);
    $b = Service::create(['name' => 'Tires', 'price' => 800, 'duration' => 30]);

    $this->actingAs(bundleAdmin())->post('/admin/bundles', [
        'name' => 'Basic Care', 'price' => 2000, 'service_ids' => [$a->id, $b->id],
    ])->assertRedirect();

    $bundle = Bundle::sole();
    expect($bundle->services)->toHaveCount(2);
    expect($bundle->is_active)->toBeTrue();
});

it('refuses a bundle that saves the customer nothing', function (float $price) {
    $a = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 30]);
    $b = Service::create(['name' => 'Tires', 'price' => 800, 'duration' => 30]);

    $this->actingAs(bundleAdmin())->post('/admin/bundles', [
        'name' => 'Pointless', 'price' => $price, 'service_ids' => [$a->id, $b->id],
    ])->assertSessionHasErrors('price');

    expect(Bundle::count())->toBe(0);
})->with([2300.0, 2500.0]);

it('refuses a bundle of fewer than two services', function () {
    $a = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 30]);

    $this->actingAs(bundleAdmin())->post('/admin/bundles', [
        'name' => 'Not A Bundle', 'price' => 1000, 'service_ids' => [$a->id],
    ])->assertSessionHasErrors('service_ids');
});

it('refuses an end date before the start', function () {
    $a = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 30]);
    $b = Service::create(['name' => 'Tires', 'price' => 800, 'duration' => 30]);

    $this->actingAs(bundleAdmin())->post('/admin/bundles', [
        'name' => 'Backwards', 'price' => 2000, 'service_ids' => [$a->id, $b->id],
        'starts_at' => today()->addDays(5)->toDateString(),
        'ends_at'   => today()->addDay()->toDateString(),
    ])->assertSessionHasErrors('ends_at');
});

it('switches a bundle off instead of deleting it', function () {
    $bundle = makeBundle(2000, [1500, 800]);

    $this->actingAs(bundleAdmin())->patch("/admin/bundles/{$bundle->id}/toggle")->assertRedirect();

    expect(Bundle::find($bundle->id))->not->toBeNull();
    expect($bundle->fresh()->is_active)->toBeFalse();
});

it('exposes no delete route for a bundle', function () {
    $bundle = makeBundle(2000, [1500, 800]);

    $this->actingAs(bundleAdmin())->delete("/admin/bundles/{$bundle->id}")->assertStatus(405);
});

it('keeps the bundle screen to the admin', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get('/admin/bundles')->assertStatus(403);
});

// ── Booking a bundle ─────────────────────────────────────────────────────

it('books a bundle at the bundle price, not the sum of its services', function () {
    $bundle   = makeBundle(2000, [1500, 800]);
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'BDL 1111', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'bundle_id'    => $bundle->id,
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->bundle_id)->toBe($bundle->id);
    expect((float) $booking->bundle_price)->toBe(2000.0);
    expect($booking->services)->toHaveCount(2);
    // The existing total, which every revenue query already reads.
    expect($booking->total_price)->toBe(2000.0);
});

it('keeps the price a bundle booking was sold at when the bundle is re-priced', function () {
    $bundle   = makeBundle(2000, [1500, 800]);
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'BDL 2222', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'bundle_id' => $bundle->id, 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $bundle->update(['price' => 2500]);

    expect(Booking::sole()->total_price)->toBe(2000.0);
});

it('refuses a bundle that is switched off', function () {
    $bundle   = makeBundle(2000, [1500, 800], ['is_active' => false]);
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'BDL 3333', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'bundle_id' => $bundle->id, 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(Booking::count())->toBe(0);
});

it('refuses a bundle meant for a different vehicle type', function () {
    $bundle   = makeBundle(2000, [1500, 800], ['vehicle_type' => 'motorcycle']);
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'BDL 4444', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'bundle_id' => $bundle->id, 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(Booking::count())->toBe(0);
});

it('ignores services the browser sends alongside a bundle', function () {
    // The bundle decides what is on the booking. Trusting the client here
    // would let anyone add a service to a bundle at the bundle's price.
    $bundle   = makeBundle(2000, [1500, 800]);
    $sneaky   = Service::create(['name' => 'Engine Rebuild', 'price' => 50000, 'duration' => 30]);
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'BDL 5555', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'bundle_id' => $bundle->id, 'service_ids' => [$sneaky->id],
        'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->services->pluck('id'))->not->toContain($sneaky->id);
    expect($booking->total_price)->toBe(2000.0);
});

// ── Where customers see bundles ──────────────────────────────────────────

it('offers bookable bundles on the landing page', function () {
    makeBundle(2000, [1500, 800], ['name' => 'Visible Bundle']);
    makeBundle(2000, [1500, 800], ['name' => 'Hidden Bundle', 'is_active' => false]);

    $this->get('/')->assertOk()
        ->assertSee('Visible Bundle')
        ->assertDontSee('Hidden Bundle');
});

it('shows real bundles in the customer rewards panel, not invented promos', function () {
    makeBundle(2000, [1500, 800], ['name' => 'Real Bundle']);
    $customer = User::factory()->create(['role' => 'customer']);

    $page = $this->actingAs($customer)->get('/customer/dashboard')->assertOk();

    $page->assertSee('Real Bundle');
    // This panel used to list four invented promos with codes nothing would
    // honour and expiry dates that had already passed.
    $page->assertDontSee('OIL100');
    $page->assertDontSee('BUNDLE150');
    $page->assertDontSee('WELCOME10');
});

it('lets a guest book a bundle', function () {
    $bundle = makeBundle(2000, [1500, 800]);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'walkin@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'GBD 1234', 'vehicle_type' => 'car',
        'bundle_id' => $bundle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->bundle_id)->toBe($bundle->id);
    expect($booking->total_price)->toBe(2000.0);
});

it('ignores services a guest sends alongside a bundle', function () {
    $bundle = makeBundle(2000, [1500, 800]);
    $sneaky = Service::create(['name' => 'Engine Rebuild', 'price' => 50000, 'duration' => 30]);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'sneaky@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'GBD 5678', 'vehicle_type' => 'car',
        'bundle_id' => $bundle->id, 'service_ids' => [$sneaky->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    expect(Booking::sole()->total_price)->toBe(2000.0);
});
