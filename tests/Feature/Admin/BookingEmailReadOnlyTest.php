<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The email address on the booking edit screen is shown, not edited.
 *
 * Full Name and Phone Number were made read-only here once already, because
 * update() never read them: an admin could type a new value, save, be told it
 * worked, and find nothing had changed. The Email Address field was missed in
 * that pass and stayed an ordinary input — hovering it offered a caret, and
 * typing into it did exactly as much as typing into the other two did.
 *
 * Same rule as the customer screen, for the same reason: the address is the
 * customer's login and where every confirmation and reminder lands, so it is
 * theirs to change. These tests hold both halves — the field is not offered on
 * edit, and posting one anyway changes nothing — plus the half that must keep
 * working, since store() genuinely uses customer_email to give a walk-in an
 * account.
 */
function beroAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function beroBooking(string $email, string $status = 'pending'): Booking
{
    static $n = 1;

    $customer = User::factory()->create([
        'role'  => 'customer',
        'name'  => 'Randy Galero',
        'phone' => '0908716577'.$n,
        'email' => $email,
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'plate_number' => 'BER '.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
    ]);

    $service = Service::create(['name' => 'Email RO '.$n, 'price' => 1200, 'duration' => 60]);

    $booking = Booking::create([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => nextWeekday(),
        'booking_time'     => '09:00',
        'duration'         => 60,
        'status'           => $status,
        'reference_number' => 'APX-BERO-'.$n++,
    ]);

    $booking->services()->sync([$service->id => ['price' => 1200, 'duration' => 60]]);

    return $booking;
}

it('shows the address on the booking edit screen without offering to change it', function () {
    $booking = beroBooking('ranmichgalero@gmail.com');

    $html = $this->actingAs(beroAdmin())
        ->get("/admin/bookings/{$booking->id}/edit")
        ->assertOk()
        ->getContent();

    // Readable, because an admin needs to know where the confirmation went...
    expect($html)->toContain('ranmichgalero@gmail.com');

    // ...but not as a field. Posting it would be ignored, so inviting it is
    // the whole fault: the form promised an edit the server never performed.
    expect($html)->not->toContain('name="customer_email"');
});

it('greys the address out the same way as the name and the phone', function () {
    $booking = beroBooking('greyed@gmail.com');

    $html = $this->actingAs(beroAdmin())
        ->get("/admin/bookings/{$booking->id}/edit")
        ->assertOk()
        ->getContent();

    // Three disabled inputs in Customer Information, not two. The cursor is
    // what gave the old field away — it looked typeable on hover.
    expect(substr_count($html, 'cursor:not-allowed'))->toBeGreaterThanOrEqual(3);
    expect($html)->toContain('changes their name, phone and email');
});

it('still offers the field when booking for a walk-in, which has no account yet', function () {
    $html = $this->actingAs(beroAdmin())
        ->get('/admin/bookings/create')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('name="customer_email"');
});

it('does not change the address even when one is posted directly', function () {
    $booking = beroBooking('owner@gmail.com');

    // Removing the input is not the guard. update() never reading it is.
    $this->actingAs(beroAdmin())->put("/admin/bookings/{$booking->id}", [
        'service_ids'    => [$booking->service_id],
        'booking_date'   => $booking->booking_date instanceof \DateTimeInterface
                                ? $booking->booking_date->format('Y-m-d')
                                : $booking->booking_date,
        'booking_time'   => '09:00',
        'status'         => 'pending',
        'notes'          => 'Checked in by phone.',
        'customer_email' => 'attacker@evil.test',
        'customer_name'  => 'Someone Else',
        'customer_phone' => '09998887777',
    ])->assertRedirect();

    $customer = $booking->user->fresh();

    expect($customer->email)->toBe('owner@gmail.com');
    expect($customer->name)->toBe('Randy Galero');
    expect($booking->fresh()->notes)->toBe('Checked in by phone.');
});
