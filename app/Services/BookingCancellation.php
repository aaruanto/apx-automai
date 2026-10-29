<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Notifications\BookingNotification;
use Illuminate\Support\Facades\Log;

/**
 * Everything that follows from a booking being cancelled.
 *
 * Booking::cancel() writes four fields and deliberately does nothing else.
 * The rest — the audit row, the loyalty ledger, telling the customer — lived
 * inline in the admin controller, so each new way of cancelling a booking had
 * to remember to copy it. Three of the four did not:
 *
 *   customer dashboard   wrote the fields and returned success. No audit, no
 *                        notification, and the ledger untouched — so a
 *                        customer who had redeemed points against the booking
 *                        simply lost them, and one who had earned points for
 *                        work that will now not happen kept them.
 *   no-show sweep        did not even call cancel(): a raw update() past the
 *                        model's own rules, with the same three gaps.
 *   account deletion     wrote the fields only, which is right about the
 *                        notification and wrong about the points.
 *
 * So the consequences live here and every path goes through one call. The
 * only thing that legitimately differs between them is who cancelled and
 * whether the customer should be told.
 */
class BookingCancellation
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    /**
     * @param  bool  $notify  False only where telling the customer is wrong —
     *                        the account-deletion sweep, where the account is
     *                        on its way out and they asked for this.
     * @return bool  False when the booking is past cancelling; nothing is
     *               written and nothing is sent.
     */
    public function cancel(Booking $booking, string $reason, ?int $byUserId = null, bool $notify = true): bool
    {
        if (! $booking->cancel($reason, $byUserId)) {
            return false;
        }

        // Cancellation already stamped who and why on the booking itself; it
        // joins the audit trail too so one page shows every change that moved
        // money, rather than this one living somewhere of its own.
        AuditLog::record(
            'booking.cancelled', $booking,
            ['status' => 'cancelled'], [], $reason,
            label: $booking->reference_number,
        );

        // Work that is not happening earns nothing. Points spent on it are
        // returned, and points it earned are taken back — both idempotent on
        // a unique (booking_id, reason), so a double-submitted cancel cannot
        // refund twice, and both clamped so the shop's own cancellation can
        // never push a balance negative.
        $this->loyalty->reverseFor($booking);
        $this->loyalty->refundSpentFor($booking);

        if ($notify && $booking->user) {
            // The booking is cancelled and the points are reversed by now,
            // both committed. Telling the customer is the last step and the
            // only one that reaches outside this server, so a dead SMTP
            // connection must not turn a completed cancellation into a 500.
            try {
                $booking->user->notify(BookingNotification::cancelled($booking->fresh()));
            } catch (\Throwable $e) {
                Log::error('Cancellation notice failed for '.$booking->reference_number.': '.$e->getMessage());
            }
        }

        return true;
    }
}
