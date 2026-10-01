<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the customer-facing side of the site while the shop works on it.
 * Toggled from Settings -> System Preferences -> Maintenance Mode.
 *
 * Admin and staff are deliberately exempt so the back office stays usable,
 * and so whoever switched it on isn't locked out.
 */
class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::get('maintenance')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && in_array($user->role, ['admin', 'staff'], true)) {
            return $next($request);
        }

        $message = 'The booking portal is temporarily unavailable while we carry out maintenance. Please check back shortly.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 503);
        }

        return response()->view('maintenance', ['message' => $message], 503);
    }
}
