<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Provisions the temporary demo logins that the sign-in page advertises.
 *
 * TEMPORARY, for the capstone demo. Switched off by DEMO_ACCOUNTS; see
 * config/apx.php for what this costs while it is on.
 *
 * Deliberately different from apx:ensure-admin in one respect. That command
 * warns when it resets a password, because the account it manages is a real
 * one someone may have changed in-app. These two are advertised in public on
 * every page load, so the printed credential has to keep working: the password
 * and the role are forced on every boot. Anything typed into Admin that is not
 * the credential itself — the name, the phone — is left alone.
 *
 * Idempotent, so the entrypoint can call it on every container start. A
 * soft-deleted row is restored rather than collided with: a deleted account
 * still occupies the unique email index, so creating over it would throw.
 */
class EnsureDemoAccounts extends Command
{
    protected $signature = 'apx:demo-accounts
        {--prune : Retire the demo logins by rotating their passwords, instead of provisioning them}';

    protected $description = 'Create or refresh the temporary demo logins shown on the sign-in page';

    public function handle(): int
    {
        $accounts = DemoAccounts::all();

        if ($this->option('prune')) {
            return $this->prune();
        }

        // Not configured is the normal state — a fresh clone, and production
        // once the demo is over. The entrypoint must not fail a boot over it.
        if ($accounts === []) {
            $this->components->info('DEMO_ACCOUNTS is off — no demo logins provisioned.');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->ensure($account);
        }

        $this->components->warn(
            'Demo logins are live and printed on /login. Set DEMO_ACCOUNTS=false after the demo.'
        );

        return self::SUCCESS;
    }

    /**
     * @param  array{label:string,role:string,name:string,email:string,password:string}  $account
     */
    private function ensure(array $account): void
    {
        $user = User::withTrashed()->where('email', $account['email'])->first();

        if (! $user) {
            User::create([
                'name'     => $account['name'],
                'email'    => $account['email'],
                'role'     => $account['role'],
                'password' => $account['password'],
                'is_active' => true,
                'is_guest'  => false,
            ])->forceFill(['email_verified_at' => now()])->save();

            // No password in the output. This runs in the deploy log, which is
            // kept and is readable by anyone with dashboard access.
            $this->components->info("Created demo {$account['role']} {$account['email']}");

            return;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        // The role is forced because the page states it. An account the panel
        // is told is a customer must not turn out to hold the admin side of
        // the system — that is the whole reason the two logins are separate.
        $user->forceFill([
            'role'              => $account['role'],
            'is_active'         => true,
            'is_guest'          => false,
            'password'          => $account['password'],
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        $this->components->info("Ensured demo {$account['role']} {$account['email']}");
    }

    /**
     * Retire the demo logins: make the printed passwords stop working.
     *
     * It rotates each password to a random value rather than deactivating the
     * accounts, because `is_active` is a flag this app writes but does not
     * check at sign-in — setting it would read like the door was shut while
     * the credential on the page still opened it.
     *
     * Nothing is deleted. The customer account may be a real one with real
     * bookings hanging off it; its owner gets back in through "Forgot
     * password?" like anyone else.
     *
     * Run this BEFORE clearing the environment variables — with them gone
     * there is no record of which addresses were advertised.
     */
    private function prune(): int
    {
        $accounts = DemoAccounts::all();

        if ($accounts === []) {
            $this->components->error(
                'Nothing to prune: DEMO_ACCOUNTS and the demo variables must still be set to know which accounts were advertised.'
            );

            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            $user = User::where('email', $account['email'])->first();

            if (! $user) {
                continue;
            }

            $user->forceFill([
                'password'       => Str::random(48),
                'remember_token' => null,
            ])->save();

            $this->components->info("Rotated the password for {$account['email']}");
        }

        $this->components->warn('Now unset DEMO_ACCOUNTS so the panel stops printing them, and reset each password through "Forgot password?".');

        return self::SUCCESS;
    }
}
