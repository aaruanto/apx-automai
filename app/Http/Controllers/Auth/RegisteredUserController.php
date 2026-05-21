<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Check if email already exists
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            if (!$existingUser->is_guest) {
                // Real account — block registration
                return back()->withErrors(['email' => 'This email is already registered. Please login instead.'])->withInput();
            }

            // Guest account — convert to real account
            $existingUser->update([
                'name'     => $request->first_name . ' ' . $request->last_name,
                'phone'    => $request->phone ?? $existingUser->phone,
                'password' => Hash::make($request->password),
                'is_guest' => false,
                'role'     => 'customer',
            ]);

            event(new Registered($existingUser));
            Auth::login($existingUser);

            return redirect()->route('customer.dashboard');
        }

        // Brand new user
        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'is_guest' => false,
            'role'     => 'customer',
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('customer.dashboard');
    }
}