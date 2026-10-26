<?php

use App\Models\Booking;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The delivery log: what the app actually sent.
 *
 * Message Templates answered "what goes out" and nothing answered "did it".
 * Settling "the customer says they never got a reminder" meant opening a
 * database client — and on Render, which has no shell on the free tier, an
 * external Postgres connection.
 *
 * The property that matters most here is that a simulated send is reported as
 * its own third outcome. SmsService returns ok for one, but it delivered
 * nothing, so counting it as a success would make the page lie in exactly the
 * way the log driver exists to prevent — and counting it as a failure would
 * paint a wall of red on a machine that is simply not configured to send.
 */
function logAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function logCustomer(): User
{
    return User::factory()->create(['role' => 'customer', 'name' => 'Rosa Delgado']);
}

function logVehicle(User $user): Vehicle
{
    static $n = 500;

    return Vehicle::create([
        'user_id'      => $user->id,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2021,
        'plate_number' => 'DLV '.$n++,
        'color'        => 'Silver',
        'vehicle_type' => 'car',
    ]);
}

function logBooking(User $user, Vehicle $vehicle): Booking
{
    static $n = 1;

    return Booking::create([
        'user_id'          => $user->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => Service::create(['name' => 'Log svc '.$n, 'price' => 900, 'duration' => 30])->id,
        'booking_date'     => nextWeekday(),
        'booking_time'     => '10:00:00',
        'duration'         => 30,
        'status'           => 'confirmed',
        'reference_number' => 'APX-LOG-'.$n++,
    ]);
}

function visitLog()
{
    return test()->actingAs(logAdmin())->get('/admin/templates/log');
}

// ── Access ─────────────────────────────────────────────────────────────────

it('renders for an admin', function () {
    visitLog()->assertOk()->assertSee('Delivery');
});

it('keeps the delivery log away from customers', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get('/admin/templates/log')
        ->assertStatus(403);
});

it('sends a signed-out visitor to log in', function () {
    $this->get('/admin/templates/log')->assertRedirect('/login');
});

// ── Reminders ──────────────────────────────────────────────────────────────

it('lists a sent reminder with the customer, the booking and the channel', function () {
    $user    = logCustomer();
    $vehicle = logVehicle($user);
    $booking = logBooking($user, $vehicle);

    Reminder::create([
        'type'        => Reminder::TYPE_APPOINTMENT,
        'user_id'     => $user->id,
        'vehicle_id'  => $vehicle->id,
        'booking_id'  => $booking->id,
        'trigger_key' => 'remind_3d',
        'channel'     => 'email',
        'status'      => 'sent',
        'sent_at'     => now(),
        'dedupe_key'  => 'appointment:'.$booking->id.':remind_3d:email',
    ]);

    visitLog()->assertOk()
        ->assertSee('Rosa Delgado')
        ->assertSee($booking->reference_number)
        ->assertSee($vehicle->display_plate)
        // The machine key is mapped to something a shop owner can read.
        ->assertSee('3 days before')
        ->assertDontSee('remind_3d');
});

it('shows an unfinished claim as in flight rather than as sent', function () {
    $user    = logCustomer();
    $vehicle = logVehicle($user);
    $booking = logBooking($user, $vehicle);

    // A claim that never got marked sent. Usually a tick in progress; one that
    // persists is a send that died between claiming and reporting, and calling
    // that "sent" would hide the only evidence of it.
    Reminder::create([
        'type'        => Reminder::TYPE_APPOINTMENT,
        'user_id'     => $user->id,
        'vehicle_id'  => $vehicle->id,
        'booking_id'  => $booking->id,
        'trigger_key' => 'remind_1d',
        'channel'     => 'sms',
        'status'      => 'claimed',
        'dedupe_key'  => 'appointment:'.$booking->id.':remind_1d:sms',
    ]);

    visitLog()->assertOk()->assertSee('In flight');
});

// ── SMS activity ───────────────────────────────────────────────────────────

it('gives a failed text its reason, which is the whole point of the table', function () {
    SmsLog::create([
        'recipient'   => '+639171234567',
        'content'     => 'Your booking is confirmed.',
        'segments'    => 1,
        'status'      => 'http_402',
        'fail_reason' => 'Out of Semaphore credits. Top up the account to resume sending.',
        'trigger'     => 'remind_1d',
    ]);

    visitLog()->assertOk()
        ->assertSee('Out of Semaphore credits. Top up the account to resume sending.')
        // The status is shown readably, not as a raw snake_case token.
        ->assertSee('http 402');
});

it('labels a simulated text as simulated, never as sent or failed', function () {
    SmsLog::create([
        'recipient'    => '+639171234567',
        'content'      => 'Your booking is confirmed.',
        'segments'     => 1,
        'status'       => SmsLog::SIMULATED,
        'reference_id' => 'sim-abc123',
        'trigger'      => 'remind_1d',
    ]);

    visitLog()->assertOk()->assertSee('Simulated');

    $log = SmsLog::sole();

    // The three outcomes are mutually exclusive, which is what lets the page
    // and the summary counts agree.
    expect($log->simulated())->toBeTrue()
        ->and($log->succeeded())->toBeFalse()
        ->and($log->failed())->toBeFalse();
});

it('does not count a simulated text among the failures', function () {
    // The regression this guards: before 'simulated' existed as its own state,
    // scopeFailed() was "not accepted", so every simulated row inflated the
    // failure count and the page showed red for a machine working as intended.
    foreach ([SmsLog::SIMULATED, SmsLog::SIMULATED, 'http_401'] as $i => $status) {
        SmsLog::create([
            'recipient' => '+63917123456'.$i,
            'content'   => 'Reminder.',
            'segments'  => 1,
            'status'    => $status,
        ]);
    }

    expect(SmsLog::failures()->count())->toBe(1)
        ->and(SmsLog::simulations()->count())->toBe(2);
});

it('counts segments rather than messages, because that is what is billed', function () {
    // A template that quietly grew past 160 characters costs twice as much per
    // reminder, and nothing else in the panel would show it.
    SmsLog::create(['recipient' => '+639171234567', 'content' => 'a', 'segments' => 3, 'status' => 'sent']);
    SmsLog::create(['recipient' => '+639171234568', 'content' => 'b', 'segments' => 2, 'status' => 'queued']);
    // Neither of these was billed.
    SmsLog::create(['recipient' => '+639171234569', 'content' => 'c', 'segments' => 9, 'status' => SmsLog::SIMULATED]);
    SmsLog::create(['recipient' => '+639171234560', 'content' => 'd', 'segments' => 9, 'status' => 'http_402']);

    visitLog()->assertOk()->assertSee('Segments Billed');

    expect((int) SmsLog::whereIn('status', SmsLog::ACCEPTED)->sum('segments'))->toBe(5);
});

// ── Attribution ────────────────────────────────────────────────────────────

it('attributes a maintenance text to its vehicle, which had nowhere to go before', function () {
    // SendMaintenanceReminders always passed vehicle_id, but it was neither a
    // column nor fillable, so Eloquent discarded it and the row was tied to
    // nothing — a maintenance text could only be traced by matching a phone
    // number against a message body.
    $user    = logCustomer();
    $vehicle = logVehicle($user);

    $log = SmsLog::create([
        'vehicle_id' => $vehicle->id,
        'recipient'  => '+639171234567',
        'content'    => 'Your Vios is due for servicing.',
        'segments'   => 1,
        'status'     => 'sent',
        'trigger'    => 'maintenance_due',
    ]);

    expect($log->fresh()->vehicle_id)->toBe($vehicle->id)
        ->and($log->fresh()->subjectVehicle()->id)->toBe($vehicle->id);

    visitLog()->assertOk()
        ->assertSee($vehicle->display_plate)
        ->assertSee('Service due');
});

it('reaches an appointment text\'s vehicle through its booking', function () {
    // The other half: an appointment send records booking_id and no vehicle_id,
    // so callers must not have to know which kind of row they are holding.
    $user    = logCustomer();
    $vehicle = logVehicle($user);
    $booking = logBooking($user, $vehicle);

    $log = SmsLog::create([
        'booking_id' => $booking->id,
        'recipient'  => '+639171234567',
        'content'    => 'Your booking is tomorrow.',
        'segments'   => 1,
        'status'     => 'sent',
        'trigger'    => 'remind_1d',
    ]);

    expect($log->fresh()->vehicle_id)->toBeNull()
        ->and($log->fresh()->subjectVehicle()->id)->toBe($vehicle->id);
});

// ── The driver banner ──────────────────────────────────────────────────────

it('warns that nothing is being delivered while the log driver is active', function () {
    config(['services.sms.driver' => 'log']);

    visitLog()->assertOk()
        ->assertSee('SMS is in simulation mode')
        ->assertSee('SMS_DRIVER');
});

it('says which provider is sending once one is configured', function () {
    config([
        'services.sms.driver'       => 'unisms',
        'services.unisms.key'       => 'k',
        'services.unisms.sender_id' => 'APXMOTORS',
    ]);

    visitLog()->assertOk()
        ->assertSee('Sending live through')
        ->assertSee('unisms')
        ->assertDontSee('SMS is in simulation mode');
});

// ── Empty state ────────────────────────────────────────────────────────────

it('reads an empty log without breaking', function () {
    visitLog()->assertOk()
        ->assertSee('No reminders have been sent yet.')
        ->assertSee('No SMS has been attempted yet.');
});
