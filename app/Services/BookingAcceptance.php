<?php

namespace App\Services;

use App\Enums\BookingStatus;
use Carbon\Carbon;

/**
 * Whether a new booking is accepted on the spot, or held for review.
 *
 * The shop used to confirm every booking by hand: everything arrived
 * Pending Review and sat there until an admin agreed to it, which meant a
 * customer booking a slot three weeks out waited on a human for a date
 * nothing else could possibly want. So a booking is now accepted the moment
 * it is made, and there is exactly one exception.
 *
 * ── The exception ──────────────────────────────────────────────────────
 *
 * A booking for TODAY, on a day that has already taken its full intake
 * (Settings -> Max Bookings Per Day), arrives Pending Review instead. It is
 * not refused: the customer cannot usefully be sent off to "choose another
 * day" when the thing they are asking for is same-day service, and the shop
 * is the only party that knows whether it can squeeze one more in. So the
 * booking is taken and the decision is handed to the admin, which is what
 * the existing approve/reject flow already does.
 *
 * Every other date keeps the hard limit it has always had — a day in the
 * future that is full is refused, because picking another one costs the
 * customer nothing.
 *
 * ── Why it is a class ──────────────────────────────────────────────────
 *
 * Because the alternative is this rule written out in the customer
 * controller and again in the guest controller, which is how the seven
 * copies of the status map happened. BookingAvailability::reserve() asks
 * this, and reserve() is the only way a booking can be created — so a
 * fourth booking path written next year cannot get a different answer.
 */
class BookingAcceptance
{
    public function __construct(private BookingAvailability $availability) {}

    /**
     * Where a booking for $date starts its life.
     *
     * $date is a Y-m-d string, as every booking path has it.
     */
    public function statusFor(string $date): BookingStatus
    {
        return $this->needsReview($date)
            ? BookingStatus::PendingReview
            : BookingStatus::BookingConfirmed;
    }

    /** Whether a booking for $date has to wait for the shop to look at it. */
    public function needsReview(string $date): bool
    {
        return $this->isToday($date) && $this->availability->dayIsFull($date);
    }

    /**
     * Whether $date is today in the shop's own timezone.
     *
     * Against Asia/Manila explicitly rather than Carbon's default, because
     * the two differ by eight hours and the mistake is invisible for sixteen
     * of every twenty-four: a booking made at 7am Manila on the 3rd is
     * "yesterday" to a UTC clock, so a day that was full would have taken it
     * as a future booking and auto-confirmed it. config('app.timezone') is
     * already Asia/Manila, so this agrees with the rest of the application
     * by reading the same setting rather than naming the zone again.
     */
    public function isToday(string $date): bool
    {
        $zone = config('app.timezone');

        return Carbon::parse($date, $zone)->isSameDay(Carbon::now($zone));
    }
}
