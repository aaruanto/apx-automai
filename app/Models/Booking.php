<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    /** Statuses a booking may be moved to in_progress from. */
    public const STARTABLE_STATUSES = ['pending', 'confirmed'];

    /** Statuses a booking may still be cancelled from. */
    public const CANCELLABLE_STATUSES = ['pending', 'confirmed', 'in_progress'];

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

    public function canCancel(): bool
    {
        return in_array($this->status, self::CANCELLABLE_STATUSES, true);
    }

    /**
     * Cancel, recording why and by whom.
     *
     * The three audit columns have existed since the bookings table was
     * created, but only the no-show job ever filled them — every other caller
     * wrote the status alone, so a cancelled booking carried no explanation of
     * who cancelled it or why. Centralised here so that cannot drift again.
     *
     * $byUserId is null for a cancellation the system performed itself, such
     * as the no-show sweep, which is how those are told apart from a person's.
     */
    public function cancel(?string $reason = null, ?int $byUserId = null): bool
    {
        if (! $this->canCancel()) {
            return false;
        }

        // Only these four fields change. Customer, vehicle, service, staff,
        // notes and the original date and time are all left untouched, so the
        // record stays complete for history and reporting.
        $this->forceFill([
            'status'        => 'cancelled',
            'cancel_reason' => $reason,
            'cancelled_by'  => $byUserId,
            'cancelled_at'  => now(),
        ])->save();

        return true;
    }

    /** Who cancelled it, for display. Null means the system did. */
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Every service on this booking.
     *
     * The pivot carries price and duration as they were when the booking was
     * made, so a later price change does not rewrite history. bookings.service_id
     * is kept alongside this and points at the first service, so any screen
     * that still reads a single service keeps working.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class)
                    ->withPivot('price', 'duration')
                    ->withTimestamps();
    }

    /** Summed price of every attached service, from the snapshots. */
    protected function totalPrice(): Attribute
    {
        return Attribute::get(fn () => (float) $this->services->sum(fn ($s) => $s->pivot->price));
    }

    /** Summed duration in minutes. Falls back to the stored block length. */
    protected function totalDuration(): Attribute
    {
        return Attribute::get(function () {
            $sum = (int) $this->services->sum(fn ($s) => $s->pivot->duration);

            return $sum > 0 ? $sum : (int) ($this->duration ?? 0);
        });
    }

    /**
     * Service names for display, e.g. "Change Oil (Diesel) + CVT Fluid Change".
     * Falls back to the single relation so a booking whose pivot rows are not
     * loaded still renders something.
     */
    protected function serviceList(): Attribute
    {
        return Attribute::get(function () {
            $names = $this->services->pluck('name');

            if ($names->isNotEmpty()) {
                return $names->implode(' + ');
            }

            return $this->service->name ?? 'N/A';
        });
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