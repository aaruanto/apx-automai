<?php

namespace App\Support;

use App\Models\Bundle;
use Illuminate\Support\Collection;

/**
 * Rules about what makes a bookable booking, shared by every path that takes one.
 *
 * The catalogue carries three ₱0 services, and the shop's own wording already
 * states the condition — "Complimentary exterior car wash with any paid
 * service". Nothing enforced it, so a booking could consist of nothing but
 * free work: a real slot, a real half hour of someone's day, and no work to
 * do when the customer arrived. Staff would then either do it unpaid or turn
 * the customer away, having been told on the website that it was fine.
 *
 * This is not an abuse guard. No booking is paid up front, so anyone set on
 * filling the calendar could do it with paid services just as easily. It is
 * here so the system honours the policy the shop has already published.
 */
class BookingPolicy
{
    /** Shown to the customer, so it says what to do rather than what went wrong. */
    public const FREE_ONLY_MESSAGE = 'Our complimentary services come with paid work. Please add at least one other service to your booking.';

    /**
     * Whether this selection is complimentary work and nothing else.
     *
     * A bundle is sold at its own price, so a booking carrying one is paid
     * work whatever it contains. An empty selection is not this rule's
     * business — both booking paths already reject that in validation, and
     * answering true here would replace their clear message with this one.
     */
    public static function isFreeOnly(Collection $services, ?Bundle $bundle = null): bool
    {
        if ($bundle) {
            return false;
        }

        if ($services->isEmpty()) {
            return false;
        }

        return $services->every(fn ($service) => (float) $service->price <= 0);
    }

    /**
     * The chosen services that are not offered for this vehicle.
     *
     * services.vehicle_type and services.transmission follow the same
     * convention as bundles and maintenance rules — null applies to
     * everything — and the comparison itself lives in one place,
     * AppliesToVehicle.
     *
     * All three booking paths hide these in the UI, which is presentation,
     * not enforcement: the browser decides what to post and is not trusted.
     *
     * A null type means no vehicle has been identified yet, so there is
     * nothing to check against and nothing is refused. $transmission is
     * independently optional for a different reason: the column is nullable
     * and plenty of vehicles predate it, so "not recorded" must not refuse a
     * booking that was fine yesterday. The trait reads both absences as "do
     * not narrow on this".
     *
     * @return Collection<int, \App\Models\Service>
     */
    public static function servicesNotFor(
        Collection $services,
        ?string $vehicleType,
        ?string $transmission = null
    ): Collection {
        $noType         = $vehicleType === null || $vehicleType === '';
        $noTransmission = $transmission === null || $transmission === '';

        if ($noType && $noTransmission) {
            return collect();
        }

        return $services
            ->reject(fn ($service) => $service->suitsVehicle(
                $noType ? null : $vehicleType,
                $noTransmission ? null : $transmission
            ))
            ->values();
    }

    /**
     * Names the work rather than the rule, so the customer knows what to drop.
     *
     * Says which of the two conditions each service failed, because "not
     * offered for cars" on an automatic-only service sent customers back to
     * the vehicle type to change something that was already right. Where a
     * service fails both, the type is named: it is the coarser fact and the
     * one more likely to be the real mistake.
     */
    public static function wrongVehicleMessage(
        Collection $offending,
        ?string $vehicleType,
        ?string $transmission = null
    ): string {
        $byType = $offending->reject(fn ($service) => $service->suitsVehicleType($vehicleType));
        $byGear = $offending->reject(fn ($service) => $service->suitsTransmission($transmission))
                            ->reject(fn ($service) => $byType->contains('id', $service->id));

        $parts = [];

        if ($byType->isNotEmpty()) {
            $kind = $vehicleType === 'motorcycle' ? 'motorcycles' : 'cars';

            $parts[] = $byType->pluck('name')->join(', ', ' and ')
                .($byType->count() === 1 ? ' is' : ' are')
                .' not offered for '.$kind.'.';
        }

        if ($byGear->isNotEmpty()) {
            $gear = $transmission === 'manual' ? 'manual' : 'automatic';

            $parts[] = $byGear->pluck('name')->join(', ', ' and ')
                .($byGear->count() === 1 ? ' is' : ' are')
                .' not offered for '.$gear.' vehicles.';
        }

        // A service can land here without failing either check only if the
        // caller passed a collection it did not get from servicesNotFor().
        // Keep the old wording rather than returning an empty sentence.
        if ($parts === []) {
            $parts[] = $offending->pluck('name')->join(', ', ' and ')
                .($offending->count() === 1 ? ' is' : ' are')
                .' not offered for this vehicle.';
        }

        return implode(' ', $parts)
            .' Please remove '.($offending->count() === 1 ? 'it' : 'them').' and try again.';
    }
}
