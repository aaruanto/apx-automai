<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Only a cancelled booking can be rebooked.
 *
 * The rebook route is a plain GET with the id in the address bar, and the only
 * link to it is the button on the cancelled list — so the list was the guard.
 * A typed URL, a bookmark or a stale tab walks past it. Rebooking a live
 * booking prefilled the form from it and created a second booking for the same
 * customer and the same car; on a different date the same-day warning and the
 * overlap check both pass, and the customer quietly ends up with two. A
 * completed booking is a record of work already done.
 */

function rbAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function rbBooking(string $status): Booking
{
    static $n = 1;

    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'plate_number' => 'RBK '.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
    ]);
    $service = Service::create(['name' => 'Rebook Guard '.$n, 'price' => 1200, 'duration' => 60]);

    $booking = Booking::create([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => nextWeekdayAfter(3),
        'booking_time'     => '14:00',
        'duration'         => 60,
        'status'           => $status,
        'reference_number' => 'APX-RBK-'.$n++,
    ]);

    $booking->services()->sync([$service->id => ['price' => 1200, 'duration' => 60]]);

    return $booking;
}

it('opens the rebook form for a cancelled booking', function () {
    $booking = rbBooking('cancelled');

    $this->actingAs(rbAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()
        ->assertSee('Rebook', escape: false);
});

it('refuses to rebook a live booking', function (string $status) {
    $booking = rbBooking($status);

    $this->actingAs(rbAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertRedirect(route('admin.bookings.cancelled'))
        ->assertSessionHas('error');

    // Refused, and nothing created — the form never rendered to be submitted.
    expect(Booking::count())->toBe(1);
})->with([
    'pending'     => ['pending'],
    'confirmed'   => ['confirmed'],
    'in progress' => ['in_progress'],
]);

it('refuses to rebook a completed booking', function () {
    $booking = rbBooking('completed');

    $this->actingAs(rbAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertRedirect(route('admin.bookings.cancelled'))
        ->assertSessionHas('error');

    expect(Booking::count())->toBe(1);
});

it('names the booking and says what state it is actually in', function () {
    $booking = rbBooking('confirmed');

    $this->actingAs(rbAdmin())->get('/admin/bookings/'.$booking->id.'/rebook');

    $message = session('error');

    expect($message)->toContain($booking->reference_number);
    expect($message)->toContain('Confirmed');
    expect($message)->toContain('not cancelled');

    // And what to do instead, rather than only what went wrong.
    expect($message)->toContain('cancel it first');
});

it('tells the admin a finished job is a record, not a starting point', function () {
    $booking = rbBooking('completed');

    $this->actingAs(rbAdmin())->get('/admin/bookings/'.$booking->id.'/rebook');

    expect(session('error'))->toContain('work already done');
});

it('shows the refusal on the page it redirects to', function () {
    $booking = rbBooking('confirmed');

    // A message flashed to a page that renders no messages is a silent
    // refusal. Only Settings and the bundle list had the block; this one did
    // not, so the admin would have been bounced back with no explanation.
    $html = $this->actingAs(rbAdmin())
        ->followingRedirects()
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('ApxAlertModal.show');
    expect($html)->toContain($booking->reference_number);
});
