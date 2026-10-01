<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The "Arrived / Start Service" gate. Refusals used to come back as a 404 with
 * an HTML body, which the caller could not parse, so an invalid transition was
 * silently swallowed. These lock in the status codes the UI depends on.
 */

function makeBooking(array $attributes = []): Booking
{
    // plate_number and services.name are unique, so every fixture needs its own.
    $unique = substr(str_replace('.', '', uniqid('', true)), -6);

    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id'      => $customer->id,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2020,
        'plate_number' => 'ABC '.$unique,
        'color'        => 'Red',
    ]);

    $service = Service::create([
        'name'     => 'Change Oil Test '.$unique,
        'price'    => 1500,
        'duration' => 45,
    ]);

    return Booking::create(array_merge([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => today()->toDateString(),
        'booking_time'     => '10:00',
        'status'           => 'confirmed',
        'reference_number' => 'APX-'.uniqid(),
    ], $attributes));
}

function admin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('starts a confirmed booking scheduled for today', function () {
    $booking = makeBooking(['status' => 'confirmed']);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'in_progress']);

    $booking->refresh();
    expect($booking->status)->toBe('in_progress');
    expect($booking->arrived_at)->not->toBeNull();
});

it('starts a pending booking', function () {
    $booking = makeBooking(['status' => 'pending']);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk();

    expect($booking->refresh()->status)->toBe('in_progress');
});

it('refuses to start a booking that is not pending or confirmed', function (string $status) {
    $booking = makeBooking(['status' => $status]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    // Unchanged, and arrived_at was not stamped.
    expect($booking->refresh()->status)->toBe($status);
    expect($booking->arrived_at)->toBeNull();
})->with(['in_progress', 'completed', 'cancelled']);

it('explains why the transition was refused', function () {
    $booking = makeBooking(['status' => 'cancelled']);

    $response = $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertStatus(422);

    expect($response->json('message'))->toContain('cancelled');
});

it('refuses to start a booking scheduled for a future date', function () {
    $booking = makeBooking([
        'status'       => 'confirmed',
        'booking_date' => today()->addDays(3)->toDateString(),
    ]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertStatus(422);

    expect($booking->refresh()->status)->toBe('confirmed');
});

it('still starts a past-dated booking, for late arrivals', function () {
    $booking = makeBooking([
        'status'       => 'confirmed',
        'booking_date' => today()->subDay()->toDateString(),
    ]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk();

    expect($booking->refresh()->status)->toBe('in_progress');
});

it('404s for a booking that does not exist', function () {
    $this->actingAs(admin())
        ->patchJson('/admin/bookings/999999/arrive')
        ->assertStatus(404);
});

it('denies customers and guests', function () {
    $booking = makeBooking();

    $this->patchJson("/admin/bookings/{$booking->id}/arrive")->assertStatus(401);

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertStatus(403);

    expect($booking->refresh()->status)->toBe('confirmed');
});

it('mirrors the gate in canStart, which the views use to render the button', function () {
    expect(makeBooking(['status' => 'confirmed'])->canStart())->toBeTrue();
    expect(makeBooking(['status' => 'pending'])->canStart())->toBeTrue();
    expect(makeBooking(['status' => 'completed'])->canStart())->toBeFalse();
    expect(makeBooking(['status' => 'cancelled'])->canStart())->toBeFalse();
    expect(makeBooking(['status' => 'in_progress'])->canStart())->toBeFalse();
    expect(makeBooking([
        'status' => 'confirmed', 'booking_date' => today()->addDay()->toDateString(),
    ])->canStart())->toBeFalse();
});
