<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * One booking, several services: durations sum into a single block and prices
 * sum into one total. Guest booking already accepted several but could only
 * store one, appending the rest to the notes field as text.
 */

function svc(string $name, int $price, int $duration): Service
{
    return Service::create(['name' => $name, 'price' => $price, 'duration' => $duration]);
}

function bookingCustomer(): array
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -6);
    $user   = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2021, 'plate_number' => 'MSV '.$unique, 'color' => 'Blue',
    ]);

    return [$user, $vehicle];
}

it('sums duration and price across every attached service', function () {
    [$user, $vehicle] = bookingCustomer();
    $a = svc('Oil', 1500, 45);
    $b = svc('Brakes', 2500, 60);

    $booking = app(BookingAvailability::class)->reserve([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id,
        'service_ids' => [$a->id, $b->id],
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '10:00', 'status' => 'pending',
    ]);

    $booking->load('services');
    expect($booking->services)->toHaveCount(2);
    expect($booking->total_duration)->toBe(105);
    expect($booking->total_price)->toBe(4000.0);
    expect($booking->duration)->toBe(105);          // the block actually held
    expect($booking->service_id)->toBe($a->id);     // first selection kept
});

it('snapshots price and duration so later changes do not rewrite history', function () {
    [$user, $vehicle] = bookingCustomer();
    $a = svc('Oil', 1500, 45);

    $booking = app(BookingAvailability::class)->reserve([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id,
        'service_ids' => [$a->id],
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '10:00', 'status' => 'pending',
    ]);

    $a->update(['price' => 9999, 'duration' => 300]);

    $booking->load('services');
    expect($booking->total_price)->toBe(1500.0);
    expect($booking->total_duration)->toBe(45);
});

it('holds a bay for the summed duration, not just the first service', function () {
    // 3 bays. Three 2-hour bookings at 09:00 fill the shop, so a fourth
    // overlapping one must be refused — proving the long block is really held.
    [$user, $vehicle] = bookingCustomer();
    $long = [svc('A', 100, 60)->id, svc('B', 100, 60)->id];
    $date = today()->addDay()->toDateString();

    foreach (range(1, 3) as $i) {
        app(BookingAvailability::class)->reserve([
            'user_id' => $user->id, 'vehicle_id' => $vehicle->id,
            'service_ids' => $long, 'booking_date' => $date,
            'booking_time' => '09:00', 'status' => 'pending',
        ]);
    }

    // 10:00 falls inside the 09:00–11:00 block of all three.
    expect(fn () => app(BookingAvailability::class)->reserve([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id,
        'service_ids' => $long, 'booking_date' => $date,
        'booking_time' => '10:00', 'status' => 'pending',
    ]))->toThrow(App\Exceptions\SlotUnavailableException::class);
});

it('lets a customer book several services at once', function () {
    [$user, $vehicle] = bookingCustomer();
    $a = svc('Oil', 1500, 45);
    $b = svc('Wash', 0, 30);

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids'  => [$a->id, $b->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '11:00',
    ])->assertOk();

    $booking = Booking::latest('id')->first()->load('services');
    expect($booking->services->pluck('id')->sort()->values()->all())
        ->toBe(collect([$a->id, $b->id])->sort()->values()->all());
    expect($booking->total_duration)->toBe(75);
});

it('still accepts a single service_id from an older client', function () {
    [$user, $vehicle] = bookingCustomer();
    $a = svc('Oil', 1500, 45);

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_id'   => $a->id,
        'vehicle_id'   => $vehicle->id,
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '11:00',
    ])->assertOk();

    expect(Booking::latest('id')->first()->load('services')->services)->toHaveCount(1);
});

it('rejects a booking with no services at all', function () {
    [$user, $vehicle] = bookingCustomer();

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids'  => [],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '11:00',
    ])->assertStatus(422);
});

it('records every service on a guest booking instead of in the notes', function () {
    $a = svc('Oil', 1500, 45);
    $b = svc('Brakes', 2500, 60);

    $this->postJson('/booking/guest', [
        'guest_name'   => 'Walk In',
        'guest_email'  => 'walkin'.uniqid().'@example.com',
        'guest_phone'   => '09171234567',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'GST 1234',
        'service_ids'  => [$a->id, $b->id],
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '13:00',
        'notes'        => 'Please check the aircon.',
    ])->assertOk();

    $booking = Booking::latest('id')->first()->load('services');
    expect($booking->services)->toHaveCount(2);
    expect($booking->total_duration)->toBe(105);

    // The note is the customer's own text again.
    expect($booking->notes)->toBe('Please check the aircon.');
    expect($booking->notes)->not->toContain('Additional services');
});

it('syncs services when an admin edits a booking', function () {
    [$user, $vehicle] = bookingCustomer();
    $a = svc('Oil', 1500, 45);
    $b = svc('Brakes', 2500, 60);
    $c = svc('Tune', 3500, 90);

    $booking = app(BookingAvailability::class)->reserve([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id,
        'service_ids' => [$a->id], 'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '09:00', 'status' => 'pending',
    ]);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put("/admin/bookings/{$booking->id}", [
            'service_ids'  => [$b->id, $c->id],
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'status'       => 'confirmed',
        ])->assertSessionHasNoErrors();

    $booking->refresh()->load('services');
    expect($booking->services->pluck('id')->sort()->values()->all())
        ->toBe(collect([$b->id, $c->id])->sort()->values()->all());
    expect($booking->total_duration)->toBe(150);
    expect($booking->duration)->toBe(150);   // the held block grew with it
});

it('shows every service wherever a booking is listed', function () {
    [$user, $vehicle] = bookingCustomer();
    $a = svc('Change Oil', 1500, 45);
    $b = svc('Brake Pads', 2500, 60);

    app(BookingAvailability::class)->reserve([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id,
        'service_ids' => [$a->id, $b->id],
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '09:00', 'status' => 'confirmed',
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/bookings')->assertOk()
        ->assertSee('Change Oil + Brake Pads');

    $this->actingAs($user)->get('/customer/dashboard')->assertOk();
});
