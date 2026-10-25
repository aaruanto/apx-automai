<?php

namespace App\Models\Concerns;

use App\Models\Vehicle;

/**
 * "This is for cars only / motorcycles only / anything."
 *
 * Three tables answer that question — bundles, maintenance_rules and now
 * services — against the same vehicles.vehicle_type, with the same convention:
 * NULL means it applies to every vehicle.
 *
 * Bundle and MaintenanceRule had grown separate copies of the check, and they
 * had already drifted: Bundle::suitsVehicleType() compared against null only,
 * while MaintenanceRule::appliesTo() also treated an empty string as "all".
 * Neither table holds an empty string today, so folding them together changes
 * nothing in practice — but a third copy for services would have been the one
 * that eventually disagreed with the other two about a real row.
 *
 * Both call shapes are kept because both are useful: suitsVehicleType() when
 * all you have is the string a request sent, appliesTo() when you are holding
 * the Vehicle itself.
 */
trait AppliesToVehicleType
{
    /**
     * Does this apply to a vehicle of the given type?
     *
     * An empty string is read as "all", the same as null. Nothing writes one,
     * but a blank select posting '' must not silently restrict a record to
     * vehicles whose type is the empty string — which is no vehicle at all.
     */
    public function suitsVehicleType(?string $type): bool
    {
        return $this->vehicle_type === null
            || $this->vehicle_type === ''
            || $this->vehicle_type === $type;
    }

    public function appliesTo(Vehicle $vehicle): bool
    {
        return $this->suitsVehicleType($vehicle->vehicle_type);
    }

    /**
     * Narrow a query to the records a given vehicle type may have.
     *
     * A null type means "we do not know what they are driving yet", which
     * must not be read as "show nothing" — it shows everything, and the
     * server-side check still runs once a vehicle is chosen.
     */
    public function scopeForVehicleType($query, ?string $type)
    {
        if ($type === null || $type === '') {
            return $query;
        }

        return $query->where(function ($q) use ($type) {
            $q->whereNull('vehicle_type')
                ->orWhere('vehicle_type', '')
                ->orWhere('vehicle_type', $type);
        });
    }
}
