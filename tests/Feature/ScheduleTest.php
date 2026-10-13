<?php

use App\Exceptions\SlotUnavailableException;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingAvailability;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Monday to Friday, 9:00 AM to 6:00 PM; closed at weekends.
 *
 * The hours were previously only used to build the calendar, never to check a
 * submitted booking, so a request that posted a time directly could book
 * outside opening hours or on a closed day.
 */

function nextWeekday(): string
{
    $d = Carbon::today()->addDay();
    while ($d->dayOfWeekIso >= 6) {
        $d->addDay();
    }

    return $d->toDateString();
}

function nextSaturday(): string
{
    return Carbon::today()->next(Carbon::SATURDAY)->toDateString();
}

function schedBooking(string $date, string $time, int $minutes = 30): array
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'SCH '.$unique, 'color' => 'Red',
    ]);
    $service = Service::create(['name' => 'Sched '.$unique, 'price' => 1000, 'duration' => $minutes]);

    return [[
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id,
        'service_ids' => [$service->id],
        'booking_date' => $date, 'booking_time' => $time, 'status' => 'pending',
    ], $customer];
}

// ── The window ───────────────────────────────────────────────────────────

it('opens 9 to 6 on weekdays', function () {
    $hours = app(BookingAvailability::class)->hoursFor(Carbon::parse(nextWeekday()));

    expect($hours)->not->toBeNull();
    expect($hours[0]->format('H:i'))->toBe('09:00');
    expect($hours[1]->format('H:i'))->toBe('18:00');
});

it('keeps weekends at 9 to 12', function (string $day) {
    // Unchanged by this phase: only the weekday closing time moved.
    $hours = app(BookingAvailability::class)->hoursFor(Carbon::today()->next($day));

    expect($hours)->not->toBeNull();
    expect($hours[0]->format('H:i'))->toBe('09:00');
    expect($hours[1]->format('H:i'))->toBe('12:00');
})->with(['Saturday', 'Sunday']);

it('still offers weekend slots, ending by noon', function () {
    $slots = app(BookingAvailability::class)->dayAvailability(nextSaturday(), 30);
    $last  = end($slots);

    expect($slots)->not->toBe([]);
    expect($last['start'])->toBe('11:30');
});

it('ends the last slot by 6 PM', function (int $duration, string $lastStart) {
    $slots = app(BookingAvailability::class)->dayAvailability(nextWeekday(), $duration);
    $last  = end($slots);

    expect($last['start'])->toBe($lastStart);

    // Whatever the duration, service finishes by close.
    $end = Carbon::parse(nextWeekday().' '.$last['start'])->addMinutes($duration);
    expect($end->format('H:i'))->toBeLessThanOrEqual('18:00');
})->with([
    '30 min' => [30, '17:30'],
    '45 min' => [45, '17:00'],
    '60 min' => [60, '17:00'],
    '90 min' => [90, '16:30'],
]);

// ── Enforced on the server, not just in the calendar ────────────────────

it('accepts a weekend booking inside weekend hours', function () {
    [$attrs] = schedBooking(nextSaturday(), '10:00');

    expect(app(BookingAvailability::class)->reserve($attrs)->booking_time)->toBe('10:00');
});

it('refuses a weekend booking past noon', function () {
    // Weekends close at 12:00, so the guard applies there too.
    [$attrs] = schedBooking(nextSaturday(), '13:00');

    expect(fn () => app(BookingAvailability::class)->reserve($attrs))
        ->toThrow(SlotUnavailableException::class);
});

it('refuses a booking before opening', function () {
    [$attrs] = schedBooking(nextWeekday(), '03:00');

    expect(fn () => app(BookingAvailability::class)->reserve($attrs))
        ->toThrow(SlotUnavailableException::class);
});

it('refuses a booking that would run past closing', function () {
    // 17:45 + 30 min = 18:15.
    [$attrs] = schedBooking(nextWeekday(), '17:45', 30);

    expect(fn () => app(BookingAvailability::class)->reserve($attrs))
        ->toThrow(SlotUnavailableException::class);
});

it('accepts a booking that finishes exactly at closing', function () {
    [$attrs] = schedBooking(nextWeekday(), '17:30', 30);

    $booking = app(BookingAvailability::class)->reserve($attrs);

    expect($booking->booking_time)->toBe('17:30');
});

it('blocks an out-of-hours booking through the customer endpoint', function () {
    [$attrs, $customer] = schedBooking(nextSaturday(), '13:00');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => $attrs['service_ids'],
        'vehicle_id'   => $attrs['vehicle_id'],
        'booking_date' => nextSaturday(),
        'booking_time' => '13:00',
    ])->assertStatus(422);
});

it('says why, rather than blaming capacity', function () {
    [$attrs] = schedBooking(nextWeekday(), '03:00');

    try {
        app(BookingAvailability::class)->reserve($attrs);
        $this->fail('expected a SlotUnavailableException');
    } catch (SlotUnavailableException $e) {
        expect($e->getMessage())->toContain('opening hours');
    }
});

// ── Existing bookings outside the window are left alone ─────────────────

it('leaves an existing out-of-hours booking readable', function () {
    // Rows already on file must keep working: the guard applies to new
    // bookings and reschedules, not to what is already stored.
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'OLD '.$unique, 'color' => 'Red',
    ]);
    $service = Service::create(['name' => 'Old '.$unique, 'price' => 1000, 'duration' => 30]);

    $booking = App\Models\Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '19:30',
        'duration' => 30, 'status' => 'pending', 'reference_number' => 'OLD-'.$unique,
    ]);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/bookings')->assertOk()->assertSee($booking->reference_number);

    // And it can still be cancelled.
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Outside new hours'])
        ->assertOk();
});
