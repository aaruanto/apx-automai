<?php

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A rebooking leaves a trace that it was one.
 *
 * The form's banner named the cancelled booking being replaced, but nothing
 * carried that reference into the submit, so store() had no idea it was a
 * rebook. store() also wrote no audit row at all, while cancel, delete,
 * reschedule and mechanic assignment all do. Afterwards there was no way to
 * tell that BK-B stood in for the cancelled BK-A — the only question anyone
 * asks about a rebooking.
 *
 * No migration: a rebooked_from column is schema work on a live Postgres, and
 * the audit log answers the question without one.
 */

function rtAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function rtCancelled(): Booking
{
    static $n = 1;

    $customer = User::factory()->create(['role' => 'customer', 'phone' => '0917555'.str_pad((string) $n, 4, '0', STR_PAD_LEFT)]);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'plate_number' => 'RTL '.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
    ]);
    $service = Service::create(['name' => 'Trail '.$n, 'price' => 1200, 'duration' => 60]);

    $booking = Booking::create([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => '2026-08-31',
        'booking_time'     => '10:00',
        'duration'         => 60,
        'status'           => 'cancelled',
        'reference_number' => 'APX-RTL-'.$n++,
    ]);

    $booking->services()->sync([$service->id => ['price' => 1200, 'duration' => 60]]);

    return $booking;
}

/** Submit the rebook form the way the browser does. */
function rtSubmit(User $admin, Booking $original, array $overrides = [])
{
    return test()->actingAs($admin)->post('/admin/bookings', array_merge([
        'customer_name'  => $original->user->name,
        'customer_phone' => $original->user->phone,
        'plate'          => $original->vehicle->plate_number,
        'service_id'     => $original->service_id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
        'rebooked_from'  => $original->reference_number,
    ], $overrides));
}

it('records which cancelled booking a rebook replaced', function () {
    $original = rtCancelled();

    rtSubmit(rtAdmin(), $original)->assertRedirect();

    $new = Booking::where('status', 'pending')->sole();

    $entry = AuditLog::where('auditable_type', Booking::class)
        ->where('auditable_id', $new->id)
        ->sole();

    expect($entry->action)->toBe('booking.rebooked');
    expect($entry->old_values['rebooked_from'])->toBe($original->reference_number);
    expect($entry->reason)->toContain($original->reference_number);

    // Subject is the NEW booking, so the trail already on its edit screen
    // shows this with no view changes.
    expect($entry->subject)->toBe($new->reference_number);
});

it('reads as a sentence in the change history', function () {
    $original = rtCancelled();
    $admin    = rtAdmin();

    rtSubmit($admin, $original)->assertRedirect();

    $new = Booking::where('status', 'pending')->sole();

    $html = $this->actingAs($admin)
        ->get('/admin/bookings/'.$new->id.'/edit')
        ->assertOk()->getContent();

    expect($html)->toContain('Change History');
    expect($html)->toContain('Rebooked from '.$original->reference_number);
});

it('records a plain creation when the shop books from scratch', function () {
    $service = Service::create(['name' => 'From Scratch', 'price' => 900, 'duration' => 30]);

    $this->actingAs(rtAdmin())->post('/admin/bookings', [
        'customer_name'  => 'Walk In',
        'customer_phone' => '09170009999',
        'plate'          => 'RTL 9999',
        'service_id'     => $service->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '11:00',
    ])->assertRedirect();

    $entry = AuditLog::sole();

    // store() wrote nothing at all, so a booking could appear with no record
    // of who made it, while every other action on it was logged.
    expect($entry->action)->toBe('booking.created');
    expect($entry->old_values)->toBeNull();
});

// ── the reference is checked, never trusted ──────────────────────────────

it('ignores a reference that names nothing and still books it', function () {
    $original = rtCancelled();

    rtSubmit(rtAdmin(), $original, ['rebooked_from' => 'APX-NOPE-9999'])->assertRedirect();

    $new = Booking::where('status', 'pending')->sole();

    expect($new->exists)->toBeTrue();
    expect(AuditLog::where('auditable_id', $new->id)->sole()->action)->toBe('booking.created');
});

it('ignores a reference naming a booking that is not cancelled', function () {
    $original = rtCancelled();

    // A live booking's reference, posted as the one being replaced. Nothing
    // was replaced, so nothing claims it was.
    $live = rtCancelled();
    $live->update(['status' => 'confirmed']);

    rtSubmit(rtAdmin(), $original, ['rebooked_from' => $live->reference_number])->assertRedirect();

    $new = Booking::where('status', 'pending')->sole();

    expect(AuditLog::where('auditable_id', $new->id)->sole()->action)->toBe('booking.created');
});

it('never lets the audit row cost the booking', function () {
    $original = rtCancelled();

    // A reference far longer than the column, so writing the row fails. The
    // booking must survive it — AuditLog::record() swallows its own errors
    // for exactly this reason.
    rtSubmit(rtAdmin(), $original, ['rebooked_from' => str_repeat('X', 5000)])
        ->assertRedirect();

    expect(Booking::where('status', 'pending')->count())->toBe(1);
});

// ── the banner names the button that is there ────────────────────────────

it('tells the admin to click the button the form actually has', function () {
    $original = rtCancelled();

    $html = $this->actingAs(rtAdmin())
        ->get('/admin/bookings/'.$original->id.'/rebook')
        ->assertOk()->getContent();

    // The banner said "Create Booking"; the button on a rebook reads
    // "Confirm Rebook".
    expect($html)->toContain('click <strong style="color:var(--text);">Confirm Rebook</strong>');
    expect($html)->not->toContain('click <strong style="color:var(--text);">Create Booking</strong>');
});

it('carries the original reference into the submit', function () {
    $original = rtCancelled();

    $html = $this->actingAs(rtAdmin())
        ->get('/admin/bookings/'.$original->id.'/rebook')
        ->assertOk()->getContent();

    expect($html)->toContain('name="rebooked_from" value="'.$original->reference_number.'"');
});

it('posts no rebook reference from a plain new booking form', function () {
    $html = $this->actingAs(rtAdmin())
        ->get('/admin/bookings/create')
        ->assertOk()->getContent();

    expect($html)->not->toContain('name="rebooked_from"');
});
