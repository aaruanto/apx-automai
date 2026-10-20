<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The bookings list filters.
 *
 * The date-range filter did nothing. Two <input type="date"> fields were
 * rendered, bound to the same change handler as every other filter and reset by
 * clearFilters(), so they looked wired — but applyFilters() never read them,
 * and no row carried a date for them to match against. Typing a date changed
 * nothing, which is worse than having no filter at all.
 *
 * The matching itself is client-side and cannot run under Pest. What is tested
 * here is the contract that filter depends on: every row carries its booking
 * date in the raw YYYY-MM-DD form the date input also produces, so the two can
 * be compared as strings. Remove the attribute or reformat it and these fail —
 * which is exactly how the bug got in.
 *
 * Dates are written directly rather than through nextWeekday(), because these
 * rows are inserted, not booked through the availability rules, and the
 * assertions below are about exact date strings.
 */
function filterBooking(string $date, array $attributes = []): Booking
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -6);

    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id'      => $customer->id,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2020,
        'plate_number' => 'FIL '.$unique,
        'color'        => 'Red',
    ]);

    $service = Service::create([
        'name'     => 'Filter Svc '.$unique,
        'price'    => 1500,
        'duration' => 45,
    ]);

    $booking = Booking::create(array_merge([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => $date,
        'booking_time'     => '10:00:00',
        'duration'         => 45,
        'status'           => 'confirmed',
        'reference_number' => 'APX-FIL-'.$unique,
    ], $attributes));

    $booking->services()->attach($service->id, ['price' => 1500, 'duration' => 45]);

    return $booking;
}

function filterAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('renders every booking row with its date, in the form the date input produces', function () {
    filterBooking('2026-03-14');

    $html = $this->actingAs(filterAdmin())->get('/admin/bookings')->assertOk()->getContent();

    // Not "contains 2026-03-14" — the date also appears in the human-readable
    // column. This asserts the attribute the filter actually reads.
    expect($html)->toContain('data-date="2026-03-14"');
});

it('gives each row its own date rather than repeating one', function () {
    filterBooking('2026-03-14');
    filterBooking('2026-07-02');

    $html = $this->actingAs(filterAdmin())->get('/admin/bookings')->assertOk()->getContent();

    expect($html)->toContain('data-date="2026-03-14"');
    expect($html)->toContain('data-date="2026-07-02"');
});

it('keeps the date in sortable YYYY-MM-DD form, never a formatted one', function () {
    filterBooking('2026-11-05');

    $html = $this->actingAs(filterAdmin())->get('/admin/bookings')->assertOk()->getContent();

    // A formatted date ("November 5, 2026", "05/11/2026") would still render
    // and still look right on screen, while silently breaking every string
    // comparison the filter makes.
    expect($html)->toContain('data-date="2026-11-05"');
    expect($html)->not->toContain('data-date="November');
    expect($html)->not->toContain('data-date="05/11/2026"');
});

it('still renders both date inputs the filter reads', function () {
    filterBooking('2026-03-14');

    $html = $this->actingAs(filterAdmin())->get('/admin/bookings')->assertOk()->getContent();

    expect($html)->toContain('id="filterDateFrom"');
    expect($html)->toContain('id="filterDateTo"');
});

// ── The cancelled list carries the same filter, and had the same bug ───────

it('renders the booking date on cancelled rows too', function () {
    filterBooking('2026-04-09', ['status' => 'cancelled', 'cancel_reason' => 'Customer rescheduled']);

    $html = $this->actingAs(filterAdmin())->get('/admin/bookings/cancelled')->assertOk()->getContent();

    expect($html)->toContain('data-date="2026-04-09"');
});

it('keeps cancelled row dates sortable, never formatted', function () {
    filterBooking('2026-12-25', ['status' => 'cancelled', 'cancel_reason' => 'Shop closed']);

    $html = $this->actingAs(filterAdmin())->get('/admin/bookings/cancelled')->assertOk()->getContent();

    expect($html)->toContain('data-date="2026-12-25"');
    expect($html)->not->toContain('data-date="December');
});

it('still renders both date inputs on the cancelled list', function () {
    filterBooking('2026-04-09', ['status' => 'cancelled', 'cancel_reason' => 'Customer rescheduled']);

    $html = $this->actingAs(filterAdmin())->get('/admin/bookings/cancelled')->assertOk()->getContent();

    expect($html)->toContain('id="filterDateFrom"');
    expect($html)->toContain('id="filterDateTo"');
});
