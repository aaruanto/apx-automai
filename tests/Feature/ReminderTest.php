<?php

use App\Models\Booking;
use App\Models\MaintenanceRule;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\MaintenanceDue;
use App\Services\ServiceHistory;
use App\Support\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Automated reminders.
 *
 * Two properties carry this feature. First, a reminder goes out exactly once:
 * before the reminders table existed the day windows matched on a date, so a
 * booking a week out was mailed on every scheduler tick — roughly 1,440 times
 * a day against a 300/day sending limit. Second, a vehicle that is genuinely
 * due for servicing is actually told, which is the half of the notification
 * requirement that had no code behind it at all.
 */
beforeEach(function () {
    // The array transport rather than Mail::fake(). Both reminder commands
    // send with Mail::html(), and MailFake records only Mailable objects —
    // sendMail() returns early for anything else — so assertSentCount() reads
    // zero no matter how much mail the command actually sends. The array
    // transport captures the real composed message instead, which is both
    // assertable and closer to what Brevo will receive.
    config(['mail.default' => 'array']);

    // The stored template is whatever the admin last saved. Tests must not
    // depend on that, so start from the shipped defaults every time.
    Storage::delete(MessageTemplate::PATH);
});

/** @return \Illuminate\Support\Collection<int, \Symfony\Component\Mailer\SentMessage> */
function sentMail(): \Illuminate\Support\Collection
{
    return collect(Mail::getSymfonyTransport()->messages());
}

function reminderCustomer(): User
{
    return User::factory()->create([
        'role'      => 'customer',
        'is_active' => true,
        'is_guest'  => false,
        'phone'     => null,
    ]);
}

function reminderVehicle(User $user, array $attributes = []): Vehicle
{
    static $n = 1000;

    return Vehicle::create(array_merge([
        'user_id'      => $user->id,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2020,
        'plate_number' => 'RMD '.$n++,
        'color'        => 'Red',
        'vehicle_type' => 'car',
    ], $attributes));
}

function reminderService(): Service
{
    static $n = 1;

    return Service::create(['name' => 'Reminder svc '.$n++, 'price' => 1000, 'duration' => 30]);
}

function reminderBooking(User $user, Vehicle $vehicle, array $attributes = []): Booking
{
    static $n = 1;

    return Booking::create(array_merge([
        'user_id'          => $user->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => reminderService()->id,
        'booking_date'     => Carbon::today()->addDays(7)->toDateString(),
        'booking_time'     => '10:00:00',
        'duration'         => 30,
        'status'           => 'confirmed',
        'reference_number' => 'APX-RMD-'.$n++,
    ], $attributes));
}

function basicRule(array $attributes = []): MaintenanceRule
{
    return MaintenanceRule::create(array_merge([
        'name'            => 'Basic PMS due',
        'interval_months' => 6,
        'lead_days'       => 14,
        'is_active'       => true,
    ], $attributes));
}

// ── Appointment reminders: exactly once ───────────────────────────────────

it('sends an appointment reminder once, however many times the scheduler ticks', function () {
    $user    = reminderCustomer();
    $vehicle = reminderVehicle($user);

    reminderBooking($user, $vehicle);

    // Ten ticks, as the scheduler would make in ten minutes.
    foreach (range(1, 10) as $ignored) {
        $this->artisan('reminders:send')->assertSuccessful();
    }

    expect(sentMail())->toHaveCount(1);

    expect(Reminder::where('type', Reminder::TYPE_APPOINTMENT)->count())->toBe(1);
    expect(Reminder::first()->status)->toBe('sent');
});

it('reminds separately for each window, and only on the right day', function () {
    $user    = reminderCustomer();
    $vehicle = reminderVehicle($user);

    reminderBooking($user, $vehicle, [
        'booking_date' => Carbon::today()->addDays(7)->toDateString(),
    ]);

    $this->artisan('reminders:send');
    expect(Reminder::where('trigger_key', 'remind_7d')->count())->toBe(1);
    expect(Reminder::where('trigger_key', 'remind_3d')->count())->toBe(0);

    // Four days later the same booking is three days out, and that is a
    // different reminder — not a duplicate of the first.
    Carbon::setTestNow(Carbon::today()->addDays(4));
    $this->artisan('reminders:send');

    expect(Reminder::where('trigger_key', 'remind_3d')->count())->toBe(1);
    expect(sentMail())->toHaveCount(2);

    Carbon::setTestNow();
});

it('leaves unconfirmed and cancelled bookings alone', function () {
    $user = reminderCustomer();

    foreach (['pending', 'cancelled', 'completed'] as $status) {
        reminderBooking($user, reminderVehicle($user), ['status' => $status]);
    }

    $this->artisan('reminders:send');

    expect(sentMail())->toHaveCount(0);
    expect(Reminder::count())->toBe(0);
});

it('does not send when the trigger is switched off', function () {
    $template = MessageTemplate::defaults();
    $template['triggers']['remind_7d'] = false;
    $template['triggers']['remind_3d'] = false;
    $template['triggers']['remind_1d'] = false;
    MessageTemplate::save($template);

    $user = reminderCustomer();
    reminderBooking($user, reminderVehicle($user));

    $this->artisan('reminders:send');

    expect(sentMail())->toHaveCount(0);
});

// ── Maintenance reminders ─────────────────────────────────────────────────

it('tells a customer when their vehicle is due for servicing', function () {
    $rule    = basicRule();
    $user    = reminderCustomer();
    $vehicle = reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(6)->toDateString(),
    ]);

    $this->artisan('reminders:maintenance')->assertSuccessful();

    expect(sentMail())->toHaveCount(1);

    $reminder = Reminder::where('type', Reminder::TYPE_MAINTENANCE)->first();
    expect($reminder)->not->toBeNull();
    expect($reminder->vehicle_id)->toBe($vehicle->id);
    expect($reminder->maintenance_rule_id)->toBe($rule->id);
});

it('sends a maintenance reminder once per interval, not once per run', function () {
    basicRule();
    $user = reminderCustomer();

    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(6)->toDateString(),
    ]);

    foreach (range(1, 5) as $ignored) {
        $this->artisan('reminders:maintenance');
    }

    expect(sentMail())->toHaveCount(1);
});

it('says nothing about a vehicle that is not due yet', function () {
    basicRule();
    $user = reminderCustomer();

    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(2)->toDateString(),
    ]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(0);
});

it('warns ahead of the due date, by the rule lead time', function () {
    basicRule();
    $user = reminderCustomer();

    // Serviced 5 months and 20 days ago: due in 10 days, inside the 14-day
    // lead time. The customer should hear about it now, while there is still
    // time to book.
    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(6)->addDays(10)->toDateString(),
    ]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(1);
});

it('stays quiet when the customer already has work booked in', function () {
    basicRule();
    $user    = reminderCustomer();
    $vehicle = reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(6)->toDateString(),
    ]);

    reminderBooking($user, $vehicle, [
        'booking_date' => Carbon::today()->addDays(3)->toDateString(),
    ]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(0);
});

it('brings a service forward when the vehicle has covered the distance', function () {
    basicRule(['interval_km' => 5000]);
    $user = reminderCustomer();

    // Serviced only a month ago, so nowhere near due on time — but 6,000 km
    // in that month is.
    reminderVehicle($user, [
        'last_service_date'       => Carbon::today()->subMonth()->toDateString(),
        'mileage_at_last_service' => 24000,
        'mileage'                 => 30000,
    ]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(1);
});

it('ignores mileage when the customer has not given a reading', function () {
    MaintenanceRule::create([
        'name'        => 'Distance only',
        'interval_km' => 5000,
        'lead_days'   => 14,
        'is_active'   => true,
    ]);

    $user = reminderCustomer();
    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(18)->toDateString(),
        'mileage'           => null,
    ]);

    $this->artisan('reminders:maintenance');

    // A distance-only rule has nothing to say about a vehicle with no
    // odometer reading. Most customers do not know theirs, so this must be
    // silence rather than a guess.
    expect(sentMail())->toHaveCount(0);
});

it('does not declare a newly registered vehicle overdue', function () {
    basicRule();
    $user = reminderCustomer();

    reminderVehicle($user, ['last_service_date' => null]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(0);
});

it('skips rules that do not apply to the vehicle type', function () {
    MaintenanceRule::create([
        'name'            => 'Motorcycle chain service',
        'interval_months' => 3,
        'lead_days'       => 7,
        'vehicle_type'    => 'motorcycle',
        'is_active'       => true,
    ]);

    $user = reminderCustomer();
    reminderVehicle($user, [
        'vehicle_type'      => 'car',
        'last_service_date' => Carbon::today()->subYear()->toDateString(),
    ]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(0);
});

it('sends one message for a vehicle behind on several rules', function () {
    foreach ([6, 12, 24] as $months) {
        MaintenanceRule::create([
            'name'            => "Due at {$months} months",
            'interval_months' => $months,
            'lead_days'       => 14,
            'is_active'       => true,
            'sort_order'      => $months,
        ]);
    }

    $user = reminderCustomer();
    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(30)->toDateString(),
    ]);

    $this->artisan('reminders:maintenance');

    // Three emails for one car reads as spam and buries the point.
    expect(sentMail())->toHaveCount(1);
});

it('ignores inactive rules', function () {
    basicRule(['name' => 'Switched off', 'is_active' => false]);

    $user = reminderCustomer();
    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subYear()->toDateString(),
    ]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(0);
});

it('ignores deactivated accounts', function () {
    basicRule();

    $user = reminderCustomer();
    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subYear()->toDateString(),
    ]);

    $user->update(['is_active' => false]);

    $this->artisan('reminders:maintenance');

    expect(sentMail())->toHaveCount(0);
});

// ── Service history upkeep ────────────────────────────────────────────────

it('stamps the vehicle service date when a booking completes', function () {
    $user    = reminderCustomer();
    $vehicle = reminderVehicle($user, ['mileage' => 42000]);

    $booking = reminderBooking($user, $vehicle, [
        'booking_date' => Carbon::today()->toDateString(),
        'status'       => 'completed',
    ]);

    app(ServiceHistory::class)->recordCompletion($booking->load('vehicle'));

    $vehicle->refresh();
    expect($vehicle->last_service_date->toDateString())->toBe(Carbon::today()->toDateString());
    expect($vehicle->mileage_at_last_service)->toBe(42000);
});

it('never rewinds the service date', function () {
    $user    = reminderCustomer();
    $vehicle = reminderVehicle($user, [
        'last_service_date' => Carbon::today()->toDateString(),
    ]);

    // A walk-in entered after the fact, dated last month. Accepting it would
    // make the vehicle look overdue when it was serviced today.
    $backdated = reminderBooking($user, $vehicle, [
        'booking_date' => Carbon::today()->subMonth()->toDateString(),
        'status'       => 'completed',
    ]);

    app(ServiceHistory::class)->recordCompletion($backdated->load('vehicle'));

    $vehicle->refresh();
    expect($vehicle->last_service_date->toDateString())->toBe(Carbon::today()->toDateString());
});

// ── The due calculation on its own ────────────────────────────────────────

it('counts intervals from the last service, not from today', function () {
    $rules = collect([basicRule(['lead_days' => 0])]);

    $user    = reminderCustomer();
    $vehicle = reminderVehicle($user, ['last_service_date' => '2026-01-15']);

    $due = app(MaintenanceDue::class)
        ->forVehicle($vehicle, $rules, Carbon::parse('2026-07-15'));

    expect($due)->toHaveCount(1);
    expect($due->first()['due_date']->toDateString())->toBe('2026-07-15');

    // A day earlier, with no lead time, it is not due.
    expect(app(MaintenanceDue::class)->forVehicle($vehicle, $rules, Carbon::parse('2026-07-14')))
        ->toHaveCount(0);
});

// ── What the customer sees ────────────────────────────────────────────────

it('shows the service-due state on the customer dashboard', function () {
    basicRule();

    $user = reminderCustomer();
    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonths(8)->toDateString(),
        'mileage'           => 48000,
    ]);

    $html = $this->actingAs($user)->get('/customer/dashboard')->assertOk()->getContent();

    // The dashboard reads the same MaintenanceDue calculation the emails do,
    // so the page and the inbox cannot disagree about whether a car is due.
    expect($html)->toContain('Basic PMS due');
    expect($html)->toContain('48000');
});

it('shows no service-due state when nothing is outstanding', function () {
    basicRule();

    $user = reminderCustomer();
    reminderVehicle($user, [
        'last_service_date' => Carbon::today()->subMonth()->toDateString(),
    ]);

    $html = $this->actingAs($user)->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->not->toContain('Basic PMS due');
});
