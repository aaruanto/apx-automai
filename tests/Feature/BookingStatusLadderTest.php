<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\LoyaltyLedger;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The booking lifecycle, now that it is one thing in one file.
 *
 * The ladder, the labels, the badge classes and the transition rules used to
 * be spread across four constants on the model and six copies of a
 * label-and-badge map. They had already drifted in a way a customer could
 * see: none of the admin maps had an entry for a completed booking, so a
 * finished job wore an amber Pending badge on the bookings list.
 *
 * These cover the enum itself, the two things about it that are easy to get
 * wrong (cancellation staying outside the ladder, and the completion side
 * effects firing exactly once), and the fact that the views are generated from
 * it rather than retyping it.
 */

function ladderAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function ladderBooking(BookingStatus|string $status = BookingStatus::PendingReview, int $price = 1500): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer', 'phone' => '09171234567']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'LDR '.substr($unique, 0, 4), 'color' => 'Red',
    ]);

    $service = Service::create([
        'name' => 'Ladder Svc '.$unique, 'price' => $price, 'duration' => 30,
    ]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '09:00', 'duration' => 30,
        'status' => $status, 'reference_number' => 'LDR-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => $price, 'duration' => 30]);

    return $booking;
}

// ── The ladder ───────────────────────────────────────────────────────────

it('runs in the order the shop works in', function () {
    expect(BookingStatus::ladderValues())->toBe([
        'pending_review',
        'booking_confirmed',
        'arrived',
        'vehicle_inspection',
        'service_in_progress',
        'service_completed',
    ]);
});

it('labels every status, including the one that used to fall through', function () {
    // 'completed' was missing from every admin map, so a finished job took
    // the default and read "Pending" in amber.
    foreach (BookingStatus::cases() as $case) {
        expect($case->label())->not->toBeEmpty();
        expect($case->badgeClass())->toStartWith('badge-');
    }

    expect(BookingStatus::ServiceCompleted->label())->toBe('Service Completed');
    expect(BookingStatus::ServiceCompleted->badgeClass())->toBe('badge-completed');
});

it('gives every status its own badge class', function () {
    // Two statuses sharing a class would make the queue board's columns
    // indistinguishable at a glance, which is the whole point of it.
    $classes = array_map(fn (BookingStatus $c) => $c->badgeClass(), BookingStatus::cases());

    expect($classes)->toHaveCount(count(array_unique($classes)));
});

it('moves forward and never back', function () {
    expect(BookingStatus::PendingReview->canMoveTo(BookingStatus::BookingConfirmed))->toBeTrue();
    expect(BookingStatus::BookingConfirmed->canMoveTo(BookingStatus::PendingReview))->toBeFalse();
    expect(BookingStatus::ServiceCompleted->canMoveTo(BookingStatus::Arrived))->toBeFalse();
});

it('lets a walk-in skip ahead', function () {
    // A car that turns up, is looked at and is finished in one visit should
    // not need four clicks to say so.
    expect(BookingStatus::PendingReview->canMoveTo(BookingStatus::ServiceCompleted))->toBeTrue();
    expect(BookingStatus::Arrived->canMoveTo(BookingStatus::ServiceCompleted))->toBeTrue();
});

it('treats staying put as allowed, so saving a form unchanged is not a move', function () {
    foreach (BookingStatus::ladder() as $case) {
        expect($case->canMoveTo($case))->toBeTrue();
    }
});

it('advances one step at a time', function () {
    expect(BookingStatus::PendingReview->next())->toBe(BookingStatus::BookingConfirmed);
    expect(BookingStatus::Arrived->next())->toBe(BookingStatus::VehicleInspection);
    expect(BookingStatus::VehicleInspection->next())->toBe(BookingStatus::ServiceInProgress);
    expect(BookingStatus::ServiceInProgress->next())->toBe(BookingStatus::ServiceCompleted);

    // The end of the ladder, and the status that is not on it.
    expect(BookingStatus::ServiceCompleted->next())->toBeNull();
    expect(BookingStatus::Cancelled->next())->toBeNull();
});

// ── Cancellation stays outside the ladder ────────────────────────────────

it('keeps cancelled off the ladder entirely', function () {
    expect(BookingStatus::Cancelled->onLadder())->toBeFalse();
    expect(BookingStatus::Cancelled->order())->toBeNull();
    expect(BookingStatus::ladder())->not->toContain(BookingStatus::Cancelled);
});

it('cannot be reached or escaped by a status move', function () {
    // Cancelling demands a reason, which a status dropdown cannot ask for, so
    // it has its own action and canMoveTo() refuses it in both directions.
    foreach (BookingStatus::ladder() as $case) {
        expect($case->canMoveTo(BookingStatus::Cancelled))->toBeFalse();
        expect(BookingStatus::Cancelled->canMoveTo($case))->toBeFalse();
    }
});

it('is cancellable anywhere before the work is finished', function () {
    // The rule the panel asked for: anything before Service Completed.
    expect(BookingStatus::PendingReview->isCancellable())->toBeTrue();
    expect(BookingStatus::BookingConfirmed->isCancellable())->toBeTrue();
    expect(BookingStatus::Arrived->isCancellable())->toBeTrue();
    expect(BookingStatus::VehicleInspection->isCancellable())->toBeTrue();
    expect(BookingStatus::ServiceInProgress->isCancellable())->toBeTrue();

    // A finished job is a record of work done; cancelling it would take
    // revenue out of the reports with nothing left to explain the gap.
    expect(BookingStatus::ServiceCompleted->isCancellable())->toBeFalse();
    // And there is nothing to cancel twice.
    expect(BookingStatus::Cancelled->isCancellable())->toBeFalse();
});

it('still records who cancelled and why', function () {
    // The three audit columns, BookingCancellation and the cancel_reason flow
    // are deliberately untouched by the rename.
    $booking = ladderBooking(BookingStatus::Arrived);
    $admin   = ladderAdmin();

    $this->actingAs($admin)
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Customer rang to call it off'])
        ->assertOk();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled);
    expect($booking->cancel_reason)->toBe('Customer rang to call it off');
    expect($booking->cancelled_by)->toBe($admin->id);
    expect($booking->cancelled_at)->not->toBeNull();
});

it('can be cancelled from a step that did not exist before', function () {
    // Arrived and Vehicle Inspection are new, and a car in either is very
    // much still cancellable — it is standing in the shop.
    foreach ([BookingStatus::Arrived, BookingStatus::VehicleInspection] as $from) {
        $booking = ladderBooking($from);

        $this->actingAs(ladderAdmin())
            ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Parts not available'])
            ->assertOk();

        expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled);
    }
});

it('refuses to cancel finished work', function () {
    $booking = ladderBooking(BookingStatus::ServiceCompleted);

    $this->actingAs(ladderAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Changed my mind'])
        ->assertStatus(422);

    expect($booking->refresh()->status)->toBe(BookingStatus::ServiceCompleted);
});

// ── Capacity ─────────────────────────────────────────────────────────────

it('counts a car in the shop as occupying a slot', function () {
    // The old list was pending, confirmed, in_progress. Arrived and Vehicle
    // Inspection joined the ladder between the last two, and a hardcoded list
    // would have let a car physically on the premises count as free capacity.
    expect(BookingStatus::occupyingValues())->toBe([
        'pending_review',
        'booking_confirmed',
        'arrived',
        'vehicle_inspection',
        'service_in_progress',
    ]);

    expect(BookingStatus::ServiceCompleted->occupiesSlot())->toBeFalse();
    expect(BookingStatus::Cancelled->occupiesSlot())->toBeFalse();
});

// ── The completion side effects, exactly once ───────────────────────────

it('awards points and stamps the vehicle when the work is finished', function () {
    Setting::set('loyalty_enabled', true);

    $booking = ladderBooking(BookingStatus::ServiceInProgress, 2000);

    $this->actingAs(ladderAdmin())->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => $booking->services()->pluck('services.id')->all(),
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => BookingStatus::ServiceCompleted->value,
    ])->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::ServiceCompleted);
    expect(LoyaltyLedger::where('booking_id', $booking->id)->where('points', '>', 0)->count())->toBe(1);
    expect($booking->vehicle->refresh()->last_service_date)->not->toBeNull();
});

it('does not award them twice when a finished booking is saved again', function () {
    // Exactly-once comes from the transition guard, not from the destination.
    // LoyaltyService::earnFor() is idempotent on top of that, which is belt
    // and braces on the one effect that moves money.
    Setting::set('loyalty_enabled', true);

    $booking = ladderBooking(BookingStatus::ServiceInProgress, 2000);
    $admin   = ladderAdmin();

    $payload = [
        'service_ids'  => $booking->services()->pluck('services.id')->all(),
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => BookingStatus::ServiceCompleted->value,
    ];

    $this->actingAs($admin)->put("/admin/bookings/{$booking->id}", $payload)->assertRedirect();
    $earned = LoyaltyLedger::where('booking_id', $booking->id)->where('points', '>', 0)->sum('points');

    // Save it again. A completed booking is locked, so this is a notes-only
    // edit — and it must not mint a second lot of points.
    $this->actingAs($admin)->put("/admin/bookings/{$booking->id}", $payload + ['notes' => 'paid in cash'])
        ->assertRedirect();

    expect((int) LoyaltyLedger::where('booking_id', $booking->id)->where('points', '>', 0)->sum('points'))
        ->toBe((int) $earned);
});

it('fires the completion effects from one place, whatever moved the booking', function () {
    // BookingProgress exists so the edit form and the queue board's Advance
    // control cannot drift. A copy of these effects is how a car advanced on
    // the board would have earned no points and never told its owner it was
    // ready.
    Setting::set('loyalty_enabled', true);

    $booking = ladderBooking(BookingStatus::ServiceInProgress, 2000);

    app(\App\Services\BookingProgress::class)->statusChanged(
        $booking->fresh(), BookingStatus::ServiceInProgress, BookingStatus::ServiceCompleted
    );

    // Called directly, with no HTTP request anywhere near it.
    expect(LoyaltyLedger::where('booking_id', $booking->id)->where('points', '>', 0)->count())->toBe(1);
});

// ── The views are generated from it ─────────────────────────────────────

it('serialises itself for the pages that need the map in javascript', function () {
    $map = BookingStatus::forJs();

    expect(array_keys($map))->toEqualCanonicalizing(BookingStatus::values());
    expect($map['arrived'])->toMatchArray([
        'label' => 'Arrived',
        'badge' => 'badge-arrived',
        'next'  => 'vehicle_inspection',
    ]);
});

it('hands the admin pages the serialised map rather than a retyped one', function () {
    $booking = ladderBooking(BookingStatus::BookingConfirmed);
    $admin   = ladderAdmin();

    foreach (['/admin/bookings', '/admin/dashboard', '/admin/bookings/schedule'] as $url) {
        $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

        expect($html)->toContain('APX_STATUSES');
        // A status the old hand-written maps never had an entry for.
        expect($html)->toContain('"vehicle_inspection"');
    }
});

it('offers every status on the admin list filter', function () {
    $html = $this->actingAs(ladderAdmin())->get('/admin/bookings')->assertOk()->getContent();

    foreach (BookingStatus::cases() as $case) {
        expect($html)->toContain('<option value="'.$case->value.'"');
    }
});

it('groups the ladder into the four tabs a customer needs', function () {
    // Six statuses is the shop's vocabulary, not the customer's. The grouping
    // is for the filter tabs; the card still shows the real label.
    expect(BookingStatus::PendingReview->customerGroup())->toBe('upcoming');
    expect(BookingStatus::BookingConfirmed->customerGroup())->toBe('upcoming');
    expect(BookingStatus::Arrived->customerGroup())->toBe('in_progress');
    expect(BookingStatus::VehicleInspection->customerGroup())->toBe('in_progress');
    expect(BookingStatus::ServiceInProgress->customerGroup())->toBe('in_progress');
    expect(BookingStatus::ServiceCompleted->customerGroup())->toBe('completed');
    expect(BookingStatus::Cancelled->customerGroup())->toBe('cancelled');
});

it('tells a customer the real step, not the group it is filed under', function () {
    $booking  = ladderBooking(BookingStatus::VehicleInspection);
    $customer = $booking->user;

    $html = $this->actingAs($customer)->get('/customer/dashboard')->assertOk()->getContent();

    // Filed under In Progress, but read as what is actually happening.
    expect($html)->toContain('Vehicle Inspection');
});

// ── The stored values ────────────────────────────────────────────────────

it('stores the values the migration renamed to', function () {
    $booking = ladderBooking(BookingStatus::BookingConfirmed);

    // Read past the cast, so this is what is really in the column.
    $raw = \Illuminate\Support\Facades\DB::table('bookings')
        ->where('id', $booking->id)->value('status');

    expect($raw)->toBe('booking_confirmed');
});

it('leaves cancelled spelled exactly as it was', function () {
    // Deliberately not renamed: it is outside the ladder, every one of its
    // audit columns and screens refers to it, and there was nothing to gain.
    expect(BookingStatus::Cancelled->value)->toBe('cancelled');
});
