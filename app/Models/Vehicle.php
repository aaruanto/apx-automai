<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'make', 'model', 'plate_number', 'vehicle_type', 'year', 'color', 'is_primary'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Guest bookings previously wrote a "NO-PLATE-{user_id}" placeholder when
     * the plate was left blank. Those rows still exist, so the placeholder is
     * translated here rather than leaking to any screen that shows a plate.
     * Plates cleared by an account deletion (REMOVED-*) are hidden the same way.
     */
    protected function displayPlate(): Attribute
    {
        return Attribute::get(function () {
            $plate = trim((string) $this->plate_number);

            return ($plate === ''
                || str_starts_with($plate, 'NO-PLATE-')
                || str_starts_with($plate, 'REMOVED-'))
                ? 'Not provided'
                : $plate;
        });
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            $type      = $this->vehicle_type === 'motorcycle' ? 'Motorcycle' : 'Car';
            $makeModel = trim(($this->make ?? '') . ' ' . ($this->model ?? ''));

            return $makeModel !== '' ? $type . ' — ' . $makeModel : $type;
        });
    }
}