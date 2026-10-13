<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Whether Laravel's scheduler is actually running.
 *
 * The admin screen used to assert this: a hard-coded "Running" badge, a last
 * run of now(), and a countdown to a fixed 8:00 AM that had nothing to do with
 * the schedule. It reported health whether or not anything was running, which
 * is worse than reporting nothing.
 *
 * A heartbeat task in routes/console.php writes a timestamp every minute. If
 * that value is recent the scheduler is alive; if it is old or missing, it is
 * not, and the screen says so.
 */
class SchedulerStatus
{
    /** A tick may be missed under load, so allow a little slack. */
    private const STALE_AFTER_MINUTES = 5;

    public static function lastRun(): ?Carbon
    {
        $value = Setting::get('scheduler_last_run');

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isRunning(): bool
    {
        $last = self::lastRun();

        return $last !== null && $last->diffInMinutes(now()) < self::STALE_AFTER_MINUTES;
    }

    /** When the next tick is due, or null when nothing is running. */
    public static function nextRun(): ?Carbon
    {
        if (! self::isRunning()) {
            return null;
        }

        return self::lastRun()->copy()->addMinute();
    }

    /** Everything the status card needs, in one call. */
    public static function summary(): array
    {
        $last = self::lastRun();

        return [
            'running'  => self::isRunning(),
            'last_run' => $last?->format('M d, Y g:i A'),
            'last_ago' => $last?->diffForHumans(),
            'next_run' => self::nextRun()?->format('g:i A'),
        ];
    }
}
