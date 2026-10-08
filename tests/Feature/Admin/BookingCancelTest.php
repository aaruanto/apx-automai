<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Cancelling used to write the status alone. The cancel_reason, cancelled_by
 * and cancelled_at columns existed from the start but only the no-show job
 * ever filled them, so a cancelled booking carried no record of who cancelled
 * it or why — and the admin status dropdown offered a route to cancelled that
 * skipped the reason entirely.
 */

function cancelAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'name' => 'Admin Person']);
}

function bookingFor(string $status = 'confirmed'): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'CNL '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Cancel Service '.$unique, 'price' => 1500, 'duration' => 45]);

    return Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => today()->toDateString(), 'booking_time' => '10:00',
        'status' => $status, 'notes' => 'Customer asked for a wash too.',
        'reference_number' => 'APX-'.$unique,
    ]);
}

it('records the reason, who cancelled and when', function () {
    $booking = bookingFor();
    $admin   = cancelAdmin();

    $this->actingAs($admin)
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Customer rescheduled by phone'])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'cancelled']);

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
    expect($booking->cancel_reason)->toBe('Customer rescheduled by phone');
    expect($booking->cancelled_by)->toBe($admin->id);
    expect($booking->cancelled_at)->not->toBeNull();
});

it('keeps every other field intact', function () {
    $booking = bookingFor();
    $before  = $booking->only(['user_id', 'vehicle_id', 'service_id', 'booking_date', 'booking_time', 'notes', 'reference_number']);

    $this->actingAs(cancelAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Parts delayed'])
        ->assertOk();

    // The complaint was that cancelling "loses the booking's information".
    expect($booking->refresh()->only(array_keys($before)))->toBe($before);
});

it('refuses to cancel without a reason', function (array $payload) {
    $booking = bookingFor();

    $this->actingAs(cancelAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", $payload)
        ->assertStatus(422);

    expect($booking->refresh()->status)->toBe('confirmed');
})->with([
    'missing'   => [[]],
    'empty'     => [['reason' => '']],
    'too short' => [['reason' => 'x']],
]);

it('refuses to cancel a booking that is already cancelled or completed', function (string $status) {
    $booking = bookingFor($status);

    $this->actingAs(cancelAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Changed my mind'])
        ->assertStatus(422);
})->with(['cancelled', 'completed']);

it('cannot reach cancelled through the edit form status dropdown', function () {
    // The side door: this bypassed the required reason entirely.
    $booking = bookingFor();

    $this->actingAs(cancelAdmin())
        ->put("/admin/bookings/{$booking->id}", [
            'user_id'      => $booking->user_id,
            'vehicle_id'   => $booking->vehicle_id,
            'service_id'   => $booking->service_id,
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'status'       => 'cancelled',
        ])
        ->assertSessionHasErrors('status');

    expect($booking->refresh()->status)->toBe('confirmed');
    expect($booking->cancel_reason)->toBeNull();
});

it('still allows the other statuses through the edit form', function () {
    $booking = bookingFor('pending');

    $this->actingAs(cancelAdmin())
        ->put("/admin/bookings/{$booking->id}", [
            'user_id'      => $booking->user_id,
            'vehicle_id'   => $booking->vehicle_id,
            'service_id'   => $booking->service_id,
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'status'       => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect($booking->refresh()->status)->toBe('confirmed');
});

it('keeps cancelled bookings in the admin list and on the cancelled page', function () {
    $booking = bookingFor();

    $this->actingAs(cancelAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Shop closed that day']);

    // Visible in both places, with the reason shown.
    $this->actingAs(cancelAdmin())->get('/admin/bookings')->assertOk()
        ->assertSee($booking->reference_number);

    $this->actingAs(cancelAdmin())->get('/admin/bookings/cancelled')->assertOk()
        ->assertSee($booking->reference_number)
        ->assertSee('Shop closed that day')
        ->assertSee('Admin Person');
});

it('records the customer as the canceller when they cancel their own booking', function () {
    $booking  = bookingFor('pending');
    $customer = User::find($booking->user_id);

    $this->actingAs($customer)
        ->patchJson("/customer/bookings/{$booking->id}/cancel", ['reason' => 'Car sold'])
        ->assertOk();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
    expect($booking->cancelled_by)->toBe($customer->id);
    expect($booking->cancel_reason)->toBe('Car sold');
});

it('still records a customer cancellation that gives no reason', function () {
    $booking  = bookingFor('pending');
    $customer = User::find($booking->user_id);

    $this->actingAs($customer)
        ->patchJson("/customer/bookings/{$booking->id}/cancel")
        ->assertOk();

    expect($booking->refresh()->cancel_reason)->toBe('Cancelled by customer');
});

it('will not let a customer cancel somebody elses booking', function () {
    $booking   = bookingFor();
    $outsider  = User::factory()->create(['role' => 'customer']);

    $this->actingAs($outsider)
        ->patchJson("/customer/bookings/{$booking->id}/cancel", ['reason' => 'Not mine'])
        ->assertStatus(404);

    expect($booking->refresh()->status)->toBe('confirmed');
});

it('frees the slot but stays in reporting', function () {
    $booking = bookingFor();

    $this->actingAs(cancelAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Double booked']);

    // Counted as cancelled in reports rather than vanishing from history.
    $response = $this->actingAs(cancelAdmin())->get('/admin/reports')->assertOk();
    expect($response->viewData('statusCounts')['cancelled'])->toBe(1);
    expect($response->viewData('totalBookings'))->toBe(1);
});
