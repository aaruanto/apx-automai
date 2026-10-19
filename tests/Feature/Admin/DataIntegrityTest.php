<?php

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The rules that keep the money figures honest.
 *
 * Each of the first three tests reproduces a gap that was real and confirmed
 * before this phase: a completed booking re-priced itself at today's rates on
 * any edit, status moved freely backwards out of completed, and the booking
 * form wrote an unvalidated plate onto the shared vehicle.
 */

function intAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'name' => 'The Admin']);
}

function intBooking(string $status, int $price = 1500, ?Service $service = null): array
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);
    $service ??= Service::create(['name' => 'Svc '.$unique, 'price' => $price, 'duration' => 30]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'INT '.substr($unique, 0, 4), 'color' => 'Red',
    ]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => today()->addDay()->toDateString(), 'booking_time' => '09:00',
        'duration' => 30, 'status' => $status, 'reference_number' => 'APX-'.$unique,
    ]);
    $booking->services()->attach($service->id, ['price' => $price, 'duration' => 30]);

    return [$booking, $service, $vehicle];
}

function editPayload(Booking $b, array $overrides = []): array
{
    return array_merge([
        'service_ids'  => $b->services()->pluck('services.id')->all(),
        'booking_date' => $b->booking_date,
        'booking_time' => $b->booking_time,
        'status'       => $b->status,
    ], $overrides);
}

// ── 4: the price snapshot ────────────────────────────────────────────────

it('keeps the price a completed booking was done at', function () {
    [$b, $service] = intBooking('completed', 1500);

    $service->update(['price' => 2000]);

    $this->actingAs(intAdmin())
        ->put("/admin/bookings/{$b->id}", editPayload($b, ['notes' => 'customer called']));

    // This returned 2000 before: a notes-only edit rewrote what the job earned.
    expect((float) $b->fresh()->services()->first()->pivot->price)->toBe(1500.0);
});

it('keeps existing prices but quotes a newly added service at todays rate', function () {
    [$b, $oil] = intBooking('confirmed', 1500);
    $brakes    = Service::create(['name' => 'Brakes', 'price' => 2500, 'duration' => 30]);

    $oil->update(['price' => 9999]);

    $this->actingAs(intAdmin())->put("/admin/bookings/{$b->id}",
        editPayload($b, ['service_ids' => [$oil->id, $brakes->id]]));

    $prices = $b->fresh()->services()->pluck('booking_service.price', 'services.id');
    expect((float) $prices[$oil->id])->toBe(1500.0);     // unchanged
    expect((float) $prices[$brakes->id])->toBe(2500.0);  // today's rate
});

// ── 2 and 3: completed is locked, status is forward only ─────────────────

it('refuses to move a completed booking back to pending', function () {
    [$b] = intBooking('completed');

    $this->actingAs(intAdmin())->put("/admin/bookings/{$b->id}", editPayload($b, ['status' => 'pending']));

    expect($b->fresh()->status)->toBe('completed');
});

it('refuses any backward status move', function (string $from, string $to) {
    [$b] = intBooking($from);

    $this->actingAs(intAdmin())
        ->put("/admin/bookings/{$b->id}", editPayload($b, ['status' => $to]))
        ->assertSessionHasErrors('status');

    expect($b->fresh()->status)->toBe($from);
})->with([
    ['in_progress', 'pending'],
    ['in_progress', 'confirmed'],
    ['confirmed', 'pending'],
]);

it('still allows moving forward, including skipping ahead', function (string $from, string $to) {
    [$b] = intBooking($from);

    $this->actingAs(intAdmin())
        ->put("/admin/bookings/{$b->id}", editPayload($b, ['status' => $to]))
        ->assertSessionHasNoErrors();

    expect($b->fresh()->status)->toBe($to);
})->with([
    ['pending', 'confirmed'],
    ['confirmed', 'in_progress'],
    ['in_progress', 'completed'],
    ['pending', 'completed'],
]);

it('still lets notes be edited on a completed booking', function () {
    [$b] = intBooking('completed');

    $this->actingAs(intAdmin())
        ->put("/admin/bookings/{$b->id}", editPayload($b, ['notes' => 'paid in cash']));

    expect($b->fresh()->notes)->toBe('paid in cash');
});

it('does not reschedule a completed booking', function () {
    [$b] = intBooking('completed');
    $was = $b->booking_date;

    $this->actingAs(intAdmin())->put("/admin/bookings/{$b->id}",
        editPayload($b, ['booking_date' => today()->addMonth()->toDateString()]));

    expect($b->fresh()->booking_date)->toBe($was);
});

// ── 5: the plate ─────────────────────────────────────────────────────────

it('ignores a plate submitted through the booking edit form', function () {
    [$b, , $vehicle] = intBooking('confirmed');
    $was = $vehicle->plate_number;

    $this->actingAs(intAdmin())
        ->put("/admin/bookings/{$b->id}", editPayload($b, ['plate' => 'nonsense!!!']));

    // Previously this wrote "NONSENSE!!!" onto the shared vehicle row.
    expect($vehicle->fresh()->plate_number)->toBe($was);
});

it('holds the admin to the same plate format as the customer', function () {
    $this->actingAs(intAdmin())->post('/admin/bookings', [
        'customer_name' => 'Juan', 'customer_phone' => '09171234567',
        'plate' => 'nonsense!!!', 'service_ids' => [Service::create(['name' => 'S', 'price' => 1, 'duration' => 30])->id],
        'booking_date' => today()->addDay()->toDateString(), 'booking_time' => '09:00',
    ])->assertSessionHasErrors('plate');
});

// ── 6: deleting ──────────────────────────────────────────────────────────

it('will not delete a completed booking', function () {
    [$b] = intBooking('completed');

    $this->actingAs(intAdmin())
        ->deleteJson("/admin/bookings/{$b->id}", ['reason' => 'tidying up'])
        ->assertStatus(422);

    expect(Booking::find($b->id))->not->toBeNull();
});

it('demands a reason before deleting', function () {
    [$b] = intBooking('cancelled');

    $this->actingAs(intAdmin())
        ->deleteJson("/admin/bookings/{$b->id}", [])
        ->assertStatus(422)->assertJsonValidationErrors('reason');

    expect(Booking::find($b->id))->not->toBeNull();
});

it('deletes with a reason and records who did it', function () {
    [$b] = intBooking('cancelled');

    $this->actingAs(intAdmin())
        ->deleteJson("/admin/bookings/{$b->id}", ['reason' => 'duplicate of APX-0042'])
        ->assertOk();

    expect(Booking::find($b->id))->toBeNull();

    $log = AuditLog::where('action', 'booking.deleted')->sole();
    expect($log->reason)->toBe('duplicate of APX-0042');
    expect($log->actor_name)->toBe('The Admin');
    expect($log->subject)->toBe($b->reference_number);
});

// ── 7: the audit trail ───────────────────────────────────────────────────

it('records a status change with both values', function () {
    [$b] = intBooking('pending');

    $this->actingAs(intAdmin())->put("/admin/bookings/{$b->id}", editPayload($b, ['status' => 'confirmed']));

    $log = AuditLog::where('action', 'booking.status_changed')->sole();
    expect($log->old_values['status'])->toBe('pending');
    expect($log->new_values['status'])->toBe('confirmed');
    expect($log->actor_name)->toBe('The Admin');
});

it('records a reschedule and a service change', function () {
    [$b, $oil] = intBooking('confirmed');
    $extra = Service::create(['name' => 'Extra', 'price' => 500, 'duration' => 30]);

    $this->actingAs(intAdmin())->put("/admin/bookings/{$b->id}", editPayload($b, [
        'service_ids'  => [$oil->id, $extra->id],
        'booking_date' => nextWeekdayAfter(3),
    ]));

    expect(AuditLog::where('action', 'booking.services_changed')->exists())->toBeTrue();
    expect(AuditLog::where('action', 'booking.rescheduled')->exists())->toBeTrue();
});

it('records a price change but not a rename', function () {
    $service = Service::create(['name' => 'Oil', 'price' => 1500, 'duration' => 30]);
    $admin   = intAdmin();

    $this->actingAs($admin)->put("/admin/settings/services/{$service->id}", [
        'name' => 'Oil Change', 'price' => 1500, 'duration' => 30,
    ]);
    expect(AuditLog::where('action', 'service.price_changed')->count())->toBe(0);

    $this->actingAs($admin)->put("/admin/settings/services/{$service->id}", [
        'name' => 'Oil Change', 'price' => 2000, 'duration' => 30,
    ]);

    $log = AuditLog::where('action', 'service.price_changed')->sole();
    expect((float) $log->old_values['price'])->toBe(1500.0);
    expect((float) $log->new_values['price'])->toBe(2000.0);
});

it('writes no log entry when nothing meaningful changed', function () {
    [$b] = intBooking('confirmed');

    $this->actingAs(intAdmin())->put("/admin/bookings/{$b->id}", editPayload($b, ['notes' => 'just a note']));

    // Notes are not money. A log full of noise is one nobody reads.
    expect(AuditLog::count())->toBe(0);
});

it('shows the history on the booking it describes', function () {
    [$b] = intBooking('pending');
    $admin = intAdmin();

    $this->actingAs($admin)->put("/admin/bookings/{$b->id}", editPayload($b, ['status' => 'confirmed']));

    $this->actingAs($admin)->get("/admin/bookings/{$b->id}/edit")->assertOk()
        ->assertSee('Change History')
        ->assertSee('Status changed from pending to confirmed')
        ->assertSee('The Admin');
});

// ── 1: customer identity stays read-only ─────────────────────────────────

it('still refuses to edit customer identity', function () {
    [$b] = intBooking('confirmed');
    $customer = $b->user;

    $this->actingAs(intAdmin())->put("/admin/bookings/{$b->id}", editPayload($b, [
        'customer_name'  => 'Someone Else',
        'customer_phone' => '09999999999',
    ]));

    expect($customer->fresh()->name)->not->toBe('Someone Else');
    expect($customer->fresh()->phone)->not->toBe('09999999999');
});
