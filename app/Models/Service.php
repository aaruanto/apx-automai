<?php

namespace App\Models;

use App\Models\Concerns\AppliesToVehicle;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    // vehicle_type: null applies to every vehicle, otherwise 'car' or
    // 'motorcycle'. transmission: null applies to both, otherwise 'manual' or
    // 'automatic'. The same convention and the same implementation as Bundle
    // and MaintenanceRule, so they cannot drift apart — those two carry
    // vehicle_type only, which the trait handles without special-casing.
    use AppliesToVehicle;

    protected $fillable = [
        'name', 'description', 'price', 'duration', 'category',
        'vehicle_type', 'transmission', 'is_active',
    ];
}
