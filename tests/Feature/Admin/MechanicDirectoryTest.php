<?php

use App\Models\Booking;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The mechanic directory that replaced "Staff Accounts".
 *
 * The old screen created users with a role dropdown that included admin, so
 * anyone who reached Settings could mint a full administrator. There are no
 * accounts here at all now, which is the point of these tests as much as the
 * CRUD itself.
 */

function dirAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

// ── The staff portal is gone ─────────────────────────────────────────────

it('no longer serves any staff route', function (string $path) {
    $this->actingAs(dirAdmin())->get($path)->assertNotFound();
})->with(['/staff/dashboard', '/staff']);

it('no longer offers a way to create a login account from settings', function () {
    $this->actingAs(dirAdmin())->get('/admin/settings')->assertOk()
        ->assertDontSee('Staff Accounts')
        ->assertDontSee('Add Staff');
});

it('refuses the removed staff endpoints', function () {
    $admin = dirAdmin();

    $this->actingAs($admin)->post('/admin/settings/staff', [
        'name' => 'Sneaky', 'email' => 'sneaky@example.com',
        'role' => 'admin', 'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
    ])->assertNotFound();

    // The route is gone, so no account can have been created by reaching it.
    expect(User::where('email', 'sneaky@example.com')->exists())->toBeFalse();
});

// ── Managing mechanics ───────────────────────────────────────────────────

it('adds a mechanic with just a name and a number', function () {
    $this->actingAs(dirAdmin())->post('/admin/mechanics', [
        'name' => 'Mang Kardo', 'contact_number' => '0917 123 4567',
    ])->assertRedirect();

    $m = Mechanic::sole();
    expect($m->name)->toBe('Mang Kardo');
    expect($m->status)->toBe('active');
    // No account is created alongside the record.
    expect(User::where('name', 'Mang Kardo')->exists())->toBeFalse();
});

it('rejects a contact number the SMS service could not send to', function (string $number) {
    $this->actingAs(dirAdmin())->post('/admin/mechanics', [
        'name' => 'Mang Kardo', 'contact_number' => $number,
    ])->assertSessionHasErrors('contact_number');

    expect(Mechanic::count())->toBe(0);
})->with([
    'landline'      => '0281234567',
    'foreign'       => '+14155551234',
    'nonsense'      => 'ring me',
]);

it('accepts the number shapes customers actually write', function (string $number) {
    $this->actingAs(dirAdmin())->post('/admin/mechanics', [
        'name' => 'Mech '.$number, 'contact_number' => $number,
    ])->assertSessionHasNoErrors();
})->with(['09171234567', '+63 917 123 4567', '639171234567', '0917-123-4567']);

it('refuses two mechanics with the same name', function () {
    Mechanic::create(['name' => 'Mang Kardo', 'contact_number' => '09171234567', 'status' => 'active']);

    // The list is read as a dropdown; two identical names make it useless.
    $this->actingAs(dirAdmin())->post('/admin/mechanics', [
        'name' => 'Mang Kardo', 'contact_number' => '09181234567',
    ])->assertSessionHasErrors('name');

    expect(Mechanic::count())->toBe(1);
});

it('edits a mechanic without touching their bookings', function () {
    $m = Mechanic::create(['name' => 'Mang Kardo', 'contact_number' => '09171234567', 'status' => 'active']);

    $customer = User::factory()->create(['role' => 'customer']);
    $service  = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 30]);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'MEC 1111', 'color' => 'Red',
    ]);
    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'mechanic_id' => $m->id, 'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '09:00', 'status' => 'confirmed', 'reference_number' => 'APX-MEC1',
    ]);

    $this->actingAs(dirAdmin())->put("/admin/mechanics/{$m->id}", [
        'name' => 'Mang Ricardo', 'contact_number' => '09181234567',
    ])->assertRedirect();

    expect($m->refresh()->name)->toBe('Mang Ricardo');
    expect($booking->refresh()->mechanic_id)->toBe($m->id);
});

// ── Deactivation, not deletion ───────────────────────────────────────────

it('deactivates rather than deleting, keeping the booking history', function () {
    $m = Mechanic::create(['name' => 'Mang Kardo', 'contact_number' => '09171234567', 'status' => 'active']);

    $this->actingAs(dirAdmin())->patch("/admin/mechanics/{$m->id}/toggle")->assertRedirect();

    // The row survives: a booking they worked still names them.
    expect(Mechanic::find($m->id))->not->toBeNull();
    expect($m->refresh()->status)->toBe('inactive');
});

it('reactivates a mechanic who comes back', function () {
    $m = Mechanic::create(['name' => 'Mang Kardo', 'contact_number' => '09171234567', 'status' => 'inactive']);

    $this->actingAs(dirAdmin())->patch("/admin/mechanics/{$m->id}/toggle")->assertRedirect();

    expect($m->refresh()->status)->toBe('active');
});

it('exposes no delete route for a mechanic', function () {
    $m = Mechanic::create(['name' => 'Mang Kardo', 'contact_number' => '09171234567', 'status' => 'active']);

    $this->actingAs(dirAdmin())->delete("/admin/mechanics/{$m->id}")->assertStatus(405);

    expect(Mechanic::find($m->id))->not->toBeNull();
});

// ── Access ───────────────────────────────────────────────────────────────

it('keeps the directory from customers', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get('/admin/mechanics')->assertStatus(403);
});

it('sends a signed-out visitor to the login page', function () {
    // Its own test: actingAs persists for the rest of a test, so asserting
    // this after a signed-in request would silently re-check that user.
    $this->get('/admin/mechanics')->assertRedirect('/login');
});

it('lists mechanics with the number of bookings each holds', function () {
    Mechanic::create(['name' => 'Mang Kardo', 'contact_number' => '09171234567', 'status' => 'active']);

    $this->actingAs(dirAdmin())->get('/admin/mechanics')->assertOk()
        ->assertSee('Mang Kardo')
        ->assertSee('09171234567')
        // Says plainly that these are not accounts, so nobody looks for a
        // password field that was deliberately removed.
        ->assertSee('do not sign in', false);
});
