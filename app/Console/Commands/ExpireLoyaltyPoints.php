<?php

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Retire points past their expiry date.
 *
 * Written as its own negative ledger row per lot rather than by quietly
 * shrinking a balance, so a customer asking "where did my points go" has an
 * answer with a date on it.
 */
class ExpireLoyaltyPoints extends Command
{
    protected $signature   = 'loyalty:expire';
    protected $description = 'Expire loyalty points past their expiry date';

    public function handle(LoyaltyService $loyalty): int
    {
        if (! $loyalty->enabled()) {
            $this->components->info('Loyalty is switched off — nothing to expire.');

            return self::SUCCESS;
        }

        $expired = $loyalty->expire();

        $this->components->info($expired > 0
            ? "Expired {$expired} point".($expired === 1 ? '' : 's').'.'
            : 'No points were due to expire.');

        Log::info("loyalty:expire completed — {$expired} points expired.");

        return self::SUCCESS;
    }
}
