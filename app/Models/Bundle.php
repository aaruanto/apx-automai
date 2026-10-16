<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Several services sold together for less than their list prices.
 */
class Bundle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'price', 'is_active',
        'starts_at', 'ends_at', 'vehicle_type',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'date',
            'ends_at'   => 'date',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /** What the same services would cost bought separately. */
    public function listPrice(): float
    {
        return (float) $this->services->sum('price');
    }

    /** How much the bundle saves against buying separately. Never negative. */
    public function savings(): float
    {
        return max(0, round($this->listPrice() - (float) $this->price, 2));
    }

    public function savingsPercent(): int
    {
        $list = $this->listPrice();

        return $list > 0 ? (int) round($this->savings() / $list * 100) : 0;
    }

    /** Total minutes, so the booking can reserve the right length of bay. */
    public function totalDuration(): int
    {
        return (int) $this->services->sum('duration');
    }

    /**
     * Bookable right now: switched on, and inside its run dates if it has any.
     * Null at either end means open-ended.
     */
    public function isBookable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->starts_at && $today->lt($this->starts_at)) {
            return false;
        }

        return ! ($this->ends_at && $today->gt($this->ends_at));
    }

    public function suitsVehicleType(?string $type): bool
    {
        return $this->vehicle_type === null || $this->vehicle_type === $type;
    }

    /** Bundles a customer may book today. */
    public function scopeBookable($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    /**
     * Spread the bundle price across its services, proportionally to their
     * list prices, so the parts sum to exactly the bundle price.
     *
     * The booking keeps its services in the existing pivot and the discount
     * lives in those snapshots, which means every query that already sums
     * booking_service.price — revenue, per-service reports, the customer's
     * total spent — keeps working with no second code path for bundle money.
     * Rounding remainders go to the last service so the total always lands
     * exactly on the bundle price rather than a centavo either side.
     *
     * @return array<int, float> service id => allocated price
     */
    public function allocatePrice(): array
    {
        $services = $this->services;
        $list     = $this->listPrice();
        $target   = (float) $this->price;

        if ($services->isEmpty()) {
            return [];
        }

        // No meaningful list price to weight by: split evenly.
        if ($list <= 0) {
            $each = round($target / $services->count(), 2);
            $out  = [];
            foreach ($services as $i => $s) {
                $out[$s->id] = $i === $services->count() - 1
                    ? round($target - $each * ($services->count() - 1), 2)
                    : $each;
            }

            return $out;
        }

        $allocated = [];
        $running   = 0.0;
        $last      = $services->count() - 1;

        foreach ($services->values() as $i => $service) {
            if ($i === $last) {
                $allocated[$service->id] = round($target - $running, 2);
                break;
            }

            $share = round($target * ((float) $service->price / $list), 2);
            $allocated[$service->id] = $share;
            $running += $share;
        }

        return $allocated;
    }
}
