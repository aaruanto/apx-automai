<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'recipient', 'content', 'segments',
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

    /**
     * Statuses that mean the gateway accepted the message. Semaphore reports
     * Queued, Pending, Sent, Failed and Refunded; we store them lowercased.
     * Everything else — our own pre-flight refusals included — is a failure.
     */
    public const ACCEPTED = ['queued', 'pending', 'sent', 'delivered'];

    public function succeeded(): bool
    {
        return in_array($this->status, self::ACCEPTED, true);
    }

    public function scopeFailed($query)
    {
        return $query->whereNotIn('status', self::ACCEPTED);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('created_at');
    }
}
