<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Mint a secret for the /cron endpoint.
 *
 * Prints the value and nothing else, so it can be piped. It is deliberately
 * NOT written to .env: the value that matters lives in Render's environment,
 * and a command that edits .env encourages committing the real one.
 */
class MakeCronToken extends Command
{
    protected $signature = 'apx:cron-token';

    protected $description = 'Generate a random APX_CRON_TOKEN value';

    public function handle(): int
    {
        $token = Str::random(48);

        $this->line($token);
        $this->newLine();
        $this->comment('Set this as APX_CRON_TOKEN in your environment (Render → Environment).');
        $this->comment('Then point an external cron at: https://<your-app>/cron/schedule?token=<the token>');
        $this->comment('Do not commit it, and do not paste it into the repo.');

        return self::SUCCESS;
    }
}
