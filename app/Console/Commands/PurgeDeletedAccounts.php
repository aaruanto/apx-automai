<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountAnonymizer;
use Illuminate\Console\Command;

class PurgeDeletedAccounts extends Command
{
    protected $signature   = 'accounts:purge-expired';
    protected $description = 'Anonymize soft-deleted accounts whose grace period has expired';

    public function handle(AccountAnonymizer $anonymizer): int
    {
        $due = $anonymizer->pendingPurge();

        if ($due->isEmpty()) {
            $this->info('No accounts due for anonymization.');
            return self::SUCCESS;
        }

        foreach ($due as $record) {
            // withTrashed: these accounts are soft-deleted by definition.
            $user = User::withTrashed()->find($record->user_id);

            if (! $user) {
                $this->warn("User #{$record->user_id} no longer exists — skipping.");
                continue;
            }

            $anonymizer->anonymize($user);
            $this->line("Anonymized account #{$record->user_id} (grace period ended {$record->purge_at->toDateString()}).");
        }

        $this->info($due->count() . ' account(s) anonymized.');

        return self::SUCCESS;
    }
}
