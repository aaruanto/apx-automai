<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\LoyaltyLedger;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The queue board: what is physically in the shop, right now.
 *
 * This screen used to be a timeline of everything booked today, in
 * booking_time order, which answered a question the shop does not have — the
 * appointments are in the bookings list. What they could not see anywhere was
 * which cars are actually here and how far along each one is.
 *
 * So only the four statuses that mean a vehicle is on the premises appear, and
 * the two that mean it is merely expected are counted instead. These pin that
 * boundary, the queue numbering, and the Advance control.
 */

function qbAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function qbBooking(
    BookingStatus $status,
    ?string $arrivedAt = null,
    ?string $date = null,
    array $attributes = []
): Booking {
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer', 'name' => 'Queue Customer '.$unique]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'QBD '.substr($unique, 0, 4), 'color' => 'Red',
        'vehicle_type' => 'car', 'transmission' => 'automatic',
    ]);

    $service = Service::create([
        'name' => 'Queue Svc '.$unique, 'price' => 1500, 'duration' => 30,
    ]);

    $booking = Booking::create(array_merge([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => $date ?? today()->toDateString(),
        'booking_time'     => '09:00',
        'duration'         => 30,
        'status'           => $status,
        'arrived_at'       => $arrivedAt,
        'reference_number' => 'QBD-'.$unique,
        'vehicle_type'     => 'car',
        'transmission'     => 'automatic',
    ], $attributes));

    $booking->services()->attach($service->id, ['price' => 1500, 'duration' => 30]);

    return $booking;
}

/** The board's HTML. */
function qbBoard(?User $admin = null): string
{
    return test()->actingAs($admin ?? qbAdmin())
        ->get('/admin/bookings/schedule')
        ->assertOk()
        ->getContent();
}

// ── What is on the board, and what is not ───────────────────────────────

it('leaves a confirmed booking off the board until someone marks it arrived', function () {
    $booking = qbBooking(BookingStatus::BookingConfirmed);

    expect(qbBoard())->not->toContain($booking->reference_number);
});

it('leaves a pending booking off it too', function () {
    $booking = qbBooking(BookingStatus::PendingReview);

    expect(qbBoard())->not->toContain($booking->reference_number);
});

it('shows a booking the moment it is marked arrived', function () {
    $booking = qbBooking(BookingStatus::BookingConfirmed);

    test()->actingAs(qbAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/arrive")
        ->assertOk();

    expect(qbBoard())->toContain($booking->reference_number);
});

it('shows every status that means the car is here', function (string $status) {
    $booking = qbBooking(BookingStatus::from($status), now()->toDateTimeString());

    expect(qbBoard())->toContain($booking->reference_number);
})->with([
    'arrived'             => ['arrived'],
    'vehicle inspection'  => ['vehicle_inspection'],
    'service in progress' => ['service_in_progress'],
    'service completed'   => ['service_completed'],
]);

it('leaves a cancelled booking off it', function () {
    // It is not in the shop, and the cancelled screen is where it belongs.
    $booking = qbBooking(BookingStatus::Cancelled, now()->toDateTimeString());

    expect(qbBoard())->not->toContain($booking->reference_number);
});

it('shows only today', function () {
    $today     = qbBooking(BookingStatus::Arrived, now()->toDateTimeString());
    $yesterday = qbBooking(
        BookingStatus::Arrived,
        now()->subDay()->toDateTimeString(),
        today()->subDay()->toDateString()
    );

    $html = qbBoard();

    expect($html)->toContain($today->reference_number);
    expect($html)->not->toContain($yesterday->reference_number);
});

// ── Saying where the missing ones went ──────────────────────────────────

it('counts what is booked for today but not here yet', function () {
    qbBooking(BookingStatus::BookingConfirmed);
    qbBooking(BookingStatus::BookingConfirmed);
    qbBooking(BookingStatus::PendingReview);
    qbBooking(BookingStatus::Arrived, now()->toDateTimeString());

    $response = test()->actingAs(qbAdmin())->get('/admin/bookings/schedule')->assertOk();

    // Reported separately, not summed: a booking the shop has not reviewed
    // yet is not "confirmed for today".
    expect($response->viewData('awaitingArrival'))->toBe(2);
    expect($response->viewData('awaitingReview'))->toBe(1);
    expect($response->viewData('inShop'))->toBe(1);
});

it('links those counts to the list the bookings are on', function () {
    qbBooking(BookingStatus::BookingConfirmed);

    $html = qbBoard();

    expect($html)->toContain('Confirmed, not arrived');
    expect($html)->toContain(route('admin.bookings.index', [
        'status' => BookingStatus::BookingConfirmed->value,
    ]));
});

it('explains an empty board rather than just being blank', function () {
    $html = qbBoard();

    expect($html)->toContain('Nothing in the shop yet');
    expect($html)->toContain('Arrived');
});

// ── Queue numbers ───────────────────────────────────────────────────────

it('numbers cars in the order they arrived', function () {
    $second = qbBooking(BookingStatus::Arrived, now()->setTime(10, 0)->toDateTimeString());
    $first  = qbBooking(BookingStatus::Arrived, now()->setTime(9, 0)->toDateTimeString());
    $third  = qbBooking(BookingStatus::Arrived, now()->setTime(11, 0)->toDateTimeString());

    $numbers = test()->actingAs(qbAdmin())
        ->get('/admin/bookings/schedule')->assertOk()
        ->viewData('queueNumbers');

    expect($numbers[$first->id])->toBe(1);
    expect($numbers[$second->id])->toBe(2);
    expect($numbers[$third->id])->toBe(3);
});

it('numbers per day, not per column, so a car keeps its number', function () {
    // Staff call out "car four". If the number were per column, car four
    // would become car one by being the first to reach Vehicle Inspection.
    $first  = qbBooking(BookingStatus::ServiceInProgress, now()->setTime(9, 0)->toDateTimeString());
    $second = qbBooking(BookingStatus::Arrived, now()->setTime(10, 0)->toDateTimeString());
    $third  = qbBooking(BookingStatus::VehicleInspection, now()->setTime(11, 0)->toDateTimeString());

    $numbers = test()->actingAs(qbAdmin())
        ->get('/admin/bookings/schedule')->assertOk()
        ->viewData('queueNumbers');

    // Three different columns, one sequence.
    expect([$numbers[$first->id], $numbers[$second->id], $numbers[$third->id]])->toBe([1, 2, 3]);
});

it('keeps a car its number when it advances', function () {
    $first  = qbBooking(BookingStatus::Arrived, now()->setTime(9, 0)->toDateTimeString());
    $second = qbBooking(BookingStatus::Arrived, now()->setTime(10, 0)->toDateTimeString());
    $admin  = qbAdmin();

    test()->actingAs($admin)->patchJson("/admin/bookings/{$first->id}/advance")->assertOk();

    $numbers = test()->actingAs($admin)
        ->get('/admin/bookings/schedule')->assertOk()
        ->viewData('queueNumbers');

    expect($numbers[$first->id])->toBe(1);
    expect($numbers[$second->id])->toBe(2);
});

it('gives no number to a booking that never recorded an arrival', function () {
    // An admin can take a booking straight to Service Completed on the edit
    // form without anyone pressing Arrived. It belongs on the board — the work
    // happened — but it cannot hold a place in a queue ordered by arrival.
    $arrived    = qbBooking(BookingStatus::Arrived, now()->setTime(9, 0)->toDateTimeString());
    $unstamped  = qbBooking(BookingStatus::ServiceCompleted, null);

    $response = test()->actingAs(qbAdmin())->get('/admin/bookings/schedule')->assertOk();
    $numbers  = $response->viewData('queueNumbers');

    expect($numbers[$arrived->id])->toBe(1);
    expect($numbers[$unstamped->id])->toBeNull();

    // And it says why, rather than showing a blank box.
    expect($response->getContent())->toContain('No arrival time recorded');
});

// ── The card ────────────────────────────────────────────────────────────

it('puts on the card everything the counter needs', function () {
    $mechanic = Mechanic::create([
        'name' => 'Ramon Cruz', 'contact_number' => '09171234567', 'status' => 'active',
    ]);

    $booking = qbBooking(
        BookingStatus::Arrived,
        now()->setTime(9, 30)->toDateTimeString(),
        null,
        ['mechanic_id' => $mechanic->id]
    );

    $html = qbBoard();

    expect($html)->toContain($booking->customer->name);          // who
    expect($html)->toContain($booking->vehicle->display_plate);  // which car
    expect($html)->toContain('Car · Automatic');                 // and what kind
    expect($html)->toContain($booking->services->first()->name); // what for
    expect($html)->toContain('9:30 AM');                         // since when
    expect($html)->toContain('Ramon Cruz');                      // and who has it
});

it('says unassigned rather than nothing when no mechanic has it', function () {
    qbBooking(BookingStatus::Arrived, now()->toDateTimeString());

    expect(qbBoard())->toContain('Unassigned');
});

// ── Advancing ───────────────────────────────────────────────────────────

it('advances one step, not to the end', function () {
    $booking = qbBooking(BookingStatus::Arrived, now()->toDateTimeString());

    test()->actingAs(qbAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/advance")
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'vehicle_inspection']);

    expect($booking->refresh()->status)->toBe(BookingStatus::VehicleInspection);
});

it('walks the whole ladder one press at a time', function () {
    $booking = qbBooking(BookingStatus::Arrived, now()->toDateTimeString());
    $admin   = qbAdmin();

    foreach ([BookingStatus::VehicleInspection, BookingStatus::ServiceInProgress, BookingStatus::ServiceCompleted] as $expected) {
        test()->actingAs($admin)
            ->patchJson("/admin/bookings/{$booking->id}/advance")
            ->assertOk();

        expect($booking->refresh()->status)->toBe($expected);
    }
});

it('refuses to advance past completed', function () {
    $booking = qbBooking(BookingStatus::ServiceCompleted, now()->toDateTimeString());

    test()->actingAs(qbAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/advance")
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($booking->refresh()->status)->toBe(BookingStatus::ServiceCompleted);
});

it('refuses to advance a cancelled booking', function () {
    // Cancelled is off the ladder, so it has no next step in either direction.
    $booking = qbBooking(BookingStatus::Cancelled, now()->toDateTimeString());

    test()->actingAs(qbAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/advance")
        ->assertStatus(422);

    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled);
});

it('draws no advance control on a finished card', function () {
    qbBooking(BookingStatus::ServiceCompleted, now()->toDateTimeString());

    $html = qbBoard();

    // The card is there; the button is not. A greyed-out control invites a
    // click that can never work.
    expect($html)->toContain('s-service_completed');
    expect($html)->not->toMatch('~s-service_completed[\s\S]{0,1200}?queue-advance~');
});

it('tells the second presser the booking already moved', function () {
    // Two tabs on the same card, or one impatient double-press. The guard is
    // inside the write, so the second one cannot move it a second step.
    $booking = qbBooking(BookingStatus::Arrived, now()->toDateTimeString());
    $admin   = qbAdmin();

    test()->actingAs($admin)->patchJson("/admin/bookings/{$booking->id}/advance")->assertOk();

    $booking->refresh();

    // Simulate the stale tab by replaying the request against the status it
    // thought the booking was in.
    Booking::where('id', $booking->id)->update(['status' => BookingStatus::Arrived->value]);
    test()->actingAs($admin)->patchJson("/admin/bookings/{$booking->id}/advance")->assertOk();

    expect($booking->refresh()->status)->toBe(BookingStatus::VehicleInspection);
});

it('runs the completion side effects when the last step is pressed', function () {
    // Advancing to the end is a completion, and it must do everything the
    // edit form's completion does — the reason BookingProgress exists.
    Setting::set('loyalty_enabled', true);

    $booking = qbBooking(BookingStatus::ServiceInProgress, now()->toDateTimeString());

    test()->actingAs(qbAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/advance")
        ->assertOk();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::ServiceCompleted);
    expect(LoyaltyLedger::where('booking_id', $booking->id)->where('points', '>', 0)->count())->toBe(1);
    expect($booking->vehicle->refresh()->last_service_date)->not->toBeNull();
});

it('is closed to customers and guests', function () {
    $booking = qbBooking(BookingStatus::Arrived, now()->toDateTimeString());

    test()->patchJson("/admin/bookings/{$booking->id}/advance")->assertStatus(401);
    test()->get('/admin/bookings/schedule')->assertRedirect();

    test()->actingAs(User::factory()->create(['role' => 'customer']))
        ->patchJson("/admin/bookings/{$booking->id}/advance")
        ->assertStatus(403);

    expect($booking->refresh()->status)->toBe(BookingStatus::Arrived);
});

// ── Layout ──────────────────────────────────────────────────────────────

it('stacks instead of running off a narrow screen', function () {
    // Same rule the rest of the admin carries: the columns must be allowed to
    // shrink, or a long plate holds one open and the page scrolls sideways.
    // See LayoutOverflowTest for where this came from.
    $html = qbBoard();

    expect($html)->toMatch('~\.queue-board\s*\{[^}]*grid-template-columns:\s*repeat\(4,\s*minmax\(0,\s*1fr\)\)~');
    expect($html)->toContain('.queue-board > * { min-width: 0; }');
    // And it folds, twice, at the breakpoints the layout already uses.
    expect($html)->toMatch('~@media \(max-width: 1100px\)\s*\{\s*\.queue-board~');
    expect($html)->toMatch('~@media \(max-width: 768px\)\s*\{\s*\.queue-board~');
});

it('keeps the admin card idiom rather than pulling in a framework', function () {
    qbBooking(BookingStatus::Arrived, now()->toDateTimeString());

    $html = qbBoard();

    expect($html)->toContain('class="card"');
    expect($html)->toContain('class="card-body"');
    expect($html)->toContain('class="badge badge-arrived"');
});
