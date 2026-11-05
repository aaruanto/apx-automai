<?php

use App\Models\Booking;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * The two-hour reminder, and the tick interval it needs in order to exist.
 *
 * Every other window matches on a date, so any tick on the right day
 * satisfies it. This one asks for a start time inside a 15-minute band
 * beginning at now()+2h, which makes the tick interval part of the feature
 * rather than a performance choice: it decides which bookings are ever looked
 * at. On an hourly tick the only bands examined are [H+2:00, H+2:15], and the
 * booking grid is 30 minutes — so every booking on a :30 start sat in the
 * three quarters of the hour nobody looked at and was never reminded at all.
 *
 * The second half of that is which minutes the schedule is *due* in, since
 * `schedule:run` runs only what is due in the minute it is called and in
 * production that minute is whenever an external cron service happens to
 * call. Hence everyFiveMinutes() rather than a stricter frequency.
 *
 * That arithmetic is written down below because it is invisible in the code
 * and a green suite proved nothing about it: the window had no test.
 *
 * Fixed to a real Sunday rather than a relative date. The band is
 * time-of-day arithmetic and reads better against the clock it runs on.
 */
beforeEach(function () {
    // The array transport, not Mail::fake(): the command sends with
    // Mail::html(), and MailFake records only Mailable objects.
    config(['mail.default' => 'array']);

    // An in-memory disk. These tests save a template, and without this they
    // would rewrite the bodies and toggles an admin last saved on this
    // machine.
    Storage::fake('local');
    Storage::delete(MessageTemplate::PATH);
});

afterEach(function () {
    Carbon::setTestNow();
});

/** The shipped template with only the 2h trigger armed. */
function onlyTwoHourArmed(): void
{
    $template = MessageTemplate::defaults();

    $template['triggers']['remind_7d'] = false;
    $template['triggers']['remind_3d'] = false;
    $template['triggers']['remind_1d'] = false;
    $template['triggers']['remind_2h'] = true;

    MessageTemplate::save($template);
}

/** A confirmed booking at $time on whatever day the clock currently says. */
function bookingAt(string $time): Booking
{
    static $n = 1;

    $user = User::factory()->create([
        'role'      => 'customer',
        'is_active' => true,
        'is_guest'  => false,
        'phone'     => '09458678716',
    ]);

    $vehicle = Vehicle::create([
        'user_id'      => $user->id,
        'make'         => 'Toyota',
        'model'        => 'Vios',
        'year'         => 2020,
        'plate_number' => 'TWO '.(1000 + $n),
        'color'        => 'Red',
        'vehicle_type' => 'car',
    ]);

    $service = Service::create([
        'name' => 'Two-hour svc '.$n, 'price' => 500, 'duration' => 30,
    ]);

    return Booking::create([
        'user_id'          => $user->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'booking_date'     => Carbon::today()->toDateString(),
        'booking_time'     => $time,
        'duration'         => 30,
        'status'           => 'confirmed',
        'reference_number' => 'APX-2H-'.$n++,
    ]);
}

/** @return \Illuminate\Support\Collection<int, \Symfony\Component\Mailer\SentMessage> */
function twoHourMail(): \Illuminate\Support\Collection
{
    return collect(Mail::getSymfonyTransport()->messages());
}

/** Run the sweep as the scheduler would, at each of these times on the day. */
function tickAt(string ...$times): void
{
    foreach ($times as $time) {
        Carbon::setTestNow(Carbon::parse('2026-11-08 '.$time));
        test()->artisan('reminders:send')->assertSuccessful();
    }
}

// ── The band ──────────────────────────────────────────────────────────────

it('reminds a booking two hours out, by email and by text', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-08 09:30:00'));
    onlyTwoHourArmed();

    $booking = bookingAt('11:30:00');

    $this->artisan('reminders:send')->assertSuccessful();

    $claim = Reminder::where('trigger_key', 'remind_2h')->where('channel', 'email')->sole();

    expect($claim->booking_id)->toBe($booking->id)
        ->and($claim->status)->toBe('sent')
        ->and(twoHourMail())->toHaveCount(1);

    // And the text, which is the half that costs money and the half a panel
    // watching a handset will actually see.
    $sms = SmsLog::where('trigger', 'remind_2h')->sole();

    expect($sms->booking_id)->toBe($booking->id)
        ->and($sms->recipient)->toBe('+639458678716')
        ->and($sms->segments)->toBe(1)
        ->and($sms->content)->toContain('November 8, 2026')
        ->and($sms->content)->toContain('11:30 AM')
        ->and($sms->content)->toContain($booking->reference_number)
        ->and($sms->content)->not->toContain('{');
});

it('leaves a booking alone until a tick lands in its band', function () {
    // 09:00 looks at 11:00 to 11:15. An 11:30 booking is not in it.
    Carbon::setTestNow(Carbon::parse('2026-11-08 09:00:00'));
    onlyTwoHourArmed();
    bookingAt('11:30:00');

    $this->artisan('reminders:send')->assertSuccessful();

    expect(Reminder::count())->toBe(0)
        ->and(twoHourMail())->toHaveCount(0);
});

it('will not remind an unconfirmed booking, whatever the clock says', function () {
    // Every window filters status = confirmed, so a booking left pending gets
    // nothing at all — the likeliest way to arrive at a silent demonstration.
    Carbon::setTestNow(Carbon::parse('2026-11-08 09:30:00'));
    onlyTwoHourArmed();

    $booking = bookingAt('11:30:00');
    $booking->update(['status' => 'pending']);

    $this->artisan('reminders:send')->assertSuccessful();

    expect(Reminder::count())->toBe(0)
        ->and(twoHourMail())->toHaveCount(0);
});

// ── Why the tick interval is part of the feature ──────────────────────────

it('never sees a booking on a half-hour start if it only ticks hourly', function () {
    // The hazard itself, written down. Not what the app does any more — if a
    // later change puts this schedule back on the hour, this is the test that
    // says what breaks.
    Carbon::setTestNow(Carbon::parse('2026-11-08 08:00:00'));
    onlyTwoHourArmed();
    bookingAt('11:30:00');

    tickAt('08:00', '09:00', '10:00', '11:00', '12:00', '13:00');

    expect(Reminder::where('trigger_key', 'remind_2h')->count())->toBe(0)
        ->and(twoHourMail())->toHaveCount(0);
});

it('sees it on a five-minute tick, which is what is scheduled', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-08 08:00:00'));
    onlyTwoHourArmed();
    $booking = bookingAt('11:30:00');

    tickAt('09:00', '09:05', '09:10', '09:15', '09:20');

    $claim = Reminder::where('trigger_key', 'remind_2h')->where('channel', 'email')->sole();

    expect($claim->booking_id)->toBe($booking->id)
        ->and(twoHourMail())->toHaveCount(1);
});

it('sends once across a whole morning of ticks', function () {
    // The property a panel is most likely to probe: the band is 15 minutes
    // wide and the ticks are five minutes apart, so up to four consecutive
    // ticks see the same booking. The unique dedupe_key is what makes that one
    // send rather than four.
    Carbon::setTestNow(Carbon::parse('2026-11-08 08:00:00'));
    onlyTwoHourArmed();
    bookingAt('11:30:00');

    tickAt('09:00', '09:05', '09:10', '09:15', '09:20', '09:25', '09:30',
        '09:35', '09:40', '09:45', '10:00', '10:30');

    expect(twoHourMail())->toHaveCount(1)
        ->and(SmsLog::where('trigger', 'remind_2h')->count())->toBe(1)
        // One claim per channel, which is what "once" means here.
        ->and(Reminder::where('trigger_key', 'remind_2h')->count())->toBe(2);
});

it('is scheduled on every five-minute boundary, not a subset of them', function () {
    // `schedule:run` runs only what is due in the minute it is called, so the
    // frequency fires only when the external cron's call lands on one of its
    // minutes. Every multiple of five is the widest set of minutes the cron
    // can be relied on to hit — it is the same set the heartbeat uses, and the
    // heartbeat is demonstrably firing in production.
    $this->artisan('schedule:list')
        ->expectsOutputToContain('*/5 * * * *')
        ->assertSuccessful();
});

// ── The on-demand door ────────────────────────────────────────────────────

it('runs the reminder sweep on demand through the cron endpoint', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    Carbon::setTestNow(Carbon::parse('2026-11-08 09:30:00'));
    onlyTwoHourArmed();
    bookingAt('11:30:00');

    $this->get('/cron/reminders?token=correct-horse-battery-staple')
        ->assertOk()
        ->assertSee('ok reminders');

    expect(twoHourMail())->toHaveCount(1)
        ->and(SmsLog::where('trigger', 'remind_2h')->count())->toBe(1);
});

it('sends nothing further however often that door is used', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    Carbon::setTestNow(Carbon::parse('2026-11-08 09:30:00'));
    onlyTwoHourArmed();
    bookingAt('11:30:00');

    foreach (range(1, 5) as $ignored) {
        $this->get('/cron/reminders?token=correct-horse-battery-staple')->assertOk();
    }

    expect(twoHourMail())->toHaveCount(1)
        ->and(SmsLog::where('trigger', 'remind_2h')->count())->toBe(1);
});

it('still refuses a task that is not on the allow list', function () {
    // Adding a second entry must not have turned the task into a parameter.
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    $this->get('/cron/migrate:fresh?token=correct-horse-battery-staple')->assertNotFound();
    $this->get('/cron/reminders:maintenance?token=correct-horse-battery-staple')->assertNotFound();
});

it('guards the new door with the same token as the old one', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    $this->get('/cron/reminders')->assertNotFound();
    $this->get('/cron/reminders?token=wrong')->assertNotFound();

    config(['apx.cron_token' => '']);
    $this->get('/cron/reminders?token=')->assertNotFound();
});
