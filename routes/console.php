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
