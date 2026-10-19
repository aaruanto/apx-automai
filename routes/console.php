<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| The schedule
|--------------------------------------------------------------------------
|
| Nothing here ticks on its own in production. Render's free tier runs one web
| service with no cron jobs, no worker process and no shell to start
| `schedule:work` in, so an external cron service calls /cron/schedule every
| five minutes and Laravel decides on each call what is actually due. The
| frequencies below are therefore real, but they only land on five-minute
| boundaries — which is why nothing here is finer-grained than that.
|
| Locally, run `php artisan schedule:work` alongside `php artisan serve`.
|
*/

// Appointment reminders. Hourly rather than every minute because the day
// windows (7d/3d/1d) match on a date, so a tick every 60 seconds asked the
// same question 1,440 times a day. The reminders table makes each send
// exactly-once regardless, but there is no reason to do the work.
Schedule::command('reminders:send')->hourly();

// Maintenance reminders: "your vehicle is due for servicing". Once a day is
// the natural frequency — a service interval is measured in months — and 8am
// so nobody is woken by a text about their oil.
Schedule::command('reminders:maintenance')->dailyAt('08:00');

// No-shows: cancel pending/confirmed bookings whose grace period (see
// BookingAvailability::GRACE_MINUTES) has elapsed with no arrival.
Schedule::command('bookings:cancel-no-shows')->everyFiveMinutes();

// Anonymize accounts whose 30-day deletion grace period has run out.
Schedule::command('accounts:purge-expired')->dailyAt('03:00');

// Retire loyalty points past their expiry date. Daily is often enough:
// points expire on a date, not at an hour, and the command is a no-op while
// loyalty is switched off.
Schedule::command('loyalty:expire')->dailyAt('03:30');

// Heartbeat. The admin Message Templates screen used to claim the scheduler
// was "Running" from hard-coded markup, with a last-run time of now() and a
// countdown to a fixed 8:00 AM, so it reported health whether or not anything
// was actually running. This records a real timestamp each tick, and the
// screen reads it instead.
Schedule::call(function () {
    \App\Models\Setting::set('scheduler_last_run', now()->toIso8601String());
})->everyFiveMinutes()->name('scheduler-heartbeat')->withoutOverlapping();
