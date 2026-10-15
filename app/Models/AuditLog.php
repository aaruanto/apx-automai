<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * The record of a change that moved money.
 *
 * Written through AuditLog::record() rather than by observers, so every entry
 * is a deliberate decision at the point of the change and carries the context
 * an observer could not know — the reason for a deletion, say.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'actor_name', 'action', 'auditable_type', 'auditable_id',
        'subject', 'old_values', 'new_values', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record one change.
     *
     * Never throws: an audit failure must not roll back the change it was
     * describing, which would be a worse outcome than a missing line.
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?string $reason = null,
        ?string $label = null,
    ): ?self {
        try {
            return static::create([
                'user_id'        => Auth::id(),
                // Copied rather than joined, so the log still names who did it
                // after the account is renamed or removed.
                'actor_name'     => Auth::user()?->name,
                'action'         => $action,
                'auditable_type' => $subject ? $subject::class : null,
                'auditable_id'   => $subject?->getKey(),
                'subject'        => $label,
                'old_values'     => $old ?: null,
                'new_values'     => $new ?: null,
                'reason'         => $reason,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Could not write audit log ['.$action.']: '.$e->getMessage());

            return null;
        }
    }

    /** Only the fields that actually differ, so a log entry has no filler. */
    public static function diff(array $before, array $after): array
    {
        $changed = [];

        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) != $value) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    /** Plain-language summary for the history panel. */
    public function describe(): string
    {
        return match ($this->action) {
            'booking.status_changed'   => 'Status changed from '.($this->old_values['status'] ?? '?')
                                          .' to '.($this->new_values['status'] ?? '?'),
            'booking.services_changed' => 'Services changed',
            'booking.rescheduled'      => 'Moved to '.($this->new_values['booking_date'] ?? '?')
                                          .' '.($this->new_values['booking_time'] ?? ''),
            'booking.deleted'          => 'Booking deleted',
            'booking.cancelled'        => 'Booking cancelled',
            'service.price_changed'    => 'Price changed from '.($this->old_values['price'] ?? '?')
                                          .' to '.($this->new_values['price'] ?? '?'),
            'service.deleted'          => 'Service removed from the price list',
            default                    => str_replace(['.', '_'], [' ', ' '], $this->action),
        };
    }
}
