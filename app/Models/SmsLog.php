<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        // vehicle_id carries the maintenance half: those sends have no booking,
        // and without it SendMaintenanceReminders' own vehicle_id was silently
        // discarded, leaving the row attached to nothing.
        'booking_id', 'vehicle_id', 'recipient', 'content', 'segments',
        'status', 'reference_id', 'fail_reason', 'trigger',
    ];

    protected function casts(): array
    {
        return ['segments' => 'integer'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The vehicle this text was about, wherever that is recorded.
     *
     * An appointment reminder names a booking and reaches the vehicle through
     * it; a maintenance reminder names the vehicle directly. Callers should
     * not have to know which kind they are holding.
     */
    public function subjectVehicle(): ?Vehicle
    {
        return $this->vehicle ?? $this->booking?->vehicle;
    }

    /**
     * Statuses that mean the gateway accepted the message. Semaphore reports
     * Queued, Pending, Sent, Failed and Refunded; we store them lowercased.
     */
    public const ACCEPTED = ['queued', 'pending', 'sent', 'delivered'];

    /**
     * Written by the log driver: the message was composed, measured and
     * recorded, and deliberately handed to nobody.
     *
     * This is a third outcome, not a shade of the other two. It is not a
     * success — nothing was delivered and claiming otherwise is the bug the
     * log driver exists to prevent — but it is not a failure either, and
     * counting it as one would show a wall of red on a machine that is simply
     * not configured to send. Anything that reports on this table has to
     * handle all three.
     */
    public const SIMULATED = 'simulated';

    public function succeeded(): bool
    {
        return in_array($this->status, self::ACCEPTED, true);
    }

    public function simulated(): bool
    {
        return $this->status === self::SIMULATED;
    }

    public function failed(): bool
    {
        return ! $this->succeeded() && ! $this->simulated();
    }

    /**
     * Genuine failures only: a simulated send never reached a gateway.
     *
     * Named in the plural, not `failed`/`simulated`, because a scope and an
     * instance method cannot share a name: PHP resolves SmsLog::failed() to
     * the predicate above and fails with "cannot be called statically"
     * instead of reaching the query builder. The predicates are the API most
     * callers want, so the scopes are the ones that moved.
     */
    public function scopeFailures($query)
    {
        return $query->whereNotIn('status', self::ACCEPTED)
            ->where('status', '!=', self::SIMULATED);
    }

    public function scopeSimulations($query)
    {
        return $query->where('status', self::SIMULATED);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('created_at');
    }
}
