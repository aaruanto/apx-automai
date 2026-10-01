<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'vehicle_id', 'service_id', 'staff_id',
        'booking_date', 'booking_time', 'duration', 'status', 'notes', 'reference_number',
        'cancel_reason', 'cancelled_by', 'cancelled_at', 'arrived_at',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'arrived_at'   => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'staff_id');
    }
}