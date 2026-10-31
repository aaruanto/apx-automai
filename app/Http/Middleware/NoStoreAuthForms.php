<?php

namespace App\Http\Middleware;

use App\Support\NoStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the sign-in forms out of the back/forward cache.
 *
 * NoCacheAuthenticated covers pages rendered for a signed-in user, and
 * deliberately early-returns for everyone else so that public pages and assets
 * keep caching normally. That left the login form — the one page in the
 * application someone types a password into — served with nothing but
 * "no-cache, private", which does not prevent a bfcache restore.
 *
 * Measured, in a browser profile with no saved passwords at all: type
 * credentials on /login, follow a link away, press Back, and the page comes
 * back with the typed password still in the field. A JavaScript variable set
 * before leaving survived too, which is how you can tell the page was restored
 * rather than re-fetched. No password manager involved — the application
 * handed the browser a cacheable page and the browser cached it.
 *
 * Applied to the guest auth group in routes/auth.php rather than to the web
 * group, so the landing page and its images — the heaviest thing this app
 * serves, on a free instance — are untouched. confirm-password needs no entry
 * here: it sits in the auth group and is already covered by the other
 * middleware.
 */
class NoStoreAuthForms
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        NoStore::apply($response);

        return $response;
    }
}
