<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\LoyaltyLedger;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Loyalty points.
 *
 * Points are money. Everything that moves them does so inside a transaction
 * that locks the customer's ledger rows first, so two requests cannot both
 * read a balance of 300 and both spend it. The balance is always the sum of
 * the ledger — never a counter that could drift from its own history — and
 * can never be driven below zero.
 *
 * Points are earned only when a booking is completed, on the amount actually
 * paid rather than the list price, so a discount is not rewarded twice.
 */
class LoyaltyService
{
    public function enabled(): bool
    {
        return (bool) Setting::get('loyalty_enabled');
    }

    // ── Rates, all configurable ──────────────────────────────────────────

    /** Pesos that must be spent to earn one point. */
    public function pesosPerPoint(): int
    {
        return max(1, (int) Setting::get('loyalty_pesos_per_point'));
    }

    /** What one point is worth when redeemed, in pesos. */
    public function pointValue(): float
    {
        return max(0, (float) Setting::get('loyalty_point_value'));
    }

    public function minimumRedemption(): int
    {
        return max(0, (int) Setting::get('loyalty_min_redeem'));
    }

    /** The most of a booking that points may cover, as a percentage. */
    public function maxRedeemPercent(): int
    {
        return max(0, min(100, (int) Setting::get('loyalty_max_redeem_percent')));
    }

    public function expiryMonths(): int
    {
        return max(0, (int) Setting::get('loyalty_expiry_months'));
    }

    // ── Reading ──────────────────────────────────────────────────────────

    public function balanceFor(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return (int) LoyaltyLedger::where('user_id', $user->id)->sum('points');
    }

    /** Points a booking of this value would earn, if completed. */
    public function pointsForSpend(float $amount): int
    {
        if (! $this->enabled() || $amount <= 0) {
            return 0;
        }

        return (int) floor($amount / $this->pesosPerPoint());
    }

    /**
     * The most this customer may redeem against a booking of this value, in
     * points — capped by their balance, by the maximum share of a booking
     * points may cover, and rounded down to whole points.
     */
    public function redeemableAgainst(?User $user, float $total): int
    {
        if (! $this->enabled() || ! $user || $this->pointValue() <= 0) {
            return 0;
        }

        $balance = $this->balanceFor($user);

        if ($balance < $this->minimumRedemption()) {
            return 0;
        }

        $cashCap   = round($total * $this->maxRedeemPercent() / 100, 2);
        $pointsCap = (int) floor($cashCap / $this->pointValue());

        return max(0, min($balance, $pointsCap));
    }

    public function pesoValueOf(int $points): float
    {
        return round($points * $this->pointValue(), 2);
    }

    // ── Writing ──────────────────────────────────────────────────────────

    /**
     * Award points for a completed booking.
     *
     * Idempotent: a booking that has already earned does not earn again, so
     * re-saving a completed booking cannot mint points. Returns the number
     * awarded, which is zero when loyalty is off or the booking is free.
     */
    public function earnFor(Booking $booking): int
    {
        if (! $this->enabled() || ! $booking->user_id) {
            return 0;
        }

        return DB::transaction(function () use ($booking) {
            $already = LoyaltyLedger::where('booking_id', $booking->id)
                ->where('reason', LoyaltyLedger::EARNED)
                ->lockForUpdate()
                ->exists();

            if ($already) {
                return 0;
            }

            $points = $this->pointsForSpend((float) $booking->total_price);

            if ($points <= 0) {
                return 0;
            }

            $months = $this->expiryMonths();

            $this->write(
                $booking->user_id, $points, LoyaltyLedger::EARNED,
                bookingId: $booking->id,
                remaining: $points,
                expiresAt: $months > 0 ? now()->addMonths($months)->toDateString() : null,
            );

            $booking->update(['points_earned' => $points]);

            return $points;
        });
    }

    /**
     * Spend points against a booking.
     *
     * The whole thing runs inside one transaction with the customer's ledger
     * locked, so a second request cannot read the same balance and spend it
     * too. Returns the peso value applied.
     */
    public function redeemFor(Booking $booking, User $user, int $points): float
    {
        if (! $this->enabled() || $points <= 0) {
            return 0.0;
        }

        return DB::transaction(function () use ($booking, $user, $points) {
            // Lock first, then read: the balance this decision rests on
            // cannot change underneath it.
            $lots = LoyaltyLedger::where('user_id', $user->id)
                ->lockForUpdate()
                ->openLots()
                ->get();

            $available = (int) LoyaltyLedger::where('user_id', $user->id)->sum('points');

            // Re-checked inside the lock rather than trusted from the caller.
            if ($points > $available) {
                return 0.0;
            }

            $toConsume = $points;

            // Oldest lots first, so points expire in the order earned.
            foreach ($lots as $lot) {
                if ($toConsume <= 0) {
                    break;
                }

                $take = min($lot->remaining, $toConsume);
                $lot->update(['remaining' => $lot->remaining - $take]);
                $toConsume -= $take;
            }

            // Lots did not cover it: the ledger disagrees with itself, which
            // is a bug worth refusing rather than papering over.
            if ($toConsume > 0) {
                throw new \RuntimeException(
                    'Loyalty ledger inconsistent for user '.$user->id
                    .': balance says '.$available.' but open lots cover '.($points - $toConsume).'.'
                );
            }

            $value = $this->pesoValueOf($points);

            $this->write($user->id, -$points, LoyaltyLedger::REDEEMED, bookingId: $booking->id);

            $booking->update([
                'points_spent'    => $points,
                'points_discount' => $value,
            ]);

            return $value;
        });
    }

    /**
     * Take back points a booking earned, when it stops being completed work.
     *
     * The returned points go back as a fresh lot rather than by editing the
     * original, so the history still shows both the earning and the reversal.
     * A balance that would go negative is clamped at zero: the customer may
     * already have spent them, and clawing a customer into debt over the
     * shop's own cancellation is not a reasonable outcome.
     */
    public function reverseFor(Booking $booking): int
    {
        return DB::transaction(function () use ($booking) {
            $earned = (int) LoyaltyLedger::where('booking_id', $booking->id)
                ->where('reason', LoyaltyLedger::EARNED)
                ->lockForUpdate()
                ->sum('points');

            $alreadyReversed = (int) abs(LoyaltyLedger::where('booking_id', $booking->id)
                ->where('reason', LoyaltyLedger::REVERSED)
                ->sum('points'));

            $outstanding = $earned - $alreadyReversed;

            if ($outstanding <= 0 || ! $booking->user_id) {
                return 0;
            }

            $balance = (int) LoyaltyLedger::where('user_id', $booking->user_id)->sum('points');
            $take    = min($outstanding, max(0, $balance));

            if ($take <= 0) {
                return 0;
            }

            // Consume the reversal from open lots so it cannot be spent again.
            $remaining = $take;

            foreach (LoyaltyLedger::where('user_id', $booking->user_id)->lockForUpdate()->openLots()->get() as $lot) {
                if ($remaining <= 0) {
                    break;
                }

                $consume = min($lot->remaining, $remaining);
                $lot->update(['remaining' => $lot->remaining - $consume]);
                $remaining -= $consume;
            }

            $this->write($booking->user_id, -$take, LoyaltyLedger::REVERSED, bookingId: $booking->id);
            $booking->update(['points_earned' => 0]);

            return $take;
        });
    }

    /**
     * Give back points that were spent on a booking that is not happening.
     *
     * Returned as a fresh lot with a new expiry, which is the generous
     * reading but the defensible one: the customer did not choose to lose
     * the booking, and points that came back already expired would be worse
     * than not returning them at all.
     */
    public function refundSpentFor(Booking $booking): int
    {
        if (! $booking->user_id) {
            return 0;
        }

        return DB::transaction(function () use ($booking) {
            $spent = (int) abs(LoyaltyLedger::where('booking_id', $booking->id)
                ->where('reason', LoyaltyLedger::REDEEMED)
                ->lockForUpdate()
                ->sum('points'));

            $alreadyBack = (int) LoyaltyLedger::where('booking_id', $booking->id)
                ->where('reason', LoyaltyLedger::ADJUSTED)
                ->where('note', 'Points returned from a cancelled booking')
                ->sum('points');

            $outstanding = $spent - $alreadyBack;

            if ($outstanding <= 0) {
                return 0;
            }

            $months = $this->expiryMonths();

            $this->write(
                $booking->user_id, $outstanding, LoyaltyLedger::ADJUSTED,
                bookingId: $booking->id,
                remaining: $outstanding,
                expiresAt: $months > 0 ? now()->addMonths($months)->toDateString() : null,
                note: 'Points returned from a cancelled booking',
            );

            $booking->update(['points_spent' => 0, 'points_discount' => 0]);

            return $outstanding;
        });
    }

    /**
     * Expire points past their date. Returns how many were removed.
     *
     * Written as its own negative row per lot, so the customer's history
     * says what went and when rather than a balance quietly shrinking.
     */
    public function expire(): int
    {
        $lots = LoyaltyLedger::openLots()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->get();

        $total = 0;

        foreach ($lots as $lot) {
            DB::transaction(function () use ($lot, &$total) {
                $fresh = LoyaltyLedger::whereKey($lot->id)->lockForUpdate()->first();

                if (! $fresh || $fresh->remaining <= 0) {
                    return;
                }

                $this->write(
                    $fresh->user_id, -$fresh->remaining, LoyaltyLedger::EXPIRED,
                    note: 'Earned '.$fresh->created_at->format('M j, Y'),
                );

                $total += $fresh->remaining;
                $fresh->update(['remaining' => 0]);
            });
        }

        return $total;
    }

    /**
     * An admin adjustment, which always carries a reason.
     *
     * Positive adjustments create a lot so they can be spent and expire like
     * any other points; negative ones consume from existing lots.
     */
    public function adjust(User $user, int $points, string $reason): int
    {
        if ($points === 0) {
            return 0;
        }

        return DB::transaction(function () use ($user, $points, $reason) {
            $balance = (int) LoyaltyLedger::where('user_id', $user->id)->lockForUpdate()->sum('points');

            if ($points < 0) {
                // Never below zero, however the figure was typed.
                $points = -min(abs($points), $balance);

                if ($points === 0) {
                    return 0;
                }

                $remaining = abs($points);

                foreach (LoyaltyLedger::where('user_id', $user->id)->openLots()->get() as $lot) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $consume = min($lot->remaining, $remaining);
                    $lot->update(['remaining' => $lot->remaining - $consume]);
                    $remaining -= $consume;
                }
            }

            $months = $this->expiryMonths();

            $this->write(
                $user->id, $points, LoyaltyLedger::ADJUSTED,
                note: $reason,
                remaining: $points > 0 ? $points : null,
                expiresAt: $points > 0 && $months > 0 ? now()->addMonths($months)->toDateString() : null,
            );

            return $points;
        });
    }

    /** One ledger row, with the running balance carried on it. */
    private function write(
        int $userId,
        int $points,
        string $reason,
        ?int $bookingId = null,
        ?int $remaining = null,
        ?string $expiresAt = null,
        ?string $note = null,
    ): LoyaltyLedger {
        $balance = (int) LoyaltyLedger::where('user_id', $userId)->sum('points');

        return LoyaltyLedger::create([
            'user_id'       => $userId,
            'booking_id'    => $bookingId,
            'points'        => $points,
            'remaining'     => $remaining,
            'balance_after' => $balance + $points,
            'reason'        => $reason,
            'note'          => $note,
            'expires_at'    => $expiresAt,
        ]);
    }
}
