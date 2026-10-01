<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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
     * "Arrived" means the customer is physically present, so a booking dated
     * in the future can't be started. Past-dated ones stay startable so late
     * arrivals and backfilled records still work.
     */
    public function canStart(): bool
    {
        if (! in_array($this->status, self::STARTABLE_STATUSES, true)) {
            return false;
        }

        // booking_date is an uncast date column, so it arrives as a string.
        return ! Carbon::parse($this->booking_date)->startOfDay()->isAfter(Carbon::today());
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