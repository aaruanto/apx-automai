<?php

namespace App\Http\Controllers\Auth;

use App\Models\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
 public function store(LoginRequest $request): RedirectResponse
{
  
    $request->authenticate();

    $request->session()->regenerate();

    $user = Auth::user();

    // The login page itself stays reachable during maintenance because the
    // role is not known until the credentials are checked — closing it would
    // lock the admin out of the switch that turns maintenance off. So the
    // check happens here instead: an admin continues, a customer is signed
    // straight back out and told why.
    if (Setting::get('maintenance') && $user->role !== 'admin') {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors([
            'email' => 'The booking portal is temporarily unavailable while we carry out maintenance. Please try again shortly.',
        ]);
    }

    // One source of truth for where a role lands, shared with email
    // verification and password confirmation.
    return redirect()->route($user->dashboardRoute());
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
