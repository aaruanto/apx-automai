<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingAvailability;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CancelNoShowBookings extends Command
{
    protected $signature   = 'bookings:cancel-no-shows';
    protected $description = 'Auto-cancel pending/confirmed bookings whose grace period has elapsed with no arrival';

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

            $booking->update([
                'status'       => 'cancelled',
                'cancel_reason' => 'No-show (grace period elapsed)',
                'cancelled_by' => null,
                'cancelled_at' => now(),
            ]);

            $cancelled++;
        }

        $this->info("Cancelled {$cancelled} no-show booking(s) (grace period: " . BookingAvailability::GRACE_MINUTES . " min, cutoff {$cutoff}).");

        return self::SUCCESS;
    }
}
