<?php

namespace App\Support;

/**
 * The demo logins printed on the sign-in page, and provisioned by
 * apx:demo-accounts.
 *
 * TEMPORARY. This exists so a capstone panel can sign in without being handed
 * credentials first, and it publishes a working admin password to anyone who
 * opens /login. See config/apx.php for how to switch it off.
 *
 * One class rather than two readings of config(), because the command and the
 * page must agree: a credential shown on screen that was never provisioned is
 * worse than no panel at all, and a silently different normalisation of the
 * email address is exactly how that happens.
 */
class DemoAccounts
{
    /**
     * Is the demo panel switched on at all?
     */
    public static function enabled(): bool
    {
        return (bool) config('apx.demo.enabled', false);
    }

    /**
     * The accounts that are both enabled and fully configured.
     *
     * Each entry is ['label', 'role', 'name', 'email', 'password'].
     *
     * An account missing an email or a password is dropped rather than
     * half-created: the entrypoint must not abort a deploy over an unset demo
     * variable, and the page must not advertise a login that does not exist.
     *
     * Roles are narrowed to the two this app has. Anything else in the
     * environment becomes a customer — a typo in a deploy variable must not be
     * able to mint an admin.
     *
     * @return list<array{label:string,role:string,name:string,email:string,password:string}>
     */
    public static function all(): array
    {
        if (! self::enabled()) {
            return [];
        }

        $accounts = [];

        foreach ((array) config('apx.demo.accounts', []) as $account) {
            $email    = strtolower(trim((string) ($account['email'] ?? '')));
            $password = (string) ($account['password'] ?? '');

            if ($email === '' || $password === '') {
                continue;
            }

            $role = ($account['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';

            $accounts[] = [
                'label'    => (string) ($account['label'] ?? ucfirst($role)),
                'role'     => $role,
                'name'     => (string) ($account['name'] ?? 'Demo User'),
                'email'    => $email,
                'password' => $password,
            ];
        }

        return $accounts;
    }
}
