<?php

/**
 * Plate numbers: one normaliser, one rule, every entry point.
 *
 * The old rule was /^[A-Z]{3} \d{3,4}$/i, copied into four controllers and a
 * fifth time in JavaScript. That is a car plate. It refused every motorcycle
 * format the shop sees, and the input mask papered over it by silently
 * rewriting what the customer typed — "123 ABC" became "ABC 123", a different
 * plate on a different vehicle.
 *
 * What matters more than the rule is that every write and every lookup agree
 * on one spelling. plate_number is globally unique and compared as a string,
 * so "ABC  1234" and "ABC 1234" are two vehicles for one car unless they do.
 */

use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\PlateNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function plateCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function plateService(): Service
{
    return Service::create(['name' => 'Plate Svc '.uniqid(), 'price' => 1500, 'duration' => 45]);
}

// ── Normalising ──────────────────────────────────────────────────────────

it('normalises to one canonical spelling', function (string $raw, string $expected) {
    expect(PlateNumber::normalise($raw))->toBe($expected);
})->with([
    'lower case'        => ['abc 1234', 'ABC 1234'],
    'surrounding space' => ['  ABC 1234  ', 'ABC 1234'],
    'doubled inner'     => ['ABC  1234', 'ABC 1234'],
    'tab'               => ["ABC\t1234", 'ABC 1234'],
    'already clean'     => ['ABC 1234', 'ABC 1234'],
    'motorcycle'        => ['123 abc', '123 ABC'],
    'hyphen kept'       => ['abc-1234', 'ABC-1234'],
    'null'              => ['', ''],
]);

// ── The formats the shop actually sees ───────────────────────────────────

it('accepts the plate formats on real vehicles', function (string $plate) {
    $customer = plateCustomer();

    $this->actingAs($customer)->postJson('/customer/vehicles', [
        'brand' => 'Honda', 'model' => 'Click', 'year' => 2022,
        'plate' => $plate, 'color' => 'Red', 'vehicle_type' => 'motorcycle',
    ])->assertOk();

    expect(Vehicle::where('user_id', $customer->id)->value('plate_number'))
        ->toBe(PlateNumber::normalise($plate));
})->with([
    'car, current'       => ['ABC 1234'],
    'car, old'           => ['ABC 123'],
    'motorcycle, new'    => ['123 ABC'],
    'motorcycle, old'    => ['AB 12345'],
    'digits first, long' => ['1234 AB'],
    'no separator'       => ['ABC1234'],
    'government/special' => ['SFP 1234'],
    'hyphenated'         => ['ABC-1234'],
    'lower case typed'   => ['abc 1234'],
    'doubled space'      => ['ABC  1234'],
]);

it('still refuses input that is not a plate', function (string $plate) {
    $this->actingAs(plateCustomer())->postJson('/customer/vehicles', [
        'brand' => 'Honda', 'model' => 'Click', 'year' => 2022,
        'plate' => $plate, 'color' => 'Red', 'vehicle_type' => 'motorcycle',
    ])->assertStatus(422);

    expect(Vehicle::count())->toBe(0);
})->with([
    'empty'        => [''],
    'one char'     => ['A'],
    'punctuation'  => ['ABC!1234'],
    'too long'     => ['ABCDEFGHIJKLMNOPQRST'],
    'slash'        => ['ABC/1234'],

    // Shape alone was not enough. These all matched "letters, digits, spaces
    // and hyphens" and two of them — FDFA and SAFEFF — reached the vehicles
    // table before anyone noticed. A plate carries both letters and digits.
    'letters only'      => ['ABC'],
    'two letters'       => ['AB'],
    'digits only'       => ['12'],
    'letters, long'     => ['ABCDEFGH'],
    'spaced letters'    => ['A B C D'],
    // Typed as "ABC" and a row of spaces: normalising collapses the spaces
    // away, which is what hid this behind an apparently three-character plate.
    'letters then space' => ['ABC      '],
    'separators only'   => ['--'],
]);

it('refuses the placeholder plates the system reserves for itself', function (string $plate) {
    // Vehicle::displayPlate() renders these as "Not provided". The old regex
    // made them impossible to type; this one would not, and the customer's
    // real plate would vanish from every screen with no explanation.
    $this->actingAs(plateCustomer())->postJson('/customer/vehicles', [
        'brand' => 'Honda', 'model' => 'Click', 'year' => 2022,
        'plate' => $plate, 'color' => 'Red', 'vehicle_type' => 'motorcycle',
    ])->assertStatus(422);
})->with([
    'no-plate sentinel' => ['NO-PLATE-99'],
    'removed sentinel'  => ['REMOVED-99'],
]);

// ── One spelling across every entry point ────────────────────────────────

it('does not create a second vehicle for the same car typed untidily', function () {
    // This is the whole reason normalising lives in one helper. The guest
    // lookup used strtoupper(trim(...)); two admin paths used strtoupper()
    // with no trim at all. Any disagreement makes a duplicate row, and
    // plate_number is globally unique so the second write would have failed
    // loudly or attached the booking to the wrong vehicle.
    $svc = plateService();

    // Three different days, asked for together. Feeding $i to
    // nextWeekdayAfter() returned the same Monday twice on a Friday, so the
    // third booking landed on the second one's slot and came back 422 —
    // nothing to do with plates.
    $dates = nextWeekdays(3);

    foreach (['ABC 1234', 'abc 1234', '  ABC  1234  '] as $i => $typed) {
        $this->postJson('/booking/guest', [
            'guest_name' => 'Walk In', 'guest_email' => 'plate-dupe@example.com',
            'guest_phone' => '09171234567',
            'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
            'vehicle_plate' => $typed, 'vehicle_type' => 'car',
            'service_ids' => [$svc->id],
            'booking_date' => $dates[$i], 'booking_time' => '10:00',
        ])->assertOk();
    }

    expect(Vehicle::where('plate_number', 'ABC 1234')->count())->toBe(1);
    expect(Vehicle::count())->toBe(1);
});

it('stores the same spelling whichever screen created the vehicle', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Admin customer creation: this path had no format rule at all, and
    // wrote strtoupper() with no trim.
    $this->actingAs($admin)->post('/admin/customers', [
        'name' => 'Juan Dela Cruz', 'email' => 'plate-admin@example.com',
        'phone' => '09171234567',
        'make' => 'Honda', 'model' => 'Click', 'year' => 2022,
        'plate' => '  123   abc  ', 'vehicle_type' => 'motorcycle',
    ])->assertRedirect();

    expect(Vehicle::where('plate_number', '123 ABC')->count())->toBe(1);
});

it('lets a customer keep a motorcycle plate when editing', function () {
    // The mask used to rewrite 123 ABC to ABC 123 on every keystroke, so a
    // customer could not save their own plate back unchanged.
    $customer = plateCustomer();
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Honda', 'model' => 'Click',
        'year' => 2022, 'plate_number' => '123 ABC', 'color' => 'Red',
        'vehicle_type' => 'motorcycle',
    ]);

    $this->actingAs($customer)->patchJson('/customer/vehicles/'.$vehicle->id, [
        'brand' => 'Honda', 'model' => 'Click', 'year' => 2022,
        'plate' => '123 ABC', 'color' => 'Blue', 'vehicle_type' => 'motorcycle',
    ])->assertOk();

    expect($vehicle->fresh()->plate_number)->toBe('123 ABC');
});

it('still refuses a plate another customer already holds', function () {
    $owner = plateCustomer();
    Vehicle::create([
        'user_id' => $owner->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'ABC 1234', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    // Typed untidily, so this also proves the uniqueness check sees the
    // normalised form rather than the raw string.
    $this->actingAs(plateCustomer())->postJson('/customer/vehicles', [
        'brand' => 'Honda', 'model' => 'Click', 'year' => 2022,
        'plate' => 'abc  1234', 'color' => 'Blue', 'vehicle_type' => 'motorcycle',
    ])->assertStatus(422);

    expect(Vehicle::where('plate_number', 'ABC 1234')->count())->toBe(1);
});
