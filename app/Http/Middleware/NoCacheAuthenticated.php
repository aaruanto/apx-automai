<?php

namespace App\Http\Middleware;

use App\Support\NoStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the browser caching pages that were rendered for a signed-in user.
 *
 * Without this, pressing Back after logging out re-renders the dashboard from
 * the bfcache — the session is genuinely gone, but the page still looks live,
 * and on a shared machine the next person sees the previous user's data.
 *
 * Only applied to authenticated responses, so public pages and static assets
 * keep caching normally.
 */
class NoCacheAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        // The same header set NoStoreAuthForms applies to the sign-in pages,
        // defined once in App\Support\NoStore so the two cannot drift.
        NoStore::apply($response);

        return $response;
    }
}
