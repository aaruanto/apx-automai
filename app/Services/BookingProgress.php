<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Mail\BookingConfirmed;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Notifications\BookingNotification;
use App\Support\MessageTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Everything that follows from a booking's status changing.
 *
 * All of this used to live inline in Admin\BookingController::update(), which
 * was fine while the edit form was the only way a status could move. The queue
 * board's Advance control is a second way, and a copy of two hundred lines of
 * side effects is how a car advanced on the board ends up earning no loyalty
 * points, never stamping its vehicle's service history, and never texting the
 * customer that it is ready — all of which the edit form does.
 *
 * So it is here, once, and the two callers hand it the before and after.
 * BookingCancellation is the model for this: it exists for exactly the same
 * reason, after three of the four ways of cancelling a booking were found to
 * have skipped the loyalty ledger.
 *
 * ── Exactly once ───────────────────────────────────────────────────────
 *
 * Each effect is gated on the transition and not on the destination, so
 * re-saving a completed booking cannot mint points or re-send a text. That
 * guard is what makes this exactly-once, and it is deliberately NOT a
 * ReminderLedger claim: a reminder matches a date window that stays true all
 * day, where a status transition happens once. LoyaltyService::earnFor() is
 * idempotent on top of it, which is belt and braces on the one effect that
 * moves money.
 *
 * Nothing in here may throw. The booking is already saved by the time this
 * runs; a dead SMS gateway or a Brevo outage must not turn a status the shop
 * has already changed into a 500 on their screen.
 */
class BookingProgress
{
    public function __construct(private SmsService $sms) {}

    /**
     * Apply the consequences of $booking having moved from $from to $to.
     *
     * $booking is expected to be saved already — this reads it, it does not
     * write the status itself. Call it with the same $from the caller read
     * before saving.
     */
    public function statusChanged(Booking $booking, ?BookingStatus $from, BookingStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        AuditLog::record(
            'booking.status_changed', $booking,
            ['status' => $from?->value], ['status' => $to->value],
            label: $booking->reference_number,
        );

        if ($to === BookingStatus::BookingConfirmed) {
            $this->confirmed($booking);
        }

        if ($to === BookingStatus::ServiceCompleted) {
            $this->completed($booking);
        }
    }

    /**
     * The shop has agreed to the slot.
     *
     * This is the one transition that discloses the reference number — see
     * Booking::disclosableReference(). The bell entry, the email and the text
     * are deliberately not all-or-nothing: a customer with a bad address
     * should still get the text.
     */
    private function confirmed(Booking $booking): void
    {
        $booking->refresh()->load(['user', 'service', 'vehicle']);

        if ($booking->user) {
            try {
                $booking->user->notify(BookingNotification::confirmed($booking));
            } catch (\Throwable $e) {
                Log::error('Confirmation notice failed for '.$booking->reference_number.': '.$e->getMessage());
            }
        }

        $template = MessageTemplate::load();

        if (
            $booking->user
            && $booking->user->email
            && ! str_ends_with($booking->user->email, BookingNotification::PLACEHOLDER_DOMAIN)
        ) {
            try {
                Mail::to($booking->user->email)->send(new BookingConfirmed($booking));
            } catch (\Throwable $e) {
                Log::error('Booking confirmation email failed: '.$e->getMessage());
            }
        }

        // SmsService does not throw and writes its own sms_logs row either
        // way, so a failed send cannot roll back a confirmation the admin has
        // already made.
        if (
            ! empty($template['channels']['send_sms'])
            && ! empty($template['triggers']['confirm_booking'])
            && $this->sms->configured()
        ) {
            $phone = $booking->user?->phone ?: $booking->user?->customer?->phone;

            if ($phone) {
                $this->sms->send(
                    $phone,
                    MessageTemplate::fill($template['confirmation_sms'], $booking),
                    ['booking_id' => $booking->id, 'trigger' => 'confirm_booking'],
                );
            }
        }
    }

    /**
     * The work is done.
     *
     * Why the text exists at all: a customer who waited at the counter was
     * told in person, but one who dropped the vehicle off and left has no way
     * to know it is ready. The bell entry reaches them only if they happen to
     * open the app, which is the person this is for.
     *
     * Gated on the SMS channel but not on a trigger of its own — there is no
     * completion toggle on the Message Templates screen yet, and a key the UI
     * cannot set is a switch nobody can find.
     */
    private function completed(Booking $booking): void
    {
        $booking->refresh()->load(['user', 'service', 'vehicle', 'services']);

        // Points are earned when the work is done, never when it is booked,
        // and on the amount actually paid so a discount is not rewarded
        // twice. earnFor is idempotent, so this cannot mint on a re-save.
        try {
            app(LoyaltyService::class)->earnFor($booking);
        } catch (\Throwable $e) {
            Log::error('Loyalty award failed for '.$booking->reference_number.': '.$e->getMessage());
        }

        // Stamp the vehicle's service history. Maintenance reminders count
        // forward from this date, so if it is never written the vehicle looks
        // as though it has not been in since the day it was registered — and
        // the customer is told so, repeatedly.
        try {
            app(ServiceHistory::class)->recordCompletion($booking);
        } catch (\Throwable $e) {
            Log::error('Service history stamp failed for '.$booking->reference_number.': '.$e->getMessage());
        }

        if ($booking->user) {
            try {
                $booking->user->notify(BookingNotification::completed($booking));
            } catch (\Throwable $e) {
                Log::error('Completion notice failed for '.$booking->reference_number.': '.$e->getMessage());
            }
        }

        try {
            $template = MessageTemplate::load();
            $phone    = $booking->user?->phone ?: $booking->user?->customer?->phone;

            if ($phone && ! empty($template['channels']['send_sms']) && $this->sms->configured()) {
                $this->sms->send(
                    $phone,
                    MessageTemplate::fill($template['completion_sms'], $booking),
                    ['booking_id' => $booking->id, 'trigger' => 'complete_booking'],
                );
            }
        } catch (\Throwable $e) {
            Log::error('Completion text failed for '.$booking->reference_number.': '.$e->getMessage());
        }
    }
}
