<?php

namespace App\Console\Commands;

use App\Models\LoyaltyLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Prove the ledger agrees with itself.
 *
 * The balance is derived from the ledger rather than cached, so the usual
 * drift between a counter and its history cannot happen here. What this
 * checks is the weaker promise each row makes: that its balance_after equals
 * the running sum up to that point, that no customer's balance is negative,
 * and that the lot remainders still account for the balance.
 *
 * Those can only disagree if something wrote to the table without going
 * through LoyaltyService — which is exactly the case worth catching, because
 * nothing else would notice.
 */
class VerifyLoyaltyLedger extends Command
{
    protected $signature   = 'loyalty:verify {--user= : Check one customer only}';
    protected $description = 'Check that every loyalty balance agrees with its ledger';

    public function handle(): int
    {
        $userIds = LoyaltyLedger::query()
            ->when($this->option('user'), fn ($q, $id) => $q->where('user_id', $id))
            ->distinct()->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->components->info('No loyalty activity to check.');

            return self::SUCCESS;
        }

        $problems = [];

        foreach ($userIds as $userId) {
            $rows    = LoyaltyLedger::where('user_id', $userId)->orderBy('id')->get();
            $running = 0;

            foreach ($rows as $row) {
                $running += $row->points;

                if ($row->balance_after !== $running) {
                    $problems[] = sprintf(
                        'user %d, ledger row %d: balance_after is %d but the running total is %d',
                        $userId, $row->id, $row->balance_after, $running
                    );
                }
            }

            if ($running < 0) {
                $problems[] = sprintf('user %d: balance is negative (%d)', $userId, $running);
            }

            // Every point still in the balance should be sitting in an
            // unspent lot, or there is nothing left to spend it from.
            $lots = (int) LoyaltyLedger::where('user_id', $userId)->sum('remaining');

            if ($lots !== $running) {
                $problems[] = sprintf(
                    'user %d: balance is %d but unspent lots hold %d',
                    $userId, $running, $lots
                );
            }
        }

        if ($problems === []) {
            $this->components->info(
                'Checked '.$userIds->count().' customer'.($userIds->count() === 1 ? '' : 's').' — every balance agrees with its ledger.'
            );

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->components->error($problem);
            Log::error('loyalty:verify — '.$problem);
        }

        $this->newLine();
        $this->components->error(count($problems).' problem'.(count($problems) === 1 ? '' : 's').' found.');

        // A non-zero exit so this can be wired into a check that alerts.
        return self::FAILURE;
    }
}
