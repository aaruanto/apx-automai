<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The "Mark as arrived" gate.
 *
 * Refusals used to come back as a 404 with an HTML body, which the caller
 * could not parse, so an invalid transition was silently swallowed. These lock
 * in the status codes the UI depends on.
 *
 * What this action means changed with the six-status ladder. It used to stamp
 * arrived_at AND set the status straight to in_progress, so a car checked in
 * at the counter was reported as being worked on before anybody had looked at
 * it. Arrived and Vehicle Inspection sit between those two now, and this
 * action is the step onto the first of them. Service In Progress is reached
 * from the queue board, by whoever actually starts the work.
 *
 * arrived_at stays, and both are still written here: it is what the queue
 * board orders by — the one piece of information that says who was here first,
 * which a status cannot — so there is still exactly one way to mark arrival.
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
        'status'           => BookingStatus::BookingConfirmed,
        'reference_number' => 'APX-'.uniqid(),
    ], $attributes));
}

function admin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('marks a confirmed booking arrived', function () {
    $booking = makeBooking(['status' => BookingStatus::BookingConfirmed]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'arrived']);

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Arrived);
    expect($booking->arrived_at)->not->toBeNull();
});

it('does not start the work, which is a separate step', function () {
    // The whole reason Arrived exists. A car at the counter is not a car on
    // the ramp, and reporting it as one made the old In Progress figure a
    // count of everything that had turned up.
    $booking = makeBooking(['status' => BookingStatus::BookingConfirmed]);

    $this->actingAs(admin())->patchJson("/admin/bookings/{$booking->id}/arrive")->assertOk();

    expect($booking->refresh()->status)->not->toBe(BookingStatus::ServiceInProgress);
});

it('marks a pending booking arrived', function () {
    // A walk-in the shop has not reviewed yet still physically arrives.
    $booking = makeBooking(['status' => BookingStatus::PendingReview]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk();

    expect($booking->refresh()->status)->toBe(BookingStatus::Arrived);
});

it('stamps the arrival time, which the queue board orders by', function () {
    $booking = makeBooking(['status' => BookingStatus::BookingConfirmed]);

    expect($booking->arrived_at)->toBeNull();

    $this->actingAs(admin())->patchJson("/admin/bookings/{$booking->id}/arrive")->assertOk();

    expect($booking->refresh()->arrived_at)->not->toBeNull();
});

it('refuses a booking that has already arrived or moved past it', function (string $status) {
    $booking = makeBooking(['status' => $status]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    // Unchanged, and arrived_at was not stamped.
    expect($booking->refresh()->status)->toBe(BookingStatus::from($status));
    expect($booking->arrived_at)->toBeNull();
})->with([
    'arrived'             => ['arrived'],
    'vehicle inspection'  => ['vehicle_inspection'],
    'service in progress' => ['service_in_progress'],
    'service completed'   => ['service_completed'],
    'cancelled'           => ['cancelled'],
]);

it('explains why the transition was refused', function () {
    $booking = makeBooking(['status' => BookingStatus::Cancelled]);

    $response = $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertStatus(422);

    expect($response->json('message'))->toContain('Cancelled');
});

it('marks a future-dated booking arrived', function () {
    // Bookings are made ahead of time, so the appointment date says nothing
    // about whether the customer is at the counter. Gating on it hid the
    // button from nearly every booking in practice.
    $booking = makeBooking([
        'status'       => BookingStatus::BookingConfirmed,
        'booking_date' => nextWeekdayAfter(3),
    ]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk();

    expect($booking->refresh()->status)->toBe(BookingStatus::Arrived);
});

it('marks a past-dated booking arrived, for late arrivals', function () {
    $booking = makeBooking([
        'status'       => BookingStatus::BookingConfirmed,
        'booking_date' => today()->subDay()->toDateString(),
    ]);

    $this->actingAs(admin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk();

    expect($booking->refresh()->status)->toBe(BookingStatus::Arrived);
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

    expect($booking->refresh()->status)->toBe(BookingStatus::BookingConfirmed);
});

it('mirrors the gate in canStart, which the views use to render the button', function () {
    expect(makeBooking(['status' => BookingStatus::BookingConfirmed])->canStart())->toBeTrue();
    expect(makeBooking(['status' => BookingStatus::PendingReview])->canStart())->toBeTrue();
    expect(makeBooking(['status' => BookingStatus::Arrived])->canStart())->toBeFalse();
    expect(makeBooking(['status' => BookingStatus::VehicleInspection])->canStart())->toBeFalse();
    expect(makeBooking(['status' => BookingStatus::ServiceInProgress])->canStart())->toBeFalse();
    expect(makeBooking(['status' => BookingStatus::ServiceCompleted])->canStart())->toBeFalse();
    expect(makeBooking(['status' => BookingStatus::Cancelled])->canStart())->toBeFalse();
    // Date is not part of the rule.
    expect(makeBooking([
        'status' => BookingStatus::BookingConfirmed, 'booking_date' => today()->addDay()->toDateString(),
    ])->canStart())->toBeTrue();
});
