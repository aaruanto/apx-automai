<?php

namespace App\Support;

/**
 * The email validation rule used wherever an address is accepted.
 *
 * "dns" makes Laravel resolve the domain's MX/A records, which rejects
 * typo'd and throwaway domains — the point of the stricter rule. It also
 * means a live DNS lookup sits in the request path, so it is deliberately
 * skipped while testing: the suite would otherwise need working DNS to pass,
 * and reserved domains like example.com have no MX record and would fail.
 *
 * Production and local development both get the full check.
 */
class EmailRule
{
    public static function strict(): string
    {
        return app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';
    }
}
