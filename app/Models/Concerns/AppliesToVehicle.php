<?php

namespace App\Models\Concerns;

use App\Models\Vehicle;

/**
 * "This is for cars only / motorcycles only / manuals only / anything."
 *
 * Three tables answer the first question — bundles, maintenance_rules and
 * services — against vehicles.vehicle_type. One of them, services, also
 * answers the second, against vehicles.transmission. Both use the same
 * convention: NULL means it applies to everything.
 *
 * Renamed from AppliesToVehicleType when transmission arrived. The old name
 * had stopped describing it: the trait answers whether a record suits a
 * vehicle, and the type is now only half of that.
 *
 * Bundle and MaintenanceRule had grown separate copies of the type check, and
 * they had already drifted: Bundle::suitsVehicleType() compared against null
 * only, while MaintenanceRule::appliesTo() also treated an empty string as
 * "all". Neither table holds an empty string today, so folding them together
 * changed nothing in practice — but a third copy for services would have been
 * the one that eventually disagreed with the other two about a real row.
 *
 * Transmission needs no special-casing for the two tables that do not have
 * the column. bundles and maintenance_rules deliberately do not carry it — a
 * bundle's restriction follows from the services inside it — so
 * $this->transmission reads as null on those models, which already means
 * "applies to both". The question answers itself correctly without the trait
 * having to know which table it is on.
 *
 * Both call shapes are kept because both are useful: suitsVehicle() when all
 * you have are the strings a request sent, appliesTo() when you are holding
 * the Vehicle itself.
 */
trait AppliesToVehicle
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

    /**
     * Does this apply to a vehicle with the given transmission?
     *
     * Same convention, and the same reading of an empty string. A null $type
     * — the customer has not said, or the vehicle predates the column — is
     * not a reason to refuse: a service restricted to automatics still shows
     * to someone who has not told us what they drive, and the one place that
     * matters is the server check, which only runs once there is an answer.
     *
     * The asymmetry is deliberate. A record with no restriction suits every
     * vehicle; a vehicle with no recorded transmission suits every record.
     * Both directions of "not stated" mean "do not narrow on this".
     */
    public function suitsTransmission(?string $transmission): bool
    {
        if ($transmission === null || $transmission === '') {
            return true;
        }

        return $this->transmission === null
            || $this->transmission === ''
            || $this->transmission === $transmission;
    }

    /** Both questions at once, which is what every caller actually wants. */
    public function suitsVehicle(?string $type, ?string $transmission = null): bool
    {
        return $this->suitsVehicleType($type) && $this->suitsTransmission($transmission);
    }

    public function appliesTo(Vehicle $vehicle): bool
    {
        return $this->suitsVehicle($vehicle->vehicle_type, $vehicle->transmission);
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

    /**
     * The same, for transmission.
     *
     * Only meaningful on a model whose table has the column — services. Left
     * on the trait rather than moved onto Service so the pair reads together,
     * and so a future table that does grow the column gets both scopes at
     * once instead of one of them.
     */
    public function scopeForTransmission($query, ?string $transmission)
    {
        if ($transmission === null || $transmission === '') {
            return $query;
        }

        return $query->where(function ($q) use ($transmission) {
            $q->whereNull('transmission')
                ->orWhere('transmission', '')
                ->orWhere('transmission', $transmission);
        });
    }
}
