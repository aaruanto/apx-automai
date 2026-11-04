<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The dashboard says what each figure counts.
 *
 * Three cards, three different rules: Upcoming is pending and confirmed,
 * Completed is completed, Total is every booking on record — cancelled ones
 * included. All three were correct and none of them said so, so Upcoming plus
 * Completed never added up to Total and nothing on screen explained the gap.
 *
 * The tester hit the worst version of it. Their bookings had all been
 * cancelled, so Upcoming read 0 — no appointment count at all — beside a Total
 * that was quietly counting those same cancelled bookings. Two figures about
 * the same bookings, disagreeing, with no stated rule to reconcile them.
 *
 * Total keeps counting everything. That is already the rule behind the All tab
 * and the My Bookings badge, and View All opens exactly that list — narrowing
 * the card would have left it disagreeing with the list it links to, which is
 * the same complaint inverted. So the rule stays and the UI states it, the way
 * the admin dashboard already states its own under every figure.
 *
 * These pin the arithmetic that made the gap visible, and the captions that
 * explain it.
 */

function countsCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function countsBooking(User $customer, string $status, string $date): Booking
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -6);

    $vehicle = Vehicle::firstOrCreate(
        ['user_id' => $customer->id, 'plate_number' => 'CNT 0001'],
        ['make' => 'Toyota', 'model' => 'Vios', 'year' => 2020]
    );

    $service = Service::create(['name' => 'Count Service '.$unique, 'price' => 1000, 'duration' => 45]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => $date, 'booking_time' => '10:00', 'status' => $status,
        'duration' => 45, 'reference_number' => 'CNT-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => $service->price]);

    return $booking;
}

/** One of each, so every rule has something to disagree about. */
function customerWithAMix(): User
{
    $customer = countsCustomer();
    $dates    = nextWeekdays(5);

    countsBooking($customer, 'pending', $dates[0]);
    countsBooking($customer, 'confirmed', $dates[1]);
    countsBooking($customer, 'completed', $dates[2]);
    countsBooking($customer, 'cancelled', $dates[3]);
    countsBooking($customer, 'cancelled', $dates[4]);

    return $customer;
}

it('counts upcoming without the cancelled ones', function () {
    $html = $this->actingAs(customerWithAMix())->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->toMatch('/id="cstatUpcoming"[^>]*>\s*2\s*</');
    expect($html)->toMatch('/id="cstatCompleted"[^>]*>\s*1\s*</');
});

it('counts every booking on record in the total, cancelled included', function () {
    $html = $this->actingAs(customerWithAMix())->get('/customer/dashboard')->assertOk()->getContent();

    // 2 upcoming + 1 completed + 2 cancelled. The gap between 3 and 5 is the
    // whole reason the captions exist.
    expect($html)->toMatch('/id="cstatTotal"[^>]*>\s*5\s*</');
});

it('keeps the total card agreeing with the list View All opens', function () {
    $html = $this->actingAs(customerWithAMix())->get('/customer/dashboard')->assertOk()->getContent();

    // The card, the All tab and the My Bookings badge are one number. If a
    // future change narrows one of them it has to narrow all three.
    expect($html)->toMatch('/id="cstatTotal"[^>]*>\s*5\s*</');
    expect($html)->toMatch('/id="cnt-all"[^>]*>\s*5\s*</');
    expect($html)->toMatch('/id="tabCountBookings"[^>]*>\s*5\s*</');
});

it('states what each figure counts', function () {
    $html = $this->actingAs(customerWithAMix())->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->toContain('Pending and confirmed');
    expect($html)->toContain('Service finished');
    expect($html)->toContain('All time, including cancelled');
    expect($html)->toContain('Completed work only');
});

it('answers the live refresh with the same rules the page rendered', function () {
    // The cards are repainted from this endpoint, so a rule that differs here
    // would have the figures change on their own a few seconds after load.
    $this->actingAs(customerWithAMix())
        ->getJson('/customer/dashboard/live')
        ->assertOk()
        ->assertJsonPath('stats.upcoming', 2)
        ->assertJsonPath('stats.completed', 1)
        ->assertJsonPath('stats.total', 5);
});

it('shows a customer with only cancelled bookings why the two disagree', function () {
    $customer = countsCustomer();
    $dates    = nextWeekdays(2);
    countsBooking($customer, 'cancelled', $dates[0]);
    countsBooking($customer, 'cancelled', $dates[1]);

    $html = $this->actingAs($customer)->get('/customer/dashboard')->assertOk()->getContent();

    // Exactly what the tester saw: nothing upcoming, two on the total.
    expect($html)->toMatch('/id="cstatUpcoming"[^>]*>\s*0\s*</');
    expect($html)->toMatch('/id="cstatTotal"[^>]*>\s*2\s*</');

    // The difference is now accounted for on the page itself.
    expect($html)->toContain('All time, including cancelled');
});
