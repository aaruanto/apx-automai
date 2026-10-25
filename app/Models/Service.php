<?php

namespace App\Models;

use App\Models\Concerns\AppliesToVehicleType;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    // vehicle_type: null applies to every vehicle, otherwise 'car' or
    // 'motorcycle'. The same convention and the same implementation as
    // Bundle and MaintenanceRule, so the three cannot drift apart.
    use AppliesToVehicleType;

    protected $fillable = ['name', 'description', 'price', 'duration', 'category', 'vehicle_type', 'is_active'];
}
