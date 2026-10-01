<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\Validator;

/**
 * Creates (or promotes) a privileged account from the environment.
 *
 * Registration hardcodes role=customer on purpose — there is deliberately no
 * public path to an admin account. That leaves no way to get the first admin
 * into a fresh database, which matters most on hosts with no shell access
 * (Render's free tier), so the deploy entrypoint calls this instead.
 *
 * Idempotent: safe to run on every container start. An existing account is
 * promoted and re-activated rather than duplicated, and a soft-deleted one is
 * restored instead of colliding with the unique email index.
 */
class EnsureAdminUser extends Command
{
    protected $signature = 'apx:ensure-admin
        {--email=    : Defaults to the ADMIN_EMAIL env var}
        {--password= : Defaults to the ADMIN_PASSWORD env var}
        {--name=     : Defaults to the ADMIN_NAME env var, else "Administrator"}
        {--role=admin : admin or staff}';

    protected $description = 'Create or promote the admin account defined by ADMIN_EMAIL / ADMIN_PASSWORD';

    public function handle(): int
    {
        $email    = $this->option('email')    ?: env('ADMIN_EMAIL');
        $password = $this->option('password') ?: env('ADMIN_PASSWORD');
        $name     = $this->option('name')     ?: env('ADMIN_NAME', 'Administrator');
        $role     = $this->option('role')     ?: 'admin';

        // Not configured is a normal state, not a failure — the entrypoint must
        // not abort the boot just because these vars are absent.
        if (! $email || ! $password) {
            $this->components->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set — skipping admin provisioning.');

            return self::SUCCESS;
        }

        if (! in_array($role, ['admin', 'staff'], true)) {
            $this->components->error("Invalid role [{$role}]. Use admin or staff.");

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email'    => ['required', 'email', 'max:255'],
                'password' => ['required', PasswordRule::min(8)],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $email = strtolower(trim($email));

        // withTrashed: a soft-deleted row still occupies the unique email index,
        // so creating over it would throw instead of recovering the account.
        $user = User::withTrashed()->where('email', $email)->first();

        if (! $user) {
            User::create([
                'name'     => $name,
                'email'    => $email,
                'role'     => $role,
                'password' => $password,
            ])->forceFill(['email_verified_at' => now()])->save();

            $this->components->info("Created {$role} account {$email}");

            return self::SUCCESS;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $changes = ['role' => $role, 'is_active' => true];

        if (! $user->email_verified_at) {
            $changes['email_verified_at'] = now();
        }

        // Keep the env var authoritative so a forgotten password is recoverable
        // without shell access. Remove ADMIN_PASSWORD after first boot to stop
        // this resetting a password that was later changed in-app.
        if (! Hash::check($password, $user->password)) {
            $changes['password'] = $password;
            $this->components->warn('Password reset to match ADMIN_PASSWORD.');
        }

        $user->forceFill($changes)->save();

        $this->components->info("Ensured {$role} account {$email}");

        return self::SUCCESS;
    }
}
