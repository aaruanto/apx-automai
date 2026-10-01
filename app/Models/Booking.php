<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    /** Statuses a booking may be moved to in_progress from. */
    public const STARTABLE_STATUSES = ['pending', 'confirmed'];

    protected $fillable = [
        'user_id', 'vehicle_id', 'service_id', 'staff_id',
        'booking_date', 'booking_time', 'duration', 'status', 'notes', 'reference_number',
        'cancel_reason', 'cancelled_by', 'cancelled_at', 'arrived_at',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'arrived_at'   => 'datetime',
    ];

    /**
     * Whether "Arrived / Start Service" is allowed right now.
     *
     * Lives here so the two admin views that render the button and the
     * controller that performs the transition agree on one rule — the status
     * list was previously hardcoded separately in all three places.
     *
     * Status is the only condition: bookings are made ahead of time, so the
     * appointment date says nothing about whether the customer is standing at
     * the counter now. Staff decide that, not the calendar.
     */
    public function canStart(): bool
    {
        return in_array($this->status, self::STARTABLE_STATUSES, true);
    }

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