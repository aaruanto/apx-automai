<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * The headers that keep a page out of the browser's caches.
 *
 * Two middlewares need them for two different reasons — a page rendered for a
 * signed-in user, and a form someone has typed a password into — so the exact
 * header set lives here rather than being written twice and drifting.
 *
 * no-store is the one that matters and the one that is easy to get wrong.
 * no-cache only means "revalidate before reusing", and a page served with it
 * is still eligible for the back/forward cache: pressing Back restores the
 * live DOM, typed values and all, without asking the server anything. Only
 * no-store takes a page out of bfcache. The rest of the set is for older
 * browsers and proxies that predate it.
 */
class NoStore
{
    public static function apply(Response $response): void
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
    }
}
