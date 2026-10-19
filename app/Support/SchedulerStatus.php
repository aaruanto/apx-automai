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
 * A heartbeat task in routes/console.php writes a timestamp on every tick. If
 * that value is recent the scheduler is alive; if it is old or missing, it is
 * not, and the screen says so.
 *
 * In production the ticking is done by an external cron service calling
 * /cron/schedule, because Render's free tier has no cron jobs, no worker and
 * no shell. So this is also the readout for whether that cron is still
 * configured and reaching the app.
 */
class SchedulerStatus
{
    /** How often the heartbeat is expected to run. Keep in step with console.php. */
    private const TICK_MINUTES = 5;

    /**
     * Tolerate one missed tick plus drift.
     *
     * This was equal to the tick interval, which meant a heartbeat arriving a
     * few seconds late read as "not running" — and once the ticking moved to
     * an external cron, whose timing nobody controls, that would have been
     * most of the time.
     */
    private const STALE_AFTER_MINUTES = self::TICK_MINUTES * 2 + 2;

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

        return self::lastRun()->copy()->addMinutes(self::TICK_MINUTES);
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
