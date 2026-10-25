<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRule extends Model
{
    use \App\Models\Concerns\AppliesToVehicleType;

    protected $fillable = [
        'name', 'description', 'bundle_id', 'service_id',
        'interval_months', 'lead_days',
        'vehicle_type', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'interval_months' => 'integer',
        'lead_days'       => 'integer',
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Whether this rule has anything to say about this vehicle.
     *
     * A null vehicle_type means the rule applies to everything, which is the
     * normal case — an oil change is an oil change.
     */

    /** What the customer should book to satisfy this rule, if anything. */
    public function recommendation(): ?string
    {
        return $this->bundle?->name ?? $this->service?->name;
    }
}
