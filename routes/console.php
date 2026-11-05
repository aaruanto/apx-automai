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

// Appointment reminders.
//
// Every five minutes, not hourly, and the 2h window is the whole reason.
// The day windows (7d/3d/1d) match on a date and are satisfied by any tick at
// all; bookingsInTwoHours() instead asks for a start time inside a 15-minute
// band beginning at now()+2h, so one tick only ever examines a quarter of an
// hour. Hourly ticking therefore looked at the bands [H+2:00, H+2:15] and
// nowhere else — and because the slot grid is 30 minutes, every booking on a
// :30 start sat in the three quarters of the hour nobody looked at. Not a
// late reminder: no reminder, ever.
//
// Every five minutes rather than every fifteen, because of how due-ness is
// decided in production. `schedule:run` runs only what is due in the minute
// it is called, so a declared frequency fires only when the external cron's
// call lands on one of its minutes. Every fifteen minutes is due at minutes
// 0, 15, 30 and 45; every five minutes is due at every multiple of five. A
// cron on a ten-minute interval, or one that misses a call, can satisfy the
// second and not the first — and a 2h reminder that misses its band is not
// late, it is never sent. The heartbeat below is already everyFiveMinutes(),
// so a fresh scheduler_last_run is proof the call lands on these minutes and
// therefore on these.
//
// It also means up to three ticks see each 15-minute band rather than one.
// The reminders table makes each send exactly-once however often this runs,
// so those extra ticks cost a few indexed queries and no mail.
Schedule::command('reminders:send')->everyFiveMinutes();

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
