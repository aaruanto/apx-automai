<?php

/**
 * Which services a vehicle's transmission may be booked for, and what the
 * booking records about it.
 *
 * The same shape and the same convention as vehicle_type, one step along:
 * NULL on the service means "applies to both", NULL on the vehicle means "not
 * recorded", and neither narrows anything. The two absences mean different
 * things in English and the same thing to the comparison, which is the part
 * worth pinning — a vehicle registered before the shop started asking must
 * keep being bookable for everything.
 *
 * The browser hides what does not apply, but that is presentation. These
 * cover the part that decides: the server refuses it whatever is posted, and
 * the booking keeps its own copy of what the vehicle was.
 */

use App\Models\Booking;
use App\Models\Bundle;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function trService(?string $transmission, ?string $vehicleType = null, float $price = 1500): Service
{
    return Service::create([
        'name' => 'TR Svc '.uniqid(), 'price' => $price, 'duration' => 45,
        'vehicle_type' => $vehicleType, 'transmission' => $transmission,
    ]);
}

function trCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function trVehicle(User $owner, ?string $transmission, string $plate, string $type = 'car'): Vehicle
{
    return Vehicle::create([
        'user_id' => $owner->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => $plate, 'color' => 'Red',
        'vehicle_type' => $type, 'transmission' => $transmission,
    ]);
}

// ── The shared rule ──────────────────────────────────────────────────────

it('treats null on the service as applying to both transmissions', function () {
    $any = trService(null);

    expect($any->suitsTransmission('manual'))->toBeTrue();
    expect($any->suitsTransmission('automatic'))->toBeTrue();
});

it('treats null on the vehicle as not narrowing anything', function () {
    // The other direction, and the one that matters for the data already in
    // the database: every vehicle registered before the column existed holds
    // NULL. A service restricted to automatics must still be offered to them,
    // or this migration would have taken work away from existing customers.
    $autoOnly = trService('automatic');

    expect($autoOnly->suitsTransmission(null))->toBeTrue();
    expect($autoOnly->suitsTransmission(''))->toBeTrue();
});

it('refuses the mismatch, and only the mismatch', function () {
    $manualOnly = trService('manual');

    expect($manualOnly->suitsTransmission('manual'))->toBeTrue();
    expect($manualOnly->suitsTransmission('automatic'))->toBeFalse();
});

it('answers both questions at once from the vehicle itself', function () {
    $carAutomatic = trService('automatic', 'car');
    $customer     = trCustomer();

    expect($carAutomatic->appliesTo(trVehicle($customer, 'automatic', 'TRA 1111', 'car')))->toBeTrue();
    expect($carAutomatic->appliesTo(trVehicle($customer, 'manual', 'TRA 2222', 'car')))->toBeFalse();
    expect($carAutomatic->appliesTo(trVehicle($customer, 'automatic', 'TRA 3333', 'motorcycle')))->toBeFalse();
});

it('keeps bundles and maintenance rules out of it without special-casing', function () {
    // Neither table carries a transmission column — a bundle's restriction
    // follows from the services inside it. The trait reads the missing
    // attribute as null, which already means "applies to both", so the
    // question answers itself correctly on a model that cannot answer it.
    $bundle = Bundle::create(['name' => 'TR Bundle', 'price' => 2000, 'is_active' => true]);

    expect($bundle->transmission)->toBeNull();
    expect($bundle->suitsTransmission('manual'))->toBeTrue();
    expect($bundle->suitsTransmission('automatic'))->toBeTrue();

    expect(\Illuminate\Support\Facades\Schema::hasColumn('bundles', 'transmission'))->toBeFalse();
    expect(\Illuminate\Support\Facades\Schema::hasColumn('maintenance_rules', 'transmission'))->toBeFalse();
});

// ── Server-side enforcement: the signed-in path ─────────────────────────

it('refuses an automatic-only service booked against a manual car', function () {
    $autoOnly = trService('automatic');
    $customer = trCustomer();
    $manual   = trVehicle($customer, 'manual', 'TRM 1111');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [$autoOnly->id], 'vehicle_id' => $manual->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422)->assertJsonFragment(['success' => false]);

    expect(Booking::count())->toBe(0);
});

it('allows the same service against an automatic', function () {
    $autoOnly = trService('automatic');
    $customer = trCustomer();
    $auto     = trVehicle($customer, 'automatic', 'TRM 2222');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [$autoOnly->id], 'vehicle_id' => $auto->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();
});

it('allows it against a vehicle whose transmission was never recorded', function () {
    $autoOnly = trService('automatic');
    $customer = trCustomer();
    $unknown  = trVehicle($customer, null, 'TRM 3333');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [$autoOnly->id], 'vehicle_id' => $unknown->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();
});

it('checks the stored transmission, not what the request claims', function () {
    $autoOnly = trService('automatic');
    $customer = trCustomer();
    $manual   = trVehicle($customer, 'manual', 'TRM 4444');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [$autoOnly->id], 'vehicle_id' => $manual->id,
        'transmission' => 'automatic',   // a lie
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(Booking::count())->toBe(0);
});

// ── Server-side enforcement: the guest path ─────────────────────────────

it('refuses a manual-only service on a guest automatic booking', function () {
    $manualOnly = trService('manual');

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'tr-guest1@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'TRG 1111', 'vehicle_type' => 'car',
        'transmission' => 'automatic',
        'service_ids' => [$manualOnly->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(Booking::count())->toBe(0);
});

it('says which of the two conditions the service failed', function () {
    // "not offered for cars" on an automatic-only service sent customers back
    // to change a vehicle type that was already right.
    $manualOnly = Service::create([
        'name' => 'Manual Transmission Oil Change', 'price' => 1800, 'duration' => 45,
        'transmission' => 'manual',
    ]);

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'tr-guest2@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'TRG 2222', 'vehicle_type' => 'car',
        'transmission' => 'automatic',
        'service_ids' => [$manualOnly->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422)
      ->assertJsonFragment([
          // Names what the customer HAS, not what the service is for — the
          // same convention the vehicle-type half already uses ("not offered
          // for motorcycles"), and the useful one: their transmission is the
          // thing they can correct.
          'message' => 'Manual Transmission Oil Change is not offered for automatic vehicles. '
                       .'Please remove it and try again.',
      ]);
});

it('lets a guest who does not know their transmission book anything', function () {
    // "Not sure" is a real answer on the wizard, and it posts null.
    $autoOnly = trService('automatic');

    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'tr-guest3@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'TRG 3333', 'vehicle_type' => 'car',
        'transmission' => null,
        'service_ids' => [$autoOnly->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    expect(Vehicle::where('plate_number', 'TRG 3333')->sole()->transmission)->toBeNull();
});

it('refuses a transmission that is neither manual nor automatic', function () {
    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'tr-guest4@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'TRG 4444', 'vehicle_type' => 'car',
        'transmission' => 'cvt',
        'service_ids' => [trService(null)->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422)->assertJsonValidationErrors('transmission');
});

it('records what the guest said on their new vehicle', function () {
    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'tr-guest5@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'TRG 5555', 'vehicle_type' => 'car',
        'transmission' => 'manual',
        'service_ids' => [trService(null)->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    expect(Vehicle::where('plate_number', 'TRG 5555')->sole()->transmission)->toBe('manual');
});

it('fills a blank transmission on a returning guest vehicle but never overwrites one', function () {
    // The customer's saved record is the authority on their own car, so this
    // can add what was missing and must not change what is there.
    $customer = User::factory()->create([
        'role' => 'customer', 'email' => 'tr-return@example.com', 'is_guest' => true,
    ]);

    $blank = trVehicle($customer, null, 'TRG 6666');
    $set   = trVehicle($customer, 'manual', 'TRG 7777');

    $post = fn (string $plate, string $transmission) => $this->postJson('/booking/guest', [
        'guest_name' => 'Returning Guest', 'guest_email' => 'tr-return@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => $plate, 'vehicle_type' => 'car',
        'transmission' => $transmission,
        'service_ids' => [trService(null)->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ]);

    $post('TRG 6666', 'automatic')->assertOk();
    expect($blank->fresh()->transmission)->toBe('automatic');

    $post('TRG 7777', 'automatic')->assertOk();
    expect($set->fresh()->transmission)->toBe('manual');
});

// ── The snapshot on the booking ──────────────────────────────────────────

it('records what the vehicle was when the booking was made', function () {
    $customer = trCustomer();
    $vehicle  = trVehicle($customer, 'manual', 'TRS 1111', 'car');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [trService(null)->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();

    expect($booking->vehicle_type)->toBe('car');
    expect($booking->transmission)->toBe('manual');
});

it('does not let a later correction to the vehicle rewrite a past booking', function () {
    // The whole reason the columns are duplicated, and the same decision as
    // booking_service.price.
    $customer = trCustomer();
    $vehicle  = trVehicle($customer, 'manual', 'TRS 2222', 'car');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids' => [trService(null)->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $vehicle->update(['transmission' => 'automatic', 'vehicle_type' => 'motorcycle']);

    $booking = Booking::sole()->fresh();

    expect($booking->transmission)->toBe('manual');
    expect($booking->vehicle_type)->toBe('car');
});

it('reads the snapshot back as one phrase, dropping whichever half is missing', function () {
    $customer = trCustomer();

    $known = Booking::create([
        'user_id' => $customer->id,
        'vehicle_id' => trVehicle($customer, 'automatic', 'TRS 3333')->id,
        'service_id' => trService(null)->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        'status' => 'pending_review', 'reference_number' => 'TRS-0001',
        'vehicle_type' => 'car', 'transmission' => 'automatic',
    ]);

    $partial = Booking::create([
        'user_id' => $customer->id,
        'vehicle_id' => trVehicle($customer, null, 'TRS 4444')->id,
        'service_id' => trService(null)->id,
        'booking_date' => nextWeekday(), 'booking_time' => '11:00',
        'status' => 'pending_review', 'reference_number' => 'TRS-0002',
        'vehicle_type' => 'motorcycle', 'transmission' => null,
    ]);

    expect($known->vehicle_profile)->toBe('Car · Automatic');
    // Not "Motorcycle · Unknown": a placeholder costs a line on every screen
    // and says nothing.
    expect($partial->vehicle_profile)->toBe('Motorcycle');
});

// ── Admin ────────────────────────────────────────────────────────────────

it('lets the shop narrow a service by transmission and widen it back', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/settings/services', [
        'name' => 'CVT Fluid Change', 'price' => 3800, 'duration' => 60,
        'category' => 'CVT & Transmission', 'transmission' => 'automatic',
    ])->assertRedirect();

    $svc = Service::where('name', 'CVT Fluid Change')->sole();
    expect($svc->transmission)->toBe('automatic');

    // "Both" must land as null — an empty string would be a transmission no
    // vehicle has, and would narrow the service to nothing.
    $this->actingAs($admin)->put('/admin/settings/services/'.$svc->id, [
        'name' => 'CVT Fluid Change', 'price' => 3800, 'duration' => 60,
        'category' => 'CVT & Transmission', 'transmission' => '',
    ])->assertRedirect();

    expect($svc->fresh()->transmission)->toBeNull();
});

it('refuses a transmission the shop invented', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/settings/services', [
        'name' => 'Semi Auto Service', 'price' => 600, 'duration' => 30,
        'transmission' => 'semi-automatic',
    ])->assertSessionHasErrors('transmission');
});

it('records the transmission on a walk-in the admin books', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/bookings', [
        'customer_name' => 'Walk In Wally', 'customer_phone' => '09171234567',
        'plate' => 'TRW 1111', 'car_make' => 'Toyota', 'car_model' => 'Vios',
        'car_year' => 2021, 'car_type' => 'car', 'car_transmission' => 'manual',
        'service_ids' => [trService(null)->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertRedirect();

    expect(Vehicle::where('plate_number', 'TRW 1111')->sole()->transmission)->toBe('manual');
    expect(Booking::sole()->transmission)->toBe('manual');
});

it('lets the counter leave a walk-in transmission unrecorded', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/bookings', [
        'customer_name' => 'Walk In Wanda', 'customer_phone' => '09177654321',
        'plate' => 'TRW 2222', 'car_make' => 'Toyota', 'car_model' => 'Vios',
        'car_year' => 2021, 'car_type' => 'car', 'car_transmission' => '',
        'service_ids' => [trService(null)->id],
        'booking_date' => nextWeekday(), 'booking_time' => '11:00',
    ])->assertRedirect();

    expect(Vehicle::where('plate_number', 'TRW 2222')->sole()->transmission)->toBeNull();
    expect(Booking::sole()->transmission)->toBeNull();
});

// ── The policy helper itself ─────────────────────────────────────────────

it('refuses nothing when neither half of the vehicle is known', function () {
    $services = collect([trService('manual'), trService('automatic')]);

    expect(BookingPolicy::servicesNotFor($services, null, null))->toBeEmpty();
    expect(BookingPolicy::servicesNotFor($services, '', ''))->toBeEmpty();
});

it('still refuses on the half that is known', function () {
    $manualOnly = trService('manual');
    $services   = collect([$manualOnly, trService(null)]);

    // No vehicle type given, but the transmission is — so the transmission
    // rule still applies on its own.
    $offending = BookingPolicy::servicesNotFor($services, null, 'automatic');

    expect($offending->pluck('id')->all())->toBe([$manualOnly->id]);
});

it('names both conditions when a selection fails each of them', function () {
    $carOnly    = Service::create(['name' => 'Aircon Check', 'price' => 600, 'duration' => 30, 'vehicle_type' => 'car']);
    $manualOnly = Service::create(['name' => 'Gear Oil Change', 'price' => 900, 'duration' => 30, 'transmission' => 'manual']);

    $offending = BookingPolicy::servicesNotFor(
        collect([$carOnly, $manualOnly]), 'motorcycle', 'automatic'
    );

    $message = BookingPolicy::wrongVehicleMessage($offending, 'motorcycle', 'automatic');

    expect($message)->toBe(
        'Aircon Check is not offered for motorcycles. '
        .'Gear Oil Change is not offered for automatic vehicles. '
        .'Please remove them and try again.'
    );
});

// ── The wizard asks first ────────────────────────────────────────────────

it('asks for the vehicle before the service on the public wizard', function () {
    Service::create(['name' => 'Wizard Service', 'price' => 1100, 'duration' => 30, 'is_active' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    // Four steps, vehicle first. The order is the point: which services apply
    // is decided by the vehicle, so asking afterwards meant choosing work and
    // then having it taken away.
    expect($html)->toContain('Step 1 of 4');
    expect($html)->toContain('<span class="apx-step-lbl">Vehicle</span>');
    expect($html)->toContain('<span class="apx-step-lbl">Service</span>');

    // The vehicle step comes before the service step in the document, which is
    // what actually decides what the customer meets first.
    expect(strpos($html, 'id="apx-view-1"'))->toBeLessThan(strpos($html, 'id="apx-view-2"'));
    expect(strpos($html, 'apx-vtrans-none'))->toBeLessThan(strpos($html, 'id="apx-svc-grid"'));

    // "Not sure" is offered and is the default, because the column is
    // nullable and a forced guess hides the work they came for.
    expect($html)->toContain('Not sure</button>');
});

it('hands the transmission to the customer dashboard, not just the vehicle type', function () {
    // This was the bug: the dashboard's service payload carried `vehicle` but
    // not `transmission`, so the browser-side filter read `undefined` — which
    // is falsy, so the transmission half of it did nothing at all while the
    // vehicle-type half worked. Nothing on the server was wrong, every
    // server-side test passed, and the customer was still offered a CVT
    // service for a manual car.
    //
    // Found by driving the real page in a DOM, which is also the only way it
    // could have been found.
    Service::create([
        'name' => 'CVT Fluid Change', 'price' => 3800, 'duration' => 60,
        'transmission' => 'automatic', 'is_active' => true,
    ]);

    $customer = trCustomer();
    trVehicle($customer, 'manual', 'TRD 1111');

    $html = $this->actingAs($customer)->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->toContain('"transmission":"automatic"');
    expect($html)->toContain('transmission:s.transmission ?? null');
});

it('tells the customer dashboard what each of their vehicles is', function () {
    // The other half of the same filter: the vehicle's own transmission has
    // to reach the browser too, or there is nothing to compare against.
    $customer = trCustomer();
    trVehicle($customer, 'automatic', 'TRD 2222');

    $html = $this->actingAs($customer)->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->toContain('"transmission":"automatic"');
    // And the vehicle select is above the service chip in the booking modal,
    // because the vehicle is what decides which services apply.
    //
    // Matching the markup, not the class name: `.modal-service-chip` also
    // appears in the stylesheet near the top of the page, so a bare strpos
    // for it finds the CSS rule and the comparison is meaningless.
    expect(strpos($html, 'id="mVehicleSelect"'))
        ->toBeLessThan(strpos($html, '<div class="modal-service-chip">'));
});
