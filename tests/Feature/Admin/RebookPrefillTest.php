<?php

use App\Models\Booking;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * What a rebook opens on.
 *
 * The date used to reset to tomorrow flat and the time was carried over from
 * the cancelled booking with nothing comparing the two. The shop shuts at noon
 * at weekends, so rebooking a cancelled afternoon booking on a Saturday
 * prefilled Sunday at an hour the shop is shut: the calendar drew nothing, the
 * hidden time field still held the old value, and submitting was refused by
 * assertWithinHours(). A form that cannot be submitted, and no word of why.
 *
 * Time travel here rather than relying on which day the suite runs — that trap
 * has cost this project two tests already.
 */
afterEach(fn () => Carbon::setTestNow());

function rpAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function rpCancelled(string $time, int $duration = 60, ?int $mechanicId = null): Booking
{
    static $n = 1;

    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'plate_number' => 'RPF '.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
    ]);
    $service = Service::create(['name' => 'Prefill '.$n, 'price' => 1200, 'duration' => $duration]);

    $booking = Booking::create([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'mechanic_id'      => $mechanicId,
        'booking_date'     => '2026-08-31',
        'booking_time'     => $time,
        'duration'         => $duration,
        'status'           => 'cancelled',
        'reference_number' => 'APX-RPF-'.$n++,
    ]);

    $booking->services()->sync([$service->id => ['price' => 1200, 'duration' => $duration]]);

    return $booking;
}

/** What the two hidden fields the picker reads actually contain. */
function rpPrefill(string $html): array
{
    preg_match('/name="booking_date"[^>]*value="([^"]*)"/', $html, $d);
    preg_match('/name="booking_time"[^>]*value="([^"]*)"/', $html, $t);

    return ['date' => $d[1] ?? null, 'time' => $t[1] ?? null];
}

// ── the reported break ───────────────────────────────────────────────────

it('does not prefill an hour the shop is shut', function () {
    // Saturday. Tomorrow is Sunday, open 09:00–12:00.
    Carbon::setTestNow(Carbon::parse('2026-10-31 10:00'));

    $booking = rpCancelled('14:00');

    $html = $this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent();

    $prefill = rpPrefill($html);

    expect($prefill['date'])->toBe('2026-11-01');

    // 14:00 on a day that closes at noon. Blank, so the picker opens on the
    // date and the admin chooses an hour that exists.
    expect($prefill['time'])->toBe('');
});

it('keeps the original time when it is still bookable', function () {
    // Monday, so tomorrow is a full weekday.
    Carbon::setTestNow(Carbon::parse('2026-11-02 08:00'));

    $booking = rpCancelled('14:00');

    $prefill = rpPrefill($this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent());

    expect($prefill['date'])->toBe('2026-11-03');
    expect($prefill['time'])->toBe('14:00');
});

it('keeps a morning time that still fits a short weekend day', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-31 07:00'));

    // 09:30 plus an hour ends at 10:30, inside Sunday's 09:00–12:00.
    $booking = rpCancelled('09:30');

    $prefill = rpPrefill($this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent());

    expect($prefill['date'])->toBe('2026-11-01');
    expect($prefill['time'])->toBe('09:30');
});

it('opens the calendar on the date even with no time to select', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-31 10:00'));

    $booking = rpCancelled('14:00');

    $html = $this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent();

    // The picker adopted the prefill only when it had BOTH a date and a time,
    // so a rebook that deliberately arrives without a time left the calendar
    // sitting on the current month with nothing selected — the one case that
    // most needs the admin pointed at a date showed them none.
    expect($html)->toContain('if (prefillDate) {');
    expect($html)->not->toContain('if (prefillDate && prefillTime) {');
});

it('does not round the time to a slot the admin did not choose', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-31 10:00'));

    $booking = rpCancelled('14:00');

    $prefill = rpPrefill($this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent());

    // Blank, not nudged to 11:00 or 09:00. A time the admin did not pick, on
    // a screen headed "Confirm Rebook", is worse than an empty field.
    expect($prefill['time'])->toBe('');
    expect($prefill['time'])->not->toBe('11:00');
});

it('leaves the time blank when the day is at the shop-wide cap', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-02 08:00'));

    Setting::set('max_bookings', 1);

    $booking = rpCancelled('14:00');

    // One booking already on the target date reaches a cap of one. The grid
    // still has capacity, so only the cap makes this unbookable.
    $filler = rpCancelled('10:00');
    $filler->update(['status' => 'pending', 'booking_date' => '2026-11-03']);

    $prefill = rpPrefill($this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent());

    expect($prefill['date'])->toBe('2026-11-03');
    expect($prefill['time'])->toBe('');
});

// ── the mechanic ─────────────────────────────────────────────────────────

it('starts a rebook with no mechanic assigned', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-02 08:00'));

    $mechanic = Mechanic::create(['name' => 'Ramon Cruz', 'status' => 'active']);
    $booking  = rpCancelled('10:00', mechanicId: $mechanic->id);

    $html = $this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent();

    // The mechanic is offered, but not chosen. They were free at the OLD date
    // and time; reserve() never checks a mechanic, and store() calls no
    // conflict check at all, so carrying them over double-books in silence.
    expect($html)->toContain('Ramon Cruz');
    expect($html)->not->toMatch('/value="'.$mechanic->id.'"\s*\n?\s*selected/');
});

it('still shows the assigned mechanic when editing a booking', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-02 08:00'));

    $mechanic = Mechanic::create(['name' => 'Ramon Cruz', 'status' => 'active']);
    $booking  = rpCancelled('10:00', mechanicId: $mechanic->id);
    $booking->update(['status' => 'pending']);

    // Editing is not rebooking: that booking already holds that mechanic at
    // that slot, and the edit screen must show what is true.
    $html = $this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/edit')
        ->assertOk()->getContent();

    expect($html)->toMatch('/value="'.$mechanic->id.'"[^>]*selected/');
});

// ── everything else still carries over ───────────────────────────────────

it('still carries the customer, vehicle, services and notes', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-02 08:00'));

    $booking = rpCancelled('10:00');
    $booking->update(['notes' => 'Bring the spare key']);
    $extra = Service::create(['name' => 'Second Service', 'price' => 500, 'duration' => 30]);
    $booking->services()->attach($extra->id, ['price' => 500, 'duration' => 30]);

    $html = $this->actingAs(rpAdmin())
        ->get('/admin/bookings/'.$booking->id.'/rebook')
        ->assertOk()->getContent();

    expect($html)->toContain($booking->user->name);
    expect($html)->toContain($booking->vehicle->plate_number);
    expect($html)->toContain('Bring the spare key');

    // Both services, from the pivot — not just the primary one.
    foreach ($booking->services as $service) {
        expect($html)->toMatch('/value="'.$service->id.'"[^>]*checked/');
    }
});
