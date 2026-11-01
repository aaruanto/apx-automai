<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A rebook must stay with the customer it was rebooked from.
 *
 * store() re-derives the customer from the typed phone number —
 * User::firstOrCreate(['phone' => $request->customer_phone]) — and then
 * refuses the booking if the plate belongs to a different account. The rebook
 * form knows exactly who the customer is and which vehicle it is, because both
 * are on the cancelled booking it was opened from, and it posts neither. So
 * the server guesses, and when the guess lands on a different row than the
 * vehicle's owner the admin is told the plate belongs to the customer they are
 * already looking at.
 *
 * Three ways the guess goes wrong, all three reproduced below. In the local
 * database 10 of 16 users have no usable phone number, and three separate
 * accounts are named "Gelo Ruanto" — so modes 1 and 2 are not hypothetical.
 */

function riAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function riService(): Service
{
    static $n = 1;

    return Service::create(['name' => 'Identity '.$n++, 'price' => 1200, 'duration' => 60]);
}

/** A cancelled booking, its customer and its vehicle. */
function riCancelled(?string $phone, string $plate, string $name = 'Gelo Ruanto'): Booking
{
    static $n = 1;

    $customer = User::factory()->create([
        'role'  => 'customer',
        'name'  => $name,
        'phone' => $phone,
        'email' => 'identity'.$n.'@gmail.com',
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'plate_number' => $plate,
        'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
    ]);

    $service = riService();

    $booking = Booking::create([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => '2026-08-31',
        'booking_time'     => '10:00',
        'duration'         => 60,
        'status'           => 'cancelled',
        'reference_number' => 'APX-RID-'.$n++,
    ]);

    $booking->services()->sync([$service->id => ['price' => 1200, 'duration' => 60]]);

    return $booking;
}

/** Submit the rebook form the way the browser does, for a given booking. */
function riRebook(User $admin, Booking $original, array $overrides = [])
{
    return test()->actingAs($admin)->post('/admin/bookings', array_merge([
        'customer_name'  => $original->user->name,
        'customer_phone' => $original->user->phone,
        'customer_email' => $original->user->email,
        'plate'          => $original->vehicle->plate_number,
        'service_id'     => $original->service_id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
        'user_id'        => $original->user_id,
        'vehicle_id'     => $original->vehicle_id,
        'rebooked_from'  => $original->reference_number,
    ], $overrides));
}

// ── mode 1: the customer has no phone on record ─────────────────────────

it('rebooks a customer who has no phone number, without inventing a second account', function () {
    $admin    = riAdmin();
    $original = riCancelled(phone: null, plate: 'NFR 2244');

    $usersBefore = User::count();

    // The form prefills $src->user->phone ?? '' — empty — and the field is
    // required, so the admin types the number in. Nothing matches it, and
    // firstOrCreate used to create a whole second account while the vehicle
    // stayed with the first.
    riRebook($admin, $original, ['customer_phone' => '09171234567'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::count())->toBe($usersBefore);

    $new = Booking::where('status', 'pending')->sole();
    expect($new->user_id)->toBe($original->user_id);
    expect($new->vehicle_id)->toBe($original->vehicle_id);
});

// ── mode 2: the same person exists more than once ───────────────────────

it('rebooks the right one of three customers sharing a name', function () {
    $admin = riAdmin();

    // The shape of the live data: three accounts called Gelo Ruanto, three
    // phone numbers, three emails. The booking belongs to one of them.
    $original = riCancelled(phone: '09312442342', plate: 'NFR 2244');
    $other    = User::factory()->create(['role' => 'customer', 'name' => 'Gelo Ruanto', 'phone' => '09274172746']);
    $another  = User::factory()->create(['role' => 'customer', 'name' => 'Gelo Ruanto', 'phone' => '09837461733']);

    // The admin's typed number lands on a different Gelo Ruanto than the one
    // the vehicle belongs to — which is how the message ends up naming the
    // customer already on screen.
    riRebook($admin, $original, ['customer_phone' => $other->phone])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $new = Booking::where('status', 'pending')->sole();

    // The booking follows the identity it was rebooked from, not the phone.
    expect($new->user_id)->toBe($original->user_id);
    expect($new->user_id)->not->toBe($other->id);
    expect($new->user_id)->not->toBe($another->id);
});

// ── mode 3: the admin corrects the phone number ─────────────────────────

it('does not re-point a rebook at another account when the phone is corrected', function () {
    $admin    = riAdmin();
    $original = riCancelled(phone: '09171111111', plate: 'NFR 2244');

    $usersBefore = User::count();

    riRebook($admin, $original, ['customer_phone' => '09179999999'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::count())->toBe($usersBefore);
    expect(Booking::where('status', 'pending')->sole()->user_id)->toBe($original->user_id);
});

// ── the plate check must survive all of this ────────────────────────────

it('still refuses a walk-in on a plate that belongs to someone else', function () {
    $admin = riAdmin();
    $owner = riCancelled(phone: '09171111111', plate: 'OWN 7788');

    // No user_id posted: this is a plain walk-in, and the plate is not theirs.
    // This is the 500 the plate check was added to prevent, and it must stay
    // a refusal.
    $this->actingAs($admin)->post('/admin/bookings', [
        'customer_name'  => 'Someone Else',
        'customer_phone' => '09175550000',
        'plate'          => 'OWN 7788',
        'service_id'     => riService()->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '11:00',
    ])->assertSessionHasErrors('plate');

    expect(Booking::where('status', 'pending')->count())->toBe(0);
});

it('refuses a vehicle_id that belongs to a different customer', function () {
    $admin  = riAdmin();
    $mine   = riCancelled(phone: '09171111111', plate: 'MINE 11');
    $theirs = riCancelled(phone: '09172222222', plate: 'THRS 22');

    // A forged id must never attach a booking to someone else's vehicle. It
    // falls back to resolving the plate, which then refuses because the plate
    // is not this customer's either.
    riRebook($admin, $mine, [
        'vehicle_id' => $theirs->vehicle_id,
        'plate'      => $theirs->vehicle->plate_number,
    ])->assertSessionHasErrors('plate');

    expect(Booking::where('status', 'pending')->count())->toBe(0);
});

it('ignores a user_id that does not exist', function () {
    $admin    = riAdmin();
    $original = riCancelled(phone: '09171111111', plate: 'GHO 5544');

    // Falls back to the phone, which is this customer's, so the booking still
    // goes through and still lands on them.
    riRebook($admin, $original, ['user_id' => 999999])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Booking::where('status', 'pending')->sole()->user_id)->toBe($original->user_id);
});

// ── the form has to carry the identity in the first place ───────────────

it('puts the customer and vehicle on the rebook form', function () {
    $original = riCancelled(phone: '09171111111', plate: 'CRY 1234');

    $html = $this->actingAs(riAdmin())
        ->get('/admin/bookings/'.$original->id.'/rebook')
        ->assertOk()->getContent();

    expect($html)->toContain('name="user_id" value="'.$original->user_id.'"');
    expect($html)->toContain('name="vehicle_id" value="'.$original->vehicle_id.'"');
});

it('posts no identity from a plain new booking form', function () {
    $html = $this->actingAs(riAdmin())
        ->get('/admin/bookings/create')
        ->assertOk()->getContent();

    expect($html)->not->toContain('name="user_id"');
    expect($html)->not->toContain('name="vehicle_id"');
});

// ── the other 500 on this path ──────────────────────────────────────────

it('refuses a duplicate email instead of crashing on it', function () {
    $admin = riAdmin();

    User::factory()->create(['role' => 'customer', 'email' => 'taken@gmail.com', 'phone' => '09171111111']);

    // customer_email was not validated at all — read only as a create default.
    // A new account with an address already in the users table hit the unique
    // index and returned a 500 with nothing saved.
    $this->actingAs($admin)->post('/admin/bookings', [
        'customer_name'  => 'Someone New',
        'customer_phone' => '09179999999',
        'customer_email' => 'taken@gmail.com',
        'plate'          => 'DUP 1234',
        'service_id'     => riService()->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ])->assertSessionHasErrors('customer_email');

    expect(Booking::count())->toBe(0);
    expect(User::where('email', 'taken@gmail.com')->count())->toBe(1);
});

it('refuses a malformed email instead of storing it', function () {
    $this->actingAs(riAdmin())->post('/admin/bookings', [
        'customer_name'  => 'Typo Test',
        'customer_phone' => '09179999999',
        'customer_email' => 'not-an-address',
        'plate'          => 'DUP 5678',
        'service_id'     => riService()->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ])->assertSessionHasErrors('customer_email');

    expect(User::where('name', 'Typo Test')->exists())->toBeFalse();
});

it('lets a rebook post the customers own existing address', function () {
    $admin    = riAdmin();
    $original = riCancelled(phone: '09171111111', plate: 'OWN 9999');

    // Not unique-validated, deliberately: the rebook form prefills the
    // customer's own address, and refusing it would break every rebook.
    riRebook($admin, $original)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Booking::where('status', 'pending')->sole()->user_id)->toBe($original->user_id);
});

it('keeps the phone on an account it creates', function () {
    $this->actingAs(riAdmin())->post('/admin/bookings', [
        'customer_name'  => 'Brand New',
        'customer_phone' => '09175551234',
        'plate'          => 'NEW 4321',
        'service_id'     => riService()->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ])->assertRedirect();

    // firstOrCreate used to supply the phone from its lookup key. Creating
    // explicitly means it has to be passed, or the next booking for the same
    // customer would not find them and would make a second account.
    expect(User::where('name', 'Brand New')->sole()->phone)->toBe('09175551234');
});
