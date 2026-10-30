<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * make is the manufacturer, model is the model line, year is its own column.
 *
 * Three paths wrote a vehicle and each meant something different by those two
 * columns. The guest booking split them properly. The admin forms had one
 * field called "Car Model" and wrote it into both, so a walk-in's vehicle read
 * "Toyota Vios Toyota Vios" wherever the pair was printed together. The
 * customer's own form had one field called "Make & Model" and posted the whole
 * string as the make with `model: ''` hardcoded in the fetch body — which is
 * why the admin booking screen, which printed vehicles.model and nothing else,
 * showed a blank Car Model for every vehicle a customer had added.
 *
 * Seven places composed the name by hand, four of them differently. These pin
 * the convention, the one accessor, and each write path onto it.
 */

function viAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function viService(): Service
{
    return Service::create(['name' => 'Vehicle Identity', 'price' => 1000, 'duration' => 30]);
}

// ── the accessor ──────────────────────────────────────────────────────────

it('composes a vehicle name from its own columns', function () {
    $v = new Vehicle(['make' => 'Honda', 'model' => 'Civic', 'year' => 2023, 'vehicle_type' => 'car']);

    expect($v->make_model)->toBe('Honda Civic');
    expect($v->make_model_year)->toBe('Honda Civic (2023)');
    expect($v->display_name)->toBe('Car — Honda Civic');
});

it('does not leave a stray space when half of it is missing', function () {
    // The shape every customer-added vehicle is in: everything in make.
    $makeOnly = new Vehicle(['make' => 'Honda Civic', 'model' => '', 'year' => 2023]);
    expect($makeOnly->make_model)->toBe('Honda Civic');
    expect($makeOnly->make_model_year)->toBe('Honda Civic (2023)');

    $modelOnly = new Vehicle(['make' => '', 'model' => 'Civic']);
    expect($modelOnly->make_model)->toBe('Civic');

    $neither = new Vehicle(['make' => '', 'model' => '']);
    expect($neither->make_model)->toBe('');
    expect($neither->make_model_year)->toBe('');
    expect($neither->display_name)->toBe('Car');
});

it('says which kind of vehicle it is', function () {
    $moto = new Vehicle(['make' => 'Honda', 'model' => 'Click 125i', 'vehicle_type' => 'motorcycle']);

    expect($moto->display_name)->toBe('Motorcycle — Honda Click 125i');
});

it('omits the year rather than printing an empty bracket', function () {
    $v = new Vehicle(['make' => 'Toyota', 'model' => 'Vios', 'year' => null]);

    expect($v->make_model_year)->toBe('Toyota Vios');
});

// ── the catalogue lives in one place ─────────────────────────────────────

it('keeps one list of vehicles, not a copy per page', function () {
    $catalogue = config('vehicles');

    expect($catalogue)->toHaveKeys(['car', 'motorcycle']);
    expect($catalogue['car'])->toHaveKey('Toyota');
    expect($catalogue['car']['Toyota'])->toContain('Vios');
    expect($catalogue['motorcycle']['Honda'])->toContain('Click 125i');

    // It was written out twice, verbatim, in index.blade.php, which is why no
    // other form could use it. If a literal comes back, this fails.
    $source = file_get_contents(resource_path('views/index.blade.php'));
    expect($source)->not->toContain("Toyota:['Vios'");
    expect(substr_count($source, "@json(config('vehicles'))"))->toBe(2);
});

// ── the admin walk-in booking ────────────────────────────────────────────

it('records a walk-in vehicle in the right columns', function () {
    $this->actingAs(viAdmin())->post('/admin/bookings', [
        'customer_name'  => 'Walk In',
        'customer_phone' => '09170000001',
        'plate'          => 'VID 1001',
        'car_make'       => 'Toyota',
        'car_model'      => 'Vios',
        'car_year'       => 2021,
        'car_type'       => 'car',
        'service_id'     => viService()->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ])->assertRedirect();

    $vehicle = Vehicle::where('plate_number', 'VID 1001')->sole();

    expect($vehicle->make)->toBe('Toyota');
    expect($vehicle->model)->toBe('Vios');
    expect($vehicle->year)->toBe(2021);

    // It used to write the one "Car Model" field into both columns, which read
    // back doubled everywhere the pair is printed together.
    expect($vehicle->make_model)->toBe('Toyota Vios');
});

it('records that a walk-in arrived on a motorcycle', function () {
    $this->actingAs(viAdmin())->post('/admin/bookings', [
        'customer_name'  => 'Rider',
        'customer_phone' => '09170000002',
        'plate'          => 'VID 1002',
        'car_make'       => 'Honda',
        'car_model'      => 'Click 125i',
        'car_type'       => 'motorcycle',
        'service_id'     => viService()->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ])->assertRedirect();

    // The column defaults to "car", so every motorcycle a walk-in brought in
    // was filed as a car until the form started asking.
    expect(Vehicle::where('plate_number', 'VID 1002')->sole()->vehicle_type)->toBe('motorcycle');
});

it('still records a walk-in whose vehicle details were not taken', function () {
    $this->actingAs(viAdmin())->post('/admin/bookings', [
        'customer_name'  => 'In A Hurry',
        'customer_phone' => '09170000003',
        'plate'          => 'VID 1003',
        'service_id'     => viService()->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ])->assertRedirect();

    $vehicle = Vehicle::where('plate_number', 'VID 1003')->sole();

    // make is NOT NULL, so it keeps its fallback. The rest are honestly empty
    // rather than filled with a copy of something else.
    expect($vehicle->make)->toBe('Unknown');
    expect($vehicle->model)->toBe('');
    expect($vehicle->year)->toBeNull();
});

// ── the admin customer form ──────────────────────────────────────────────

it('records a new customers vehicle in the right columns', function () {
    $this->actingAs(viAdmin())->post('/admin/customers', [
        'name'      => 'New Person',
        'email'     => 'vid@example.com',
        'phone'     => '09170000004',
        'plate'     => 'VID 2001',
        'car_make'  => 'Mitsubishi',
        'car_model' => 'Mirage G4',
        'car_year'  => 2019,
        'car_type'  => 'car',
    ])->assertRedirect();

    $vehicle = Vehicle::where('plate_number', 'VID 2001')->sole();

    expect($vehicle->make)->toBe('Mitsubishi');
    expect($vehicle->model)->toBe('Mirage G4');
    expect($vehicle->year)->toBe(2019);
    expect($vehicle->make_model)->toBe('Mitsubishi Mirage G4');
});

// ── the customer's own form ──────────────────────────────────────────────

it('records a customers own vehicle in the right columns', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->postJson('/customer/vehicles', [
        'brand'        => 'Honda',
        'model'        => 'Civic',
        'plate'        => 'VID 3001',
        'year'         => 2023,
        'vehicle_type' => 'car',
    ])->assertOk();

    $vehicle = Vehicle::where('plate_number', 'VID 3001')->sole();

    // This form posted `model: ''` and put the whole string in make, so the
    // model column was empty for every vehicle a customer ever added.
    expect($vehicle->make)->toBe('Honda');
    expect($vehicle->model)->toBe('Civic');
    expect($vehicle->make_model)->toBe('Honda Civic');
});

it('hands the page the raw columns as well as the name to print', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $payload = $this->actingAs($customer)->postJson('/customer/vehicles', [
        'brand' => 'Honda', 'model' => 'Civic', 'plate' => 'VID 3002', 'year' => 2023,
    ])->json('vehicle');

    // The edit form writes back into each column separately, so it needs them
    // separately. Sending only the composed string is how everything ended up
    // in make in the first place.
    expect($payload['name'])->toBe('Honda Civic');
    expect($payload['make'])->toBe('Honda');
    expect($payload['model'])->toBe('Civic');
});

// ── the screen that started this ─────────────────────────────────────────

it('shows the make, model, year and type on the booking edit screen', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    // A vehicle in the shape the customer dashboard used to produce: the whole
    // name in make, model empty. The screen read model, so it showed nothing.
    $vehicle = Vehicle::create([
        'user_id'      => $customer->id,
        'plate_number' => 'VID 4001',
        'make'         => 'Honda Civic',
        'model'        => '',
        'year'         => 2023,
        'vehicle_type' => 'car',
    ]);

    $booking = Booking::create([
        'user_id'      => $customer->id,
        'vehicle_id'   => $vehicle->id,
        'service_id'   => viService()->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
        'status'       => 'pending',
        'duration'     => 60,
        'reference_number' => 'APX-VID-'.uniqid(),
    ]);

    $html = $this->actingAs(viAdmin())
        ->get('/admin/bookings/'.$booking->id.'/edit')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('VID 4001');
    expect($html)->toContain('Honda Civic');   // was blank: the screen read model
    expect($html)->toContain('2023');          // was not shown at all
    expect($html)->toContain('Vehicle Type');  // nor was this
});

it('names a motorcycle as one on the booking edit screen', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id'      => $customer->id,
        'plate_number' => 'VID 4002',
        'make'         => 'Honda',
        'model'        => 'Click 125i',
        'year'         => 2022,
        'vehicle_type' => 'motorcycle',
    ]);

    $booking = Booking::create([
        'user_id'      => $customer->id,
        'vehicle_id'   => $vehicle->id,
        'service_id'   => viService()->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
        'status'       => 'pending',
        'duration'     => 60,
        'reference_number' => 'APX-VID-'.uniqid(),
    ]);

    $html = $this->actingAs(viAdmin())
        ->get('/admin/bookings/'.$booking->id.'/edit')
        ->assertOk()
        ->getContent();

    // The mechanic needs to know what is arriving before it arrives.
    expect($html)->toContain('Motorcycle');
    expect($html)->toContain('Click 125i');
});
