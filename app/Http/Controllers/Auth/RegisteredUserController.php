<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
 public function store(Request $request): RedirectResponse
{
    // Normalise before validating, so "  User@Mail.COM " cannot slip past the
    // uniqueness check as a different address from "user@mail.com".
    $request->merge([
        'email' => strtolower(trim((string) $request->input('email'))),
    ]);

    $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'last_name'  => ['required', 'string', 'max:255'],
        // unique was missing here, and only here: every other path that
        // creates a user already had it. Without it, the block below was the
        // only thing standing between a stranger and an existing account.
        'email'      => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        'phone'      => ['nullable', 'string', 'max:20'],
        'password'   => ['required', 'confirmed', Rules\Password::defaults()],
    ], [
        'email.unique' => 'This email is already registered. Please log in instead, '
                          .'or use "Forgot password?" if you booked as a guest and have not set one yet.',
    ]);

    // Registration only ever creates. It previously overwrote an existing
    // user's password when their account was unverified and their name began
    // with whatever first_name was typed — and since nothing sets
    // email_verified_at, and str_starts_with matches a single letter, that
    // was an account takeover with an email address and one guess.
    $user = User::create([
        'name'     => $request->first_name . ' ' . $request->last_name,
        'email'    => $request->email,
        'phone'    => $request->phone,
        'password' => Hash::make($request->password),
        'role'     => 'customer',
    ]);

    event(new Registered($user));

    // Deliberately no Auth::login here: registering should not grant a session.
    // The customer signs in with the credentials they just chose, which both
    // confirms they recorded the password and keeps account creation and
    // authentication as two distinct, auditable steps.
    return redirect()->route('login')
        ->with('status', 'Account created successfully. Please log in to continue.');
}
}
