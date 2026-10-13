<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Render's load balancer the app sees plain HTTP, so without this
        // Laravel builds http:// asset and route URLs on an https:// site.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            // Signed-in pages must not sit in the browser cache: without this,
            // Back after logout re-renders the dashboard from bfcache.
            \App\Http\Middleware\NoCacheAuthenticated::class,

            // Required for Auth::logoutOtherDevices() to actually invalidate
            // the user's other sessions. Without it the call succeeds and does
            // nothing, because nothing is checking the password hash per request.
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);

        $middleware->alias([
       'role' => \App\Http\Middleware\RoleMiddleware::class,
       'maintenance' => \App\Http\Middleware\MaintenanceMode::class,
       'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
