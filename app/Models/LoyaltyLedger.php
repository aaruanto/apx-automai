<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyLedger extends Model
{
    protected $table = 'loyalty_ledger';

    public const EARNED   = 'earned';
    public const REDEEMED = 'redeemed';
    public const REVERSED = 'reversed';
    public const EXPIRED  = 'expired';
    public const ADJUSTED = 'adjusted';

    protected $fillable = [
        'user_id', 'booking_id', 'points', 'remaining',
        'balance_after', 'reason', 'note', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'points'        => 'integer',
            'remaining'     => 'integer',
            'balance_after' => 'integer',
            'expires_at'    => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Rows still holding unspent points, oldest first — the FIFO order.
     *
     * Keyed on `remaining` rather than on the reason, because earnings are
     * not the only thing that creates a lot: a positive admin adjustment and
     * a refund of points from a cancelled booking both do. Filtering by
     * reason here made those points count towards the balance while being
     * invisible to the walk that spends it, so a redemption could pass its
     * balance check and then find nothing to consume.
     */
    public function scopeOpenLots($query)
    {
        return $query->where('remaining', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /** Plain-language line for the customer's history panel. */
    public function describe(): string
    {
        $ref = $this->booking?->reference_number;

        return match ($this->reason) {
            self::EARNED   => 'Earned from '.($ref ?? 'a completed booking'),
            self::REDEEMED => 'Redeemed against '.($ref ?? 'a booking'),
            self::REVERSED => 'Returned — '.($ref ?? 'booking').' was cancelled',
            self::EXPIRED  => 'Expired',
            self::ADJUSTED => $this->note ?: 'Adjusted by the shop',
            default        => ucfirst($this->reason),
        };
    }
}
