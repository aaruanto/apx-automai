<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The status dropdown and the status validation agree about 'cancelled'.
 *
 * update() validated in:pending,confirmed,in_progress,completed — cancelling
 * demands a reason and has its own action — but the dropdown on the edit form
 * went on offering Cancelled as a fifth option. The two disagreed, with two
 * faces:
 *
 *  1. Choosing Cancelled on a live booking posted a value the server refuses,
 *     so the form came back "The selected status is invalid" having saved
 *     nothing. The admin had picked the only option that looked like
 *     cancelling and was told their input was wrong.
 *
 *  2. Worse, and unreported: a booking that was *already* cancelled could not
 *     be saved at all. Its select correctly showed Cancelled, so submitting
 *     the form unchanged — just to correct a note — posted 'cancelled' and hit
 *     the same refusal. The edit screen for every cancelled booking was dead.
 *
 * The ladder is the source of truth, so the dropdown is built from it. The one
 * exception is a cancelled booking, which keeps its own status as the only
 * value it may show or post, because it cannot move anywhere and the form
 * still has to submit.
 */

function statusAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function statusBooking(string $status = 'pending'): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'STS '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Status Service '.$unique, 'price' => 1500, 'duration' => 45]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        'status' => $status, 'notes' => 'Original note.', 'duration' => 45,
        'reference_number' => 'APX-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => $service->price]);

    return $booking;
}

it('does not offer cancelled on a live booking', function () {
    $booking = statusBooking('pending');

    $html = $this->actingAs(statusAdmin())
        ->get("/admin/bookings/{$booking->id}/edit")->assertOk()->getContent();

    // The four rungs of the ladder are offered; the fifth option is not.
    foreach (['pending', 'confirmed', 'in_progress', 'completed'] as $rung) {
        expect($html)->toContain('<option value="'.$rung.'"');
    }
    expect($html)->not->toContain('<option value="cancelled"');

    // And the admin is told where cancelling actually lives, rather than being
    // left to hunt for the option that was removed.
    expect($html)->toContain('Cancel This Booking');
});

it('still shows a cancelled booking its own status', function () {
    $booking = statusBooking('cancelled');

    $html = $this->actingAs(statusAdmin())
        ->get("/admin/bookings/{$booking->id}/edit")->assertOk()->getContent();

    // Without this the select falls back to its first option and the screen
    // reports a cancelled booking as Pending.
    expect($html)->toContain('<option value="cancelled"');
    expect($html)->not->toContain('<option value="pending"');
});

it('refuses to cancel a live booking through the dropdown', function () {
    $booking = statusBooking('confirmed');

    $this->actingAs(statusAdmin())
        ->put("/admin/bookings/{$booking->id}", [
            'service_ids'  => [$booking->service_id],
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'status'       => 'cancelled',
            'notes'        => 'Trying to cancel the quick way.',
        ])
        ->assertSessionHasErrors('status');

    // The reason-capturing action is the only way out, so the booking stands.
    expect($booking->refresh()->status)->toBe('confirmed');
    expect($booking->cancel_reason)->toBeNull();
});

it('saves the notes on a cancelled booking instead of refusing the form', function () {
    $booking = statusBooking('cancelled');

    $this->actingAs(statusAdmin())
        ->put("/admin/bookings/{$booking->id}", [
            'service_ids'  => [$booking->service_id],
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'status'       => 'cancelled',
            'notes'        => 'Customer rang back — wants the same slot next week.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.bookings.cancelled'));

    $booking->refresh();
    expect($booking->notes)->toBe('Customer rang back — wants the same slot next week.');

    // Everything else about a cancelled booking stands: it keeps its status,
    // and it does not take its slot back.
    expect($booking->status)->toBe('cancelled');
});

it('does not let a cancelled booking be revived through the form', function () {
    $booking = statusBooking('cancelled');

    $this->actingAs(statusAdmin())
        ->put("/admin/bookings/{$booking->id}", [
            'service_ids'  => [$booking->service_id],
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'status'       => 'pending',
            'notes'        => 'Putting it back.',
        ])
        ->assertSessionHasErrors('status');

    expect($booking->refresh()->status)->toBe('cancelled');
});
