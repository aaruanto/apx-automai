<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingAvailability;
use App\Services\BookingCancellation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CancelNoShowBookings extends Command
{
    protected $signature   = 'bookings:cancel-no-shows';
    protected $description = 'Auto-cancel pending/confirmed bookings whose grace period has elapsed with no arrival';

    public function __construct(private BookingCancellation $cancellation)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $cutoff = now()->subMinutes(BookingAvailability::GRACE_MINUTES);

        // Active bookings that haven't been marked "in_progress" (arrived) are
        // no-show candidates once start + GRACE_MINUTES has passed. Comparing
        // in PHP (rather than a raw SQL date-math WHERE) keeps this correct
        // regardless of the DB driver's string date/time handling — this
        // schema has drifted from the code before, so don't rely on the DB
        // to parse "date + time" for us.
        $candidates = Booking::whereIn('status', ['pending', 'confirmed'])
            ->whereDate('booking_date', '<=', now()->toDateString())
            ->get();

        $cancelled = 0;

        foreach ($candidates as $booking) {
            $start = Carbon::parse($booking->booking_date . ' ' . $booking->booking_time);

            if ($start->copy()->addMinutes(BookingAvailability::GRACE_MINUTES)->isFuture()) {
                continue;
            }

            // Through the shared service rather than a raw update(). Writing
            // the columns directly went around Booking::cancel() and its own
            // rules, and skipped everything that follows from a cancellation:
            // no audit row, no notification, and the loyalty ledger untouched,
            // so a customer who had redeemed points against a booking the shop
            // then auto-cancelled lost them.
            //
            // cancelled_by stays null, which is what the model already means
            // by "the system did it" — see Booking::cancelledBy().
            if ($this->cancellation->cancel($booking, 'No-show (grace period elapsed)', null)) {
                $cancelled++;
            }
        }

        $this->info("Cancelled {$cancelled} no-show booking(s) (grace period: " . BookingAvailability::GRACE_MINUTES . " min, cutoff {$cutoff}).");

        return self::SUCCESS;
    }
}
