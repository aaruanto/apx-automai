<?php

namespace App\Http\Middleware;

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

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
