<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A proposed move of a booking, not yet made.
 *
 * See the migration for why this is its own row rather than a booking status.
 * The booking it belongs to keeps its original date, time and slot for as
 * long as a request is open.
 */
class RescheduleRequest extends Model
{
    protected $table = 'booking_reschedule_requests';

    public const PENDING   = 'pending';
    public const APPLIED   = 'applied';
    public const WITHDRAWN = 'withdrawn';

    /**
     * How many times one booking may actually be moved.
     *
     * Without a ceiling an admin can proposeapply, propose, apply, and a
     * customer's Tuesday becomes Thursday becomes the following week, each
     * step individually reasonable. Applied moves are counted, not proposals:
     * raising one and withdrawing it costs nothing, because the booking never
     * moved.
     */
    public const MAX_APPLIED_PER_BOOKING = 2;

    protected $fillable = [
        'booking_id',
        'proposed_date', 'proposed_time',
        'original_date', 'original_time',
        'reason', 'status',
        'proposed_by', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_date' => 'date',
            'original_date' => 'date',
            'resolved_at'   => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    /** "2 November 2026 at 10:00 AM", for the email and the screen. */
    public function proposedLabel(): string
    {
        return $this->proposed_date->format('j F Y').' at '
            .\Carbon\Carbon::parse($this->proposed_time)->format('g:i A');
    }

    public function originalLabel(): string
    {
        return $this->original_date->format('j F Y').' at '
            .\Carbon\Carbon::parse($this->original_time)->format('g:i A');
    }
}
