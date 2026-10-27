<?php

use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingAvailability;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * The calendar's answers, pinned, and the cost of producing them.
 *
 * monthAvailability() asked the database for the same day's bookings once per
 * grid slot: 252 identical queries to draw one month. Locally that is 170ms
 * and invisible; against Render's Postgres every one of them is a network
 * round trip, which is the 1.5 to 1.7 seconds the picker took to appear.
 *
 * These tests exist to make that fixable. The capacity rule is the thing the
 * whole booking system rests on — three mechanics, never four — so the
 * refactor had to prove it changed only the number of queries and not a
 * single answer. The expectations below were captured from the original
 * implementation and must not be adjusted to suit a rewrite: if one of them
 * moves, the rewrite is wrong.
 */

function availCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

/** A booking that occupies [time, time+minutes) on $date. */
function availBooking(string $date, string $time, int $minutes = 60, string $status = 'pending'): void
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = availCustomer();

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'AVL '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Avail '.$unique, 'price' => 1000, 'duration' => $minutes]);

    App\Models\Booking::create([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => $date,
        'booking_time'     => $time,
        'duration'         => $minutes,
        'status'           => $status,
        'reference_number' => 'BK-'.$unique,
    ]);
}

// ── The answers, pinned ──────────────────────────────────────────────────

it('gives a weekday its full grid of slots', function () {
    $date  = nextWeekday();
    $slots = app(BookingAvailability::class)->dayAvailability($date, 60);

    // 09:00 to 18:00, 30-minute grid, a 60-minute job: the last start that
    // still finishes by close is 17:00.
    expect($slots)->toHaveCount(17);
    expect($slots[0]['start'])->toBe('09:00');
    expect($slots[0]['label'])->toBe('9:00 AM');
    expect(end($slots)['start'])->toBe('17:00');

    // Nothing booked, so every slot has all three mechanics free.
    expect(collect($slots)->every(fn ($s) => $s['remaining'] === 3))->toBeTrue();
    expect(collect($slots)->every(fn ($s) => $s['bookable'] === true))->toBeTrue();
});

it('closes a day once three bookings overlap the same window', function () {
    $date = nextWeekday();

    // Three 60-minute bookings all starting at 10:00 fill the shop for that
    // hour. A fourth cannot be taken, and any slot overlapping 10:00-11:00
    // must say so.
    availBooking($date, '10:00', 60);
    availBooking($date, '10:00', 60);
    availBooking($date, '10:00', 60);

    $slots = collect(app(BookingAvailability::class)->dayAvailability($date, 60))
        ->keyBy('start');

    expect($slots['10:00']['remaining'])->toBe(0);
    expect($slots['10:00']['bookable'])->toBeFalse();

    // 09:30-10:30 and 10:30-11:30 both overlap the full window.
    expect($slots['09:30']['bookable'])->toBeFalse();
    expect($slots['10:30']['bookable'])->toBeFalse();

    // 09:00-10:00 ends exactly as the block starts: half-open, so it is free.
    expect($slots['09:00']['remaining'])->toBe(3);
    expect($slots['09:00']['bookable'])->toBeTrue();

    // 11:00 is clear of it entirely.
    expect($slots['11:00']['remaining'])->toBe(3);
});

it('counts two overlapping bookings as two, not three', function () {
    $date = nextWeekday();

    availBooking($date, '10:00', 60);
    availBooking($date, '10:30', 60);

    $slots = collect(app(BookingAvailability::class)->dayAvailability($date, 30))
        ->keyBy('start');

    // 10:30-11:00 is the only instant both occupy.
    expect($slots['10:30']['remaining'])->toBe(1);
    expect($slots['10:00']['remaining'])->toBe(2);
    expect($slots['11:00']['remaining'])->toBe(2);
});

it('ignores cancelled and completed bookings', function () {
    $date = nextWeekday();

    availBooking($date, '10:00', 60, 'cancelled');
    availBooking($date, '10:00', 60, 'completed');
    availBooking($date, '10:00', 60, 'no_show');

    $slots = collect(app(BookingAvailability::class)->dayAvailability($date, 60))
        ->keyBy('start');

    // None of those statuses occupies a mechanic.
    expect($slots['10:00']['remaining'])->toBe(3);
});

it('gives a weekend its shorter grid', function () {
    $slots = app(BookingAvailability::class)->dayAvailability(nextSaturday(), 60);

    // 09:00 to 12:00, so the last 60-minute start is 11:00.
    expect($slots)->toHaveCount(5);
    expect(end($slots)['start'])->toBe('11:00');
});

it('offers nothing for a job longer than the day', function () {
    // 10 hours against a 9-hour weekday: no start finishes before close.
    expect(app(BookingAvailability::class)->dayAvailability(nextWeekday(), 600))->toBe([]);
});

it('marks past days past and leaves the rest open', function () {
    $today = Carbon::today();
    $days  = collect(app(BookingAvailability::class)
        ->monthAvailability($today->year, $today->month, 60))
        ->keyBy('date');

    expect($days)->toHaveCount($today->daysInMonth);

    if ($today->day > 1) {
        $yesterday = $today->copy()->subDay()->toDateString();
        expect($days[$yesterday]['status'])->toBe('past');
        expect($days[$yesterday]['bookable'])->toBeFalse();
    }

    // A weekday later this month, or next month if we are near the end.
    $future = Carbon::parse(nextWeekdayAfter(2));

    if ($future->month === $today->month) {
        expect($days[$future->toDateString()]['status'])->toBe('open');
        expect($days[$future->toDateString()]['bookable'])->toBeTrue();
    }
});

it('calls a day full when every slot in it is taken', function () {
    $date = nextWeekdayAfter(3);
    $day  = Carbon::parse($date);

    // A 480-minute job only fits starting at 09:00 (09:00-17:00 against a
    // 18:00 close leaves 09:00, 09:30, 10:00 as starts). Fill the morning
    // three deep and the whole day becomes unbookable for that duration.
    foreach (['09:00', '09:30', '10:00'] as $time) {
        availBooking($date, $time, 480);
        availBooking($date, $time, 480);
        availBooking($date, $time, 480);
    }

    $days = collect(app(BookingAvailability::class)
        ->monthAvailability($day->year, $day->month, 480))
        ->keyBy('date');

    expect($days[$date]['status'])->toBe('full');
    expect($days[$date]['bookable'])->toBeFalse();
});

// ── The cost ─────────────────────────────────────────────────────────────

it('draws a whole month without asking per slot', function () {
    $today = Carbon::today();

    // Some real bookings, so the query count is not flattered by an empty
    // table short-circuiting the work.
    $date = nextWeekdayAfter(2);
    availBooking($date, '10:00', 60);
    availBooking($date, '11:00', 60);

    DB::flushQueryLog();
    DB::enableQueryLog();

    app(BookingAvailability::class)->monthAvailability($today->year, $today->month, 210);

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    // This was 252: one per grid slot, per day, all asking the same question.
    // A month needs the bookings for that month, which is one query, plus the
    // settings read. The ceiling is deliberately loose — the point is that it
    // cannot grow with the number of slots.
    expect($count)->toBeLessThanOrEqual(5);
});

it('answers a month and a day in one request', function () {
    // What lets the picker open a prefilled booking in a single round trip
    // instead of two. If this ever stops returning both keys together,
    // AvailabilityPicker.refresh() is back to costing two requests.
    $service = Service::create(['name' => 'Combined', 'price' => 1000, 'duration' => 60]);
    $date    = Carbon::parse(nextWeekdayAfter(2));

    $this->getJson('/booking/availability?'.http_build_query([
        'service_ids' => [$service->id],
        'year'        => $date->year,
        'month'       => $date->month,
        'date'        => $date->toDateString(),
    ]))
        ->assertOk()
        ->assertJsonStructure(['duration', 'days', 'slots'])
        ->assertJsonPath('date', $date->toDateString())
        ->assertJsonPath('duration', 60);
});

it('refuses a request that names neither a day nor a month', function () {
    $service = Service::create(['name' => 'Neither', 'price' => 1000, 'duration' => 60]);

    $this->getJson('/booking/availability?'.http_build_query([
        'service_ids' => [$service->id],
    ]))->assertStatus(422);
});
