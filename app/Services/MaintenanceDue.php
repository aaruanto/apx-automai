<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\MaintenanceRule;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Which vehicles are due for servicing, and for what.
 *
 * This is the half of the notification system the paper describes and the
 * build did not have: the system telling a customer their car needs looking
 * at, rather than only reminding them of an appointment they already made.
 *
 * It computes and reports. It sends nothing and writes nothing, so it can be
 * asserted against directly in a test and reused by the customer dashboard to
 * show the same answer on screen that the email will give.
 */
class MaintenanceDue
{
    /**
     * Every rule that is due, or due within its lead time, for one vehicle.
     *
     * @return Collection<int, array{rule: MaintenanceRule, due_date: Carbon, reason: string, overdue: bool}>
     */
    public function forVehicle(Vehicle $vehicle, ?Collection $rules = null, ?Carbon $asOf = null): Collection
    {
        $asOf     = $asOf ? $asOf->copy()->startOfDay() : Carbon::today();
        $rules  ??= MaintenanceRule::active()->with(['bundle', 'service'])->orderBy('sort_order')->get();
        $baseline = $this->baselineFor($vehicle);

        // A vehicle with no service history and no record of when it was added
        // gives us nothing to count from. Saying nothing beats guessing.
        if (! $baseline) {
            return collect();
        }

        $sinceLastService = $vehicle->mileage !== null && $vehicle->mileage_at_last_service !== null
            ? max(0, $vehicle->mileage - $vehicle->mileage_at_last_service)
            : null;

        return $rules
            ->filter(fn (MaintenanceRule $rule) => $rule->appliesTo($vehicle))
            ->map(function (MaintenanceRule $rule) use ($baseline, $asOf, $sinceLastService) {
                $dueDate = $rule->interval_months
                    ? $baseline->copy()->addMonthsNoOverflow($rule->interval_months)
                    : null;

                // Distance can bring a service forward but never push it back:
                // a car driven hard needs the work sooner, and one barely
                // driven still needs it on time, because oil ages in the sump.
                $dueByDistance = $rule->interval_km
                    && $sinceLastService !== null
                    && $sinceLastService >= $rule->interval_km;

                if (! $dueDate && ! $dueByDistance) {
                    return null;
                }

                if ($dueByDistance) {
                    return [
                        'rule'     => $rule,
                        'due_date' => $dueDate && $dueDate->lt($asOf) ? $dueDate : $asOf->copy(),
                        'reason'   => number_format($sinceLastService).' km since the last service',
                        'overdue'  => true,
                    ];
                }

                $tellThemOn = $dueDate->copy()->subDays($rule->lead_days);

                if ($asOf->lt($tellThemOn)) {
                    return null;
                }

                return [
                    'rule'     => $rule,
                    'due_date' => $dueDate,
                    'reason'   => $rule->interval_months.' months since the last service',
                    'overdue'  => $asOf->gt($dueDate),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * The date to count intervals from.
     *
     * last_service_date is maintained whenever a booking is completed, so it
     * is the answer for any vehicle that has been in. For one that has not,
     * counting from when it was registered is the honest fallback: it stops a
     * brand new vehicle from being declared overdue on the day it is added.
     */
    public function baselineFor(Vehicle $vehicle): ?Carbon
    {
        if ($vehicle->last_service_date) {
            return Carbon::parse($vehicle->last_service_date)->startOfDay();
        }

        return $vehicle->created_at ? $vehicle->created_at->copy()->startOfDay() : null;
    }

    /**
     * Whether this customer has already acted on a reminder.
     *
     * Someone with work booked in does not need telling their car is due —
     * that is exactly what the booking is. Checked per vehicle, since a
     * household may have several.
     */
    public function hasUpcomingBooking(Vehicle $vehicle): bool
    {
        return Booking::where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->whereDate('booking_date', '>=', Carbon::today())
            ->exists();
    }
}
