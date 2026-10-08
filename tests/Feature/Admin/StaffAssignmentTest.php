<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Staff assignment.
 *
 * bookings.staff_id is a foreign key to users, and staff are users with
 * role = 'staff'. The Booking relation previously resolved it against the
 * Employee model — a different table the key does not reference — so an
 * assignment would have violated the foreign key on PostgreSQL. Nothing had
 * ever been assigned, so it never fired.
 */

function assignAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function mechanic(string $name): User
{
    return User::factory()->create(['role' => 'staff', 'name' => $name, 'is_active' => true]);
}

function jobAt(string $time, int $minutes = 60, ?int $staffId = null, ?string $date = null): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'STF '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Job '.$unique, 'price' => 1000, 'duration' => $minutes]);

    return Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'staff_id' => $staffId,
        'booking_date' => $date ?? today()->addDay()->toDateString(),
        'booking_time' => $time, 'duration' => $minutes, 'status' => 'confirmed',
        'reference_number' => 'APX-'.$unique,
    ]);
}

it('resolves the assigned mechanic from the users table', function () {
    $mech    = mechanic('Mang Kardo');
    $booking = jobAt('09:00', 60, $mech->id);

    expect($booking->staff)->not->toBeNull();
    expect($booking->staff->id)->toBe($mech->id);
    expect($booking->staff->name)->toBe('Mang Kardo');
});

it('assigns a mechanic from the list', function () {
    $mech    = mechanic('Mang Kardo');
    $booking = jobAt('09:00');

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/staff", ['staff_id' => $mech->id])
        ->assertOk()
        ->assertJson(['success' => true, 'staff_name' => 'Mang Kardo']);

    expect($booking->refresh()->staff_id)->toBe($mech->id);
});

it('clears an assignment', function () {
    $mech    = mechanic('Mang Kardo');
    $booking = jobAt('09:00', 60, $mech->id);

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/staff", ['staff_id' => null])
        ->assertOk();

    expect($booking->refresh()->staff_id)->toBeNull();
});

it('refuses a staff_id that is not a staff user', function (string $role) {
    $notStaff = User::factory()->create(['role' => $role]);
    $booking  = jobAt('09:00');

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/staff", ['staff_id' => $notStaff->id])
        ->assertStatus(422);

    expect($booking->refresh()->staff_id)->toBeNull();
})->with(['customer', 'admin']);

it('warns when the mechanic already has an overlapping booking', function () {
    $mech = mechanic('Mang Kardo');
    $date = today()->addDay()->toDateString();

    jobAt('09:00', 120, $mech->id, $date);          // 09:00–11:00
    $second = jobAt('10:00', 60, null, $date);      // 10:00–11:00, overlaps

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$second->id}/staff", ['staff_id' => $mech->id])
        ->assertStatus(409)
        ->assertJson(['conflict' => true]);

    expect($second->refresh()->staff_id)->toBeNull();
});

it('allows the double-booking when it is explicitly forced', function () {
    $mech = mechanic('Mang Kardo');
    $date = today()->addDay()->toDateString();

    jobAt('09:00', 120, $mech->id, $date);
    $second = jobAt('10:00', 60, null, $date);

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$second->id}/staff", ['staff_id' => $mech->id, 'force' => true])
        ->assertOk();

    expect($second->refresh()->staff_id)->toBe($mech->id);
});

it('does not call back-to-back bookings a conflict', function () {
    $mech = mechanic('Mang Kardo');
    $date = today()->addDay()->toDateString();

    jobAt('09:00', 60, $mech->id, $date);           // ends 10:00
    $second = jobAt('10:00', 60, null, $date);      // starts 10:00 — touching, not overlapping

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$second->id}/staff", ['staff_id' => $mech->id])
        ->assertOk();

    expect($second->refresh()->staff_id)->toBe($mech->id);
});

it('does not treat a booking as clashing with itself', function () {
    $mech    = mechanic('Mang Kardo');
    $booking = jobAt('09:00', 60, $mech->id);

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/staff", ['staff_id' => $mech->id])
        ->assertOk();
});

it('ignores a cancelled booking when checking for clashes', function () {
    $mech = mechanic('Mang Kardo');
    $date = today()->addDay()->toDateString();

    $old = jobAt('09:00', 120, $mech->id, $date);
    $old->update(['status' => 'cancelled']);

    $second = jobAt('10:00', 60, null, $date);

    $this->actingAs(assignAdmin())
        ->patchJson("/admin/bookings/{$second->id}/staff", ['staff_id' => $mech->id])
        ->assertOk();
});

it('blocks an overlapping assignment made through the edit form', function () {
    $mech = mechanic('Mang Kardo');
    $date = today()->addDay()->toDateString();

    jobAt('09:00', 120, $mech->id, $date);
    $second = jobAt('10:00', 60, null, $date);

    $this->actingAs(assignAdmin())
        ->put("/admin/bookings/{$second->id}", [
            'service_ids'  => [$second->service_id],
            'booking_date' => $date,
            'booking_time' => '10:00',
            'status'       => 'confirmed',
            'staff_id'     => $mech->id,
        ])
        ->assertSessionHasErrors('staff_id');

    expect($second->refresh()->staff_id)->toBeNull();
});

it('offers only active staff for assignment', function () {
    mechanic('Active Mech');
    $gone = mechanic('Departed Mech');
    $gone->update(['is_active' => false]);
    User::factory()->create(['role' => 'customer', 'name' => 'Not A Mech']);

    $names = User::staffMembers()->pluck('name')->all();

    expect($names)->toContain('Active Mech');
    expect($names)->not->toContain('Departed Mech');
    expect($names)->not->toContain('Not A Mech');
});

it('shows the mechanic and lets the list filter by them', function () {
    $mech = mechanic('Mang Kardo');
    jobAt('09:00', 60, $mech->id);

    $this->actingAs(assignAdmin())->get('/admin/bookings')->assertOk()
        ->assertSee('Mechanic')
        ->assertSee('Mang Kardo')
        ->assertSee('filterStaff', false);
});

it('is closed to customers', function () {
    $booking = jobAt('09:00');
    $mech    = mechanic('Mang Kardo');

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->patchJson("/admin/bookings/{$booking->id}/staff", ['staff_id' => $mech->id])
        ->assertStatus(403);

    expect($booking->refresh()->staff_id)->toBeNull();
});

it('reuses the same overlap rule as capacity', function () {
    // Touching intervals do not overlap; crossing ones do. Same rule either way.
    $mech = mechanic('Mang Kardo');
    $date = today()->addDay()->toDateString();
    jobAt('09:00', 60, $mech->id, $date);

    $availability = app(BookingAvailability::class);

    [$s1, $e1] = $availability->windowFor($date, '10:00', 30);
    expect($availability->staffConflict($mech->id, $date, $s1, $e1))->toBeNull();

    [$s2, $e2] = $availability->windowFor($date, '09:30', 30);
    expect($availability->staffConflict($mech->id, $date, $s2, $e2))->not->toBeNull();
});
