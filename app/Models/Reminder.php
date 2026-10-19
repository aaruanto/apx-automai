<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    public const TYPE_APPOINTMENT = 'appointment';
    public const TYPE_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'type', 'user_id', 'vehicle_id', 'booking_id', 'maintenance_rule_id',
        'trigger_key', 'channel', 'due_date', 'status', 'error', 'sent_at',
        'dedupe_key',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function rule()
    {
        return $this->belongsTo(MaintenanceRule::class, 'maintenance_rule_id');
    }

    /** The one-per-thing key. Built here so nothing composes it by hand. */
    public static function keyFor(string $type, int|string $subject, string $trigger, string $channel): string
    {
        return "{$type}:{$subject}:{$trigger}:{$channel}";
    }
}
