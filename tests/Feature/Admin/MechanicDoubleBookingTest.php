<?php

use App\Models\Booking;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Creating a booking must not double-book a mechanic in silence.
 *
 * reserve() checks opening hours, shop capacity and the vehicle, and never the
 * mechanic. update() and assignMechanic() each ran mechanicConflict()
 * themselves; store() ran neither — so the one path that creates bookings was
 * the one path with no mechanic check at all, and a rebook goes through it.
 *
 * Refused rather than offered, matching update(): both are form posts landing
 * back on a filled-in form. assignMechanic() asks "assign anyway?" because it
 * is one deliberate reassignment on a booking that already exists.
 */

function mdbAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function mdbService(int $duration = 60): Service
{
    static $n = 1;

    return Service::create(['name' => 'Mech '.$n++, 'price' => 1200, 'duration' => $duration]);
}

function mdbPayload(Service $service, ?Mechanic $mechanic, string $time, string $phone, string $plate): array
{
    return array_filter([
        'customer_name'  => 'Walk In',
        'customer_phone' => $phone,
        'plate'          => $plate,
        'service_id'     => $service->id,
        'mechanic_id'    => $mechanic?->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => $time,
    ], fn ($v) => $v !== null);
}

it('refuses to create a booking on a mechanic who is already busy', function () {
    $admin    = mdbAdmin();
    $mechanic = Mechanic::create(['name' => 'Ramon Cruz', 'status' => 'active']);
    $service  = mdbService(60);

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '10:00', '09170001111', 'MDB 0001'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // 10:30 lands inside 10:00–11:00 for the same mechanic.
    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '10:30', '09170002222', 'MDB 0002'))
        ->assertSessionHasErrors('mechanic_id');

    expect(Booking::count())->toBe(1);
});

it('names the booking the mechanic is already on', function () {
    $admin    = mdbAdmin();
    $mechanic = Mechanic::create(['name' => 'Ramon Cruz', 'status' => 'active']);
    $service  = mdbService(60);

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '10:00', '09170001111', 'MDB 0011'));

    $first = Booking::sole();

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '10:30', '09170002222', 'MDB 0012'));

    $message = session('errors')->first('mechanic_id');

    expect($message)->toContain($first->reference_number);
    expect($message)->toContain('10:00 AM');
    expect($message)->toContain('Choose another, or change the time.');
});

it('allows the same mechanic at a time that does not overlap', function () {
    $admin    = mdbAdmin();
    $mechanic = Mechanic::create(['name' => 'Ramon Cruz', 'status' => 'active']);
    $service  = mdbService(60);

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '10:00', '09170001111', 'MDB 0021'))
        ->assertSessionHasNoErrors();

    // 11:00 starts as the first ends. Touching is not overlapping.
    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '11:00', '09170002222', 'MDB 0022'))
        ->assertSessionHasNoErrors();

    expect(Booking::count())->toBe(2);
});

it('allows a different mechanic at the same time', function () {
    $admin   = mdbAdmin();
    $one     = Mechanic::create(['name' => 'Ramon Cruz', 'status' => 'active']);
    $two     = Mechanic::create(['name' => 'Ana Reyes', 'status' => 'active']);
    $service = mdbService(60);

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $one, '10:00', '09170001111', 'MDB 0031'))
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $two, '10:00', '09170002222', 'MDB 0032'))
        ->assertSessionHasNoErrors();

    expect(Booking::count())->toBe(2);
});

it('says nothing when no mechanic was chosen', function () {
    $admin   = mdbAdmin();
    $service = mdbService(60);

    // Unassigned is the normal case — a rebook starts that way on purpose.
    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, null, '10:00', '09170001111', 'MDB 0041'))
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, null, '10:00', '09170002222', 'MDB 0042'))
        ->assertSessionHasNoErrors();

    expect(Booking::count())->toBe(2);
});

it('does not count a cancelled booking as the mechanic being busy', function () {
    $admin    = mdbAdmin();
    $mechanic = Mechanic::create(['name' => 'Ramon Cruz', 'status' => 'active']);
    $service  = mdbService(60);

    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '10:00', '09170001111', 'MDB 0051'));

    app(\App\Services\BookingCancellation::class)->cancel(Booking::sole(), 'Customer called off', $admin->id);

    // mechanicConflict only counts ACTIVE_STATUSES, so the slot is free again.
    $this->actingAs($admin)
        ->post('/admin/bookings', mdbPayload($service, $mechanic, '10:00', '09170002222', 'MDB 0052'))
        ->assertSessionHasNoErrors();

    expect(Booking::where('status', 'pending')->count())->toBe(1);
});
