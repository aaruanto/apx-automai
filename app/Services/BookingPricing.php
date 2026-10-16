<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Bundle;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * What a booking costs, decided here and nowhere else.
 *
 * Every price in this application is computed on the server from database
 * rows. Nothing a browser posts — no price, no discount, no total — is read.
 * The client sends what the customer *chose*; what that costs is not its
 * business.
 *
 * The discount is spread across the per-service snapshots rather than kept
 * as a separate figure to subtract later. That is the same discipline the
 * bundle allocation uses, and for the same reason: revenue, per-service
 * reports and the customer's total spent all read booking_service.price, so
 * money that does not pass through it is money some screen will miss. The
 * columns on the booking are the record of how the total was reached, not a
 * second source of truth for it.
 */
class BookingPricing
{
    /**
     * Price a booking for this customer.
     *
     * @param  Collection<int, Service>  $services
     * @return array{
     *     list: float, discount_percent: int, discount_amount: float,
     *     total: float, allocation: array<int, float>, bundle_id: ?int,
     *     bundle_price: ?float
     * }
     */
    public function quote(
        Collection $services,
        ?Bundle $bundle = null,
        ?User $user = null,
        int $requestedPoints = 0,
    ): array
    {
        // What the work costs before anything is taken off: a bundle's own
        // price if there is one, otherwise the sum of the list prices.
        $listPrice = $bundle
            ? (float) $bundle->price
            : (float) $services->sum('price');

        $percent  = $this->discountPercentFor($user);
        $discount = round($listPrice * $percent / 100, 2);

        // Bundle price, then the account discount, then points — the order
        // matters, because each step takes its cut of what is left.
        $afterDiscount = round($listPrice - $discount, 2);

        $loyalty      = app(LoyaltyService::class);
        $pointsToSpend = 0;
        $pointsValue   = 0.0;

        if ($requestedPoints > 0 && $user) {
            // Capped by their balance, by the minimum, and by the share of a
            // booking points are allowed to cover. The request is a ceiling,
            // never an instruction.
            $allowed       = $loyalty->redeemableAgainst($user, $afterDiscount);
            $pointsToSpend = max(0, min($requestedPoints, $allowed));
            $pointsValue   = $loyalty->pesoValueOf($pointsToSpend);
        }

        $total = round($afterDiscount - $pointsValue, 2);

        return [
            'list'             => $listPrice,
            'discount_percent' => $percent,
            'discount_amount'  => $discount,
            'points_spent'     => $pointsToSpend,
            'points_discount'  => $pointsValue,
            'total'            => $total,
            'allocation'       => $this->allocate($services, $bundle, $total),
            'bundle_id'        => $bundle?->id,
            'bundle_price'     => $bundle ? (float) $bundle->price : null,
        ];
    }

    /**
     * The account discount this user qualifies for, as a whole percentage.
     *
     * Guests get nothing, which is the point of the offer. A guest record the
     * shop created on someone's behalf is not a real account either, so it
     * does not qualify — otherwise booking as a guest would earn the discount
     * meant for registering.
     */
    public function discountPercentFor(?User $user): int
    {
        if (! Setting::get('account_discount_enabled')) {
            return 0;
        }

        if (! $user || $user->is_guest) {
            return 0;
        }

        $percent = (int) Setting::get('account_discount_percent');

        // Clamped rather than trusted: a bad value in the settings table must
        // not be able to make work free or negative.
        return max(0, min(100, $percent));
    }

    /**
     * Spread a total across services proportionally to their list prices, so
     * the parts sum to exactly the total.
     *
     * The rounding remainder goes to the last service, which is why the total
     * always lands on the figure the customer was quoted rather than a
     * centavo either side of it.
     *
     * @param  Collection<int, Service>  $services
     * @return array<int, float>
     */
    private function allocate(Collection $services, ?Bundle $bundle, float $total): array
    {
        $services = $services->values();

        if ($services->isEmpty()) {
            return [];
        }

        $weights = $services->map(fn (Service $s) => (float) $s->price);
        $sum     = (float) $weights->sum();

        // Nothing to weight by: split evenly.
        if ($sum <= 0) {
            $each = round($total / $services->count(), 2);

            return $services->mapWithKeys(fn (Service $s, int $i) => [
                $s->id => $i === $services->count() - 1
                    ? round($total - $each * ($services->count() - 1), 2)
                    : $each,
            ])->all();
        }

        $allocated = [];
        $running   = 0.0;
        $last      = $services->count() - 1;

        foreach ($services as $i => $service) {
            if ($i === $last) {
                $allocated[$service->id] = round($total - $running, 2);
                break;
            }

            $share = round($total * ((float) $service->price / $sum), 2);
            $allocated[$service->id] = $share;
            $running += $share;
        }

        return $allocated;
    }

    /**
     * Write a quote onto a booking: the snapshots, and the record of how the
     * total was reached.
     */
    public function apply(Booking $booking, array $quote, Collection $services): void
    {
        $durations = $services->pluck('duration', 'id');

        $pivot = collect($quote['allocation'])
            ->mapWithKeys(fn (float $price, int $serviceId) => [
                $serviceId => [
                    'price'    => $price,
                    'duration' => (int) ($durations[$serviceId] ?? 0),
                ],
            ])
            ->all();

        $booking->services()->sync($pivot);

        $booking->update([
            'bundle_id'        => $quote['bundle_id'],
            'bundle_price'     => $quote['bundle_price'],
            'list_price'       => $quote['list'],
            'discount_percent' => $quote['discount_percent'],
            'discount_amount'  => $quote['discount_amount'],
        ]);

        // Spending happens through LoyaltyService so it goes through the lock
        // and the ledger; the quote only decided how many were allowed.
        if (($quote['points_spent'] ?? 0) > 0 && $booking->user) {
            app(LoyaltyService::class)->redeemFor($booking, $booking->user, $quote['points_spent']);
        }
    }
}
