<?php

namespace App\Services;

use App\Models\Reminder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * The record of what has already been sent, and the gate that keeps it to once.
 *
 * Every reminder goes through claim() before it is sent. The claim is an INSERT
 * against a UNIQUE column, so the database decides who wins — not a
 * read-then-write in PHP, which two overlapping scheduler ticks can both pass
 * at the same time and both act on. If the insert is rejected, this reminder
 * has already gone out and the caller does nothing.
 *
 * On a failed send the claim is released, so the next run may try again. That
 * is the right trade for a transient SMTP error, and the cost of a permanent
 * one is bounded by how often the scheduler runs (daily, in production).
 */
class ReminderLedger
{
    /**
     * Reserve the right to send this reminder.
     *
     * @return Reminder|null The claim, or null if it was already sent.
     */
    public function claim(array $attributes): ?Reminder
    {
        $key = Reminder::keyFor(
            $attributes['type'],
            $attributes['subject'],
            $attributes['trigger_key'],
            $attributes['channel'],
        );

        unset($attributes['subject']);

        try {
            return Reminder::create($attributes + [
                'dedupe_key' => $key,
                'status'     => 'claimed',
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already sent, or being sent right now by another tick.
            return null;
        }
    }

    public function markSent(Reminder $reminder): void
    {
        $reminder->update(['status' => 'sent', 'sent_at' => now()]);
    }

    /**
     * Give up the claim so a later run can retry.
     *
     * Deleting rather than flagging is deliberate: a row left behind with
     * status 'failed' still holds the unique key, which would mean one failed
     * attempt silently cancels the reminder for good.
     */
    public function release(Reminder $reminder, string $error): void
    {
        Log::warning("Reminder {$reminder->dedupe_key} failed, releasing claim: {$error}");

        $reminder->delete();
    }

    /** Whether this exact reminder has already gone out. */
    public function alreadySent(string $type, int|string $subject, string $trigger, string $channel): bool
    {
        return Reminder::where('dedupe_key', Reminder::keyFor($type, $subject, $trigger, $channel))->exists();
    }
}
