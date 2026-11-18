<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * A booking is accepted the moment it is made, with one exception.
 *
 * Everything used to arrive Pending Review and wait on a human, including a
 * booking for a slot three weeks out that nothing else could want. Now
 * BookingAcceptance decides, and the only booking still held back is one for
 * TODAY on a day that has already taken its full intake — because the shop is
 * the only party that knows whether it can fit one more in, and the customer
 * cannot be sent off to "choose another day" when the thing they asked for is
 * same-day service.
 *
 * The decision lives in BookingAvailability::reserve(), which every booking
 * path goes through, so these tests drive the real endpoints rather than the
 * service: a rule that holds in the service and not on the customer's form is
 * not a rule.
 *
 * Mail goes through the array transport rather than Mail::fake(), because
 * MailFake::sendMail returns early for anything that is not a Mailable and
 * assertSentCount then reads zero however much mail went out. See CLAUDE.md.
 */
beforeEach(function () {
    config()->set('mail.default', 'array');
    Mail::purge('array');
    Setting::flushCache();
});

afterEach(function () {
    // A frozen clock leaking into the next test is a confusing failure.
    Carbon::setTestNow();
});

function acceptService(): Service
{
    static $n = 1;

    return Service::create([
        'name' => 'Accept Svc '.$n++, 'price' => 1500, 'duration' => 30, 'is_active' => true,
    ]);
}

function acceptCustomer(): User
{
    return User::factory()->create(['role' => 'customer', 'is_guest' => false, 'is_active' => true]);
}

function acceptVehicle(User $user): Vehicle
{
    static $n = 400;

    return Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
        'plate_number' => 'ACC '.$n++, 'vehicle_type' => 'car',
    ]);
}

/** A booking that already exists on $date, to fill the day up with. */
function acceptExisting(string $date, string $time = '09:00', string $status = 'booking_confirmed'): Booking
{
    $user = acceptCustomer();

    return Booking::create([
        'user_id'          => $user->id,
        'vehicle_id'       => acceptVehicle($user)->id,
        'service_id'       => acceptService()->id,
        'booking_date'     => $date,
        'booking_time'     => $time,
        'duration'         => 30,
        'status'           => $status,
        'reference_number' => 'ACC-'.substr(str_replace('.', '', uniqid('', true)), -6),
    ]);
}

function acceptAsCustomer(string $date, string $time = '10:00')
{
    $user = acceptCustomer();

    return test()->actingAs($user)->postJson('/customer/bookings', [
        'service_ids'  => [acceptService()->id],
        'vehicle_id'   => acceptVehicle($user)->id,
        'booking_date' => $date,
        'booking_time' => $time,
    ]);
}

function acceptAsGuest(string $date, string $time = '10:00', array $overrides = [])
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -4);

    return test()->postJson('/booking/guest', array_merge([
        'guest_name'    => 'Juan dela Cruz',
        'guest_email'   => 'accept'.$unique.'@gmail.com',
        'guest_phone'   => '09171234567',
        'booking_date'  => $date,
        'booking_time'  => $time,
        'service_ids'   => [acceptService()->id],
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'ACG '.$unique,
        'vehicle_type'  => 'car',
    ], $overrides));
}

/** Subject and both bodies of every message sent, as one searchable string. */
function acceptMailbox(): string
{
    return collect(Mail::getSymfonyTransport()->messages()->all())
        ->map(function ($message) {
            $email = $message->getOriginalMessage();

            return $email->getSubject()."\n".($email->getHtmlBody() ?? '')."\n".($email->getTextBody() ?? '');
        })
        ->implode("\n");
}

// ── accepted on the spot ─────────────────────────────────────────────────

it('confirms a customer booking for a future date the moment it is made', function () {
    $response = acceptAsCustomer(nextWeekdayAfter(3))->assertOk();

    expect(Booking::sole()->status)->toBe(BookingStatus::BookingConfirmed);

    // And the page is told, so the row it inserts without reloading does not
    // claim the booking is pending.
    $response->assertJsonPath('pending_review', false)
             ->assertJsonPath('status', 'upcoming')
             ->assertJsonPath('status_label', 'Booking Confirmed');
});

it('confirms a guest booking for a future date the moment it is made', function () {
    $response = acceptAsGuest(nextWeekdayAfter(3))->assertOk();

    expect(Booking::sole()->status)->toBe(BookingStatus::BookingConfirmed);

    $response->assertJsonPath('pending_review', false)
             ->assertJsonPath('status', 'booking_confirmed');
});

it('confirms a booking for today while the day still has room', function () {
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 5);
    acceptExisting($today);

    acceptAsCustomer($today, '11:00')->assertOk();

    expect(Booking::whereDate('booking_date', $today)->latest('id')->first()->status)
        ->toBe(BookingStatus::BookingConfirmed);
});

// ── the one exception ────────────────────────────────────────────────────

it('holds a customer booking for review when today is already full', function () {
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 1);
    acceptExisting($today);

    // Not refused — taken, and handed to the shop to decide on.
    $response = acceptAsCustomer($today, '11:00')->assertOk();

    expect(Booking::whereDate('booking_date', $today)->latest('id')->first()->status)
        ->toBe(BookingStatus::PendingReview);

    $response->assertJsonPath('pending_review', true)
             ->assertJsonPath('status_label', 'Pending Review');
});

it('holds a guest booking for review when today is already full', function () {
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 1);
    acceptExisting($today);

    $response = acceptAsGuest($today, '11:00')->assertOk();

    expect(Booking::whereDate('booking_date', $today)->latest('id')->first()->status)
        ->toBe(BookingStatus::PendingReview);

    $response->assertJsonPath('pending_review', true);
});

it('still refuses a future date that is full, rather than holding it for review', function () {
    // The exception is about today and only today: a customer told Thursday is
    // full can pick Friday at no cost, so the hard limit stays.
    Setting::set('max_bookings', 1);

    $date = nextWeekdayAfter(4);
    acceptExisting($date);

    $response = acceptAsCustomer($date, '11:00')->assertStatus(422);

    expect($response->json('message'))->toContain('That date is unavailable');
    expect(Booking::whereDate('booking_date', $date)->count())->toBe(1);
});

it('keeps the per-slot limit even on a full day', function () {
    // Today's intake limit becomes a review, never a bypass of the three
    // mechanics: a fourth car in one window is a promise the shop cannot keep
    // however the booking got there.
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 1);

    foreach (['10:00', '10:00', '10:00'] as $time) {
        acceptExisting($today, $time);
    }

    acceptAsCustomer($today, '10:00')->assertStatus(422);
});

it('confirms a booking for today when the cap is switched off', function () {
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 0);

    foreach (['09:00', '09:30', '10:00'] as $time) {
        acceptExisting($today, $time);
    }

    acceptAsCustomer($today, '11:00')->assertOk();

    expect(Booking::whereDate('booking_date', $today)->latest('id')->first()->status)
        ->toBe(BookingStatus::BookingConfirmed);
});

// ── what counts towards the day ──────────────────────────────────────────

it('counts a job already finished today towards the day being full', function () {
    // A cap is the shop's intake limit, so work completed at 10am has spent
    // one of the day's places. This count asked the enum for the statuses that
    // still hold a mechanic, so every completion handed its place back and a
    // cap of 1 could admit any number of bookings over one day.
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 1);
    acceptExisting($today, '09:00', 'service_completed');

    acceptAsCustomer($today, '11:00')->assertOk();

    expect(Booking::whereDate('booking_date', $today)->latest('id')->first()->status)
        ->toBe(BookingStatus::PendingReview);
});

it('does not count a cancelled booking, so a cancellation frees a place', function () {
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 1);
    acceptExisting($today, '09:00', 'cancelled');

    acceptAsCustomer($today, '11:00')->assertOk();

    expect(Booking::whereDate('booking_date', $today)->latest('id')->first()->status)
        ->toBe(BookingStatus::BookingConfirmed);
});

// ── the admin's own create path is deliberately untouched ────────────────

it('leaves an admin-created booking pending, because the admin is the reviewer', function () {
    // Recorded rather than assumed. Auto-accept answers a customer's request;
    // a walk-in the admin types in is already standing in front of them, and
    // having it approve itself would take the decision away from the one
    // person this feature hands decisions to. That path states its status
    // explicitly, which is what reserve() honours.
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->post('/admin/bookings', [
            'customer_name'  => 'Walk In',
            'customer_phone' => '09170009002',
            'plate'          => 'ADM 2201',
            'service_ids'    => [acceptService()->id],
            'booking_date'   => nextWeekdayAfter(2),
            'booking_time'   => '10:00',
        ])->assertRedirect();

    expect(Booking::sole()->status)->toBe(BookingStatus::PendingReview);
});

// ── what the customer is told ────────────────────────────────────────────

it('emails the reference number for a booking it accepted on the spot', function () {
    acceptAsGuest(nextWeekdayAfter(3))->assertOk();

    $booking = Booking::sole();
    $whole   = acceptMailbox();

    // The ticket, not the acknowledgment: the booking was never pending, so
    // "waiting for our review" would promise a second email that is never
    // coming.
    expect($whole)->toContain($booking->reference_number);
    expect(strtolower($whole))->toContain('confirmed');
    expect($whole)->not->toContain('waiting for our review');
});

it('emails the acknowledgment, with no reference, for a booking held for review', function () {
    $today = freezeOnOpenMorning();

    Setting::set('max_bookings', 1);
    acceptExisting($today);

    acceptAsGuest($today, '11:00')->assertOk();

    $booking = Booking::whereDate('booking_date', $today)->latest('id')->first();

    expect($booking->status)->toBe(BookingStatus::PendingReview);
    expect(acceptMailbox())->not->toContain($booking->reference_number);
    expect(strtolower(acceptMailbox()))->toContain('waiting for our review');
});

it('bells the customer about a booking it accepted on the spot', function () {
    acceptAsCustomer(nextWeekdayAfter(3))->assertOk();

    // Through BookingProgress, the same way an admin's Confirm does it, so the
    // two cannot say different things about the same event.
    expect(Booking::sole()->user->notifications->pluck('data')->pluck('event'))
        ->toContain('booking_confirmed');
});
