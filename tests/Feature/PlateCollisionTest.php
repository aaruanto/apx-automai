<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * vehicles.plate_number is a plain UNIQUE index, and a soft delete does not
 * release it — the row stays, deleted_at set, and the plate is taken for good.
 *
 * Four paths write a plate. Two checked for a collision with a query the
 * model's soft-delete scope hides trashed rows from, so a plate on a deleted
 * vehicle looked free; two did not check at all. All four then inserted, hit
 * the constraint, and returned a 500 — measured, six distinct ways, before
 * this was fixed. The admin walk-in booking is the one the shop hit: a plate
 * already in the system meant a 500 and no booking.
 *
 * Every write path now asks Vehicle::holdingPlate(), which is withTrashed.
 */

function pcAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function pcService(): Service
{
    return Service::create(['name' => 'Plate Check', 'price' => 1200, 'duration' => 30]);
}

/** A vehicle on someone else's account, optionally soft-deleted. */
function pcOtherOwner(string $plate, bool $trashed = false): Vehicle
{
    $owner = User::factory()->create(['role' => 'customer', 'name' => 'Existing Owner']);

    $vehicle = Vehicle::create([
        'user_id'      => $owner->id,
        'plate_number' => $plate,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2020,
    ]);

    if ($trashed) {
        $vehicle->delete();
    }

    return $vehicle;
}

function pcBookingPayload(string $plate, string $phone = '09170000001'): array
{
    return [
        'customer_name'  => 'Walk In Customer',
        'customer_phone' => $phone,
        'plate'          => $plate,
        'service_id'     => pcService()->id,
        'booking_date'   => today()->toDateString(),
        'booking_time'   => '10:00',
    ];
}

// ── the lookup itself ──────────────────────────────────────────────────────

it('finds a plate held by a soft-deleted vehicle', function () {
    $vehicle = pcOtherOwner('ABC 1234', trashed: true);

    // The point of the helper: the plain query the old guards used sees nothing.
    expect(Vehicle::where('plate_number', 'ABC 1234')->exists())->toBeFalse();
    expect(Vehicle::holdingPlate('ABC 1234')?->id)->toBe($vehicle->id);
});

it('normalises the plate it is asked about, and ignores a blank one', function () {
    pcOtherOwner('ABC 1234');

    expect(Vehicle::holdingPlate('abc  1234 '))->not->toBeNull();
    expect(Vehicle::holdingPlate(''))->toBeNull();
    expect(Vehicle::holdingPlate(null))->toBeNull();
    expect(Vehicle::holdingPlate('   '))->toBeNull();
});

// ── admin walk-in booking: the reported 500 ───────────────────────────────

it('refuses a walk-in booking whose plate belongs to another account', function () {
    pcOtherOwner('ABC 1234');

    $this->actingAs(pcAdmin())
        ->post('/admin/bookings', pcBookingPayload('ABC 1234'))
        ->assertRedirect()
        ->assertSessionHasErrors('plate');

    expect(Booking::count())->toBe(0);
    expect(Vehicle::withTrashed()->where('plate_number', 'ABC 1234')->count())->toBe(1);
});

it('names the account holding the plate, so admin can book under it', function () {
    pcOtherOwner('ABC 1234');

    $this->actingAs(pcAdmin())->post('/admin/bookings', pcBookingPayload('ABC 1234'));

    expect(session('errors')->first('plate'))->toContain('Existing Owner');
});

it('refuses a walk-in booking whose plate is on another accounts deleted vehicle', function () {
    pcOtherOwner('XYZ 5678', trashed: true);

    $this->actingAs(pcAdmin())
        ->post('/admin/bookings', pcBookingPayload('XYZ 5678'))
        ->assertRedirect()
        ->assertSessionHasErrors('plate');

    expect(Booking::count())->toBe(0);
});

it('books a walk-in against the customers own deleted vehicle, restoring it', function () {
    $customer = User::factory()->create(['role' => 'customer', 'phone' => '09171112222']);

    $vehicle = Vehicle::create([
        'user_id'      => $customer->id,
        'plate_number' => 'OWN 1234',
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2020,
    ]);
    $vehicle->delete();

    $this->actingAs(pcAdmin())
        ->post('/admin/bookings', pcBookingPayload('OWN 1234', '09171112222'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Booking::count())->toBe(1);

    // Restored, not duplicated: the service history stays on the one car.
    expect($vehicle->fresh()->trashed())->toBeFalse();
    expect(Vehicle::withTrashed()->where('plate_number', 'OWN 1234')->count())->toBe(1);
    expect(Booking::first()->vehicle_id)->toBe($vehicle->id);
});

// ── admin customer records ────────────────────────────────────────────────

it('refuses a new customer whose plate is already registered', function () {
    pcOtherOwner('DEF 1111');

    $this->actingAs(pcAdmin())
        ->post('/admin/customers', [
            'name'  => 'New Person',
            'email' => 'new@example.com',
            'phone' => '09170000003',
            'plate' => 'DEF 1111',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('plate');

    // Checked before anything is written. This used to insert the user and the
    // customer profile, then die on the vehicle, leaving a customer record with
    // no vehicle and the admin looking at a 500.
    expect(User::where('email', 'new@example.com')->exists())->toBeFalse();
    expect(Customer::count())->toBe(0);
});

// ── the customer's own vehicle form ───────────────────────────────────────

it('refuses a vehicle whose plate is on another accounts deleted vehicle', function () {
    pcOtherOwner('GHI 2222', trashed: true);

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->postJson('/customer/vehicles', [
            'brand' => 'Honda', 'model' => 'City', 'plate' => 'GHI 2222', 'year' => 2021,
        ])
        ->assertStatus(422);
});

it('tells the customer the plate is taken without saying whose it is', function () {
    pcOtherOwner('GHI 2222');

    $message = $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->postJson('/customer/vehicles', [
            'brand' => 'Honda', 'model' => 'City', 'plate' => 'GHI 2222', 'year' => 2021,
        ])
        ->json('message');

    expect($message)->not->toContain('Existing Owner');
});

// ── guest booking ─────────────────────────────────────────────────────────

function pcGuestPayload(string $plate, string $email = 'guest@example.com'): array
{
    return [
        'guest_name'    => 'Guest Person',
        'guest_email'   => $email,
        'guest_phone'   => '09170000009',
        'vehicle_make'  => 'Honda',
        'vehicle_model' => 'City',
        'vehicle_plate' => $plate,
        'vehicle_year'  => 2021,
        'vehicle_type'  => 'car',
        'service_ids'   => [pcService()->id],
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
    ];
}

it('refuses a guest booking whose plate is on another accounts deleted vehicle', function () {
    pcOtherOwner('GST 9999', trashed: true);

    $this->postJson('/booking/guest', pcGuestPayload('GST 9999'))
        ->assertStatus(422);

    expect(Booking::count())->toBe(0);
});

it('lets a returning guest rebook on their own deleted vehicle', function () {
    $guest = User::factory()->create([
        'role' => 'customer', 'email' => 'returning@example.com', 'is_guest' => true,
    ]);

    $vehicle = Vehicle::create([
        'user_id'      => $guest->id,
        'plate_number' => 'RTN 4321',
        'make'         => 'Honda',
        'model'        => 'City',
        'year'         => 2021,
    ]);
    $vehicle->delete();

    $this->postJson('/booking/guest', pcGuestPayload('RTN 4321', 'returning@example.com'))
        ->assertOk();

    expect($vehicle->fresh()->trashed())->toBeFalse();
    expect(Vehicle::withTrashed()->where('plate_number', 'RTN 4321')->count())->toBe(1);
});

// ── the refusal has to be visible, or it is a blank bounce ────────────────

it('shows the admin why the walk-in booking was refused', function () {
    pcOtherOwner('ABC 1234');
    $admin = pcAdmin();

    $html = $this->actingAs($admin)
        ->from('/admin/bookings/create')
        ->followingRedirects()
        ->post('/admin/bookings', pcBookingPayload('ABC 1234'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('This booking could not be saved.');
    expect($html)->toContain('already registered to Existing Owner');

    // And the refusal does not cost the admin the whole form. withInput() was
    // being sent and the view ignored it, so the walk-in standing at the
    // counter had their name, phone, car and chosen slot retyped from scratch
    // to correct one field.
    expect($html)->toContain('Walk In Customer');
    expect($html)->toContain('09170000001');
    expect($html)->toContain('ABC 1234');

    // The slot too: the picker reads these two hidden fields on load.
    expect($html)->toContain('name="booking_date" id="adminBookingDate" value="'.today()->toDateString().'"');
    expect($html)->toContain('name="booking_time" id="adminBookingTime" value="10:00"');
});

it('keeps the services the admin had ticked when the booking is refused', function () {
    pcOtherOwner('ABC 1234');

    $wanted = pcService();
    $other  = Service::create(['name' => 'Not Chosen', 'price' => 900, 'duration' => 30]);

    $html = $this->actingAs(pcAdmin())
        ->from('/admin/bookings/create')
        ->followingRedirects()
        ->post('/admin/bookings', [
            'customer_name'  => 'Walk In Customer',
            'customer_phone' => '09170000001',
            'plate'          => 'ABC 1234',
            'service_ids'    => [$wanted->id],
            'booking_date'   => today()->toDateString(),
            'booking_time'   => '10:00',
        ])
        ->assertOk()
        ->getContent();

    $checked = fn (int $id) => (bool) preg_match(
        '/name="service_ids\[\]" value="'.$id.'"[^>]*checked/', $html
    );

    expect($checked($wanted->id))->toBeTrue();
    expect($checked($other->id))->toBeFalse();
});

it('shows the admin why the customer was refused, and keeps what they typed', function () {
    pcOtherOwner('DEF 1111');

    $html = $this->actingAs(pcAdmin())
        ->from('/admin/customers/create')
        ->followingRedirects()
        ->post('/admin/customers', [
            'name'      => 'New Person',
            'email'     => 'new@example.com',
            'phone'     => '09170000003',
            'plate'     => 'DEF 1111',
            'color'     => 'White',
            'car_model' => 'Toyota Vios 2021',
        ])
        ->assertOk()
        ->getContent();

    // This page had no error display at all before, so the refusal was a
    // silent reload.
    expect($html)->toContain('This customer could not be added.');
    expect($html)->toContain('already registered to Existing Owner');

    // And withInput() now has something to put back, so the admin corrects
    // one field instead of retyping the form.
    foreach (['New Person', 'new@example.com', '09170000003', 'DEF 1111', 'White', 'Toyota Vios 2021'] as $typed) {
        expect($html)->toContain($typed);
    }
});

// ── the seventh path, missed by the first sweep ──────────────────────────

it('refuses to rename a vehicle onto a plate held by a deleted one', function () {
    pcOtherOwner('TRS 1111', trashed: true);

    $me = User::factory()->create(['role' => 'customer']);
    $mine = Vehicle::create([
        'user_id' => $me->id, 'plate_number' => 'MINE 22',
        'make' => 'Honda', 'model' => 'City', 'year' => 2021,
    ]);

    // updateVehicle kept its own check, written as Vehicle::where(...), which
    // the soft-delete scope hides trashed rows from. It passed, the update hit
    // the unique index, and the customer got a 500 for editing their own car.
    $this->actingAs($me)->patchJson('/customer/vehicles/'.$mine->id, [
        'brand' => 'Honda', 'model' => 'City', 'plate' => 'TRS 1111', 'year' => 2021,
    ])->assertStatus(422);

    expect($mine->fresh()->plate_number)->toBe('MINE 22');
});

it('still lets a customer edit a vehicle without changing its plate', function () {
    $me = User::factory()->create(['role' => 'customer']);
    $mine = Vehicle::create([
        'user_id' => $me->id, 'plate_number' => 'MINE 33',
        'make' => 'Honda', 'model' => 'City', 'year' => 2021, 'color' => 'Red',
    ]);

    // The vehicle's own plate has to pass its own check, or nobody could ever
    // change their colour.
    $this->actingAs($me)->patchJson('/customer/vehicles/'.$mine->id, [
        'brand' => 'Honda', 'model' => 'City', 'plate' => 'MINE 33',
        'year' => 2021, 'color' => 'Blue',
    ])->assertOk();

    expect($mine->fresh()->color)->toBe('Blue');
});
