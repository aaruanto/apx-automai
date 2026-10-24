<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

/**
 * Keeps a vehicle's service history current.
 *
 * Maintenance reminders count forward from the last time a vehicle was in, so
 * that date has to be right or the whole feature is wrong in a way nobody can
 * see. Maintaining it from the booking the shop just completed means it is
 * correct without anyone being asked to remember.
 *
 * Called from every place a booking can reach 'completed'. It is idempotent
 * and only ever moves the date forward, so a re-save, a backdated walk-in or
 * two bookings completed out of order cannot rewind a vehicle's history and
 * make it look overdue when it is not.
 */
class ServiceHistory
{
    public function recordCompletion(Booking $booking): void
    {
        $vehicle = $booking->vehicle;

        if (! $vehicle) {
            return;
        }

        $serviced = Carbon::parse($booking->booking_date)->startOfDay();
        $current  = $vehicle->last_service_date
            ? Carbon::parse($vehicle->last_service_date)->startOfDay()
            : null;

        if ($current && $current->gte($serviced)) {
            return;
        }

        $vehicle->last_service_date = $serviced->toDateString();

        // The odometer is no longer frozen here. It existed so "distance
        // since the last service" could be a subtraction, and nothing asks
        // that question any more — MaintenanceDue is time-only. vehicles.mileage
        // is still whatever the customer last typed; the shop reads it, the
        // system decides nothing from it.

        $vehicle->save();
    }
}
