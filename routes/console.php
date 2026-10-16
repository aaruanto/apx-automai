<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every minute so all reminder windows (7d, 3d, 1d, 2h) are checked
Schedule::command('reminders:send')->everyMinute();

// No-shows: cancel pending/confirmed bookings whose grace period (see
// BookingAvailability::GRACE_MINUTES) has elapsed with no arrival.
// NOTE (dev): the scheduler only fires while `php artisan schedule:work`
// is running — start it alongside `php artisan serve` for this to work locally.
Schedule::command('bookings:cancel-no-shows')->everyMinute();

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
})->everyMinute()->name('scheduler-heartbeat')->withoutOverlapping();
