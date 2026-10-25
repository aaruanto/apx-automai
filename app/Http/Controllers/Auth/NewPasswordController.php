<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $fields = [
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ];

                // This is where a guest becomes a real customer.
                //
                // A guest booking creates a users row with is_guest = true and a
                // random password nobody has seen, then emails a link here so they
                // can set one (GuestBookingController, GuestAccountCreated). Setting
                // the password used to be all this did, which let them sign in but
                // left is_guest true forever — and BookingPricing refuses the account
                // discount to a guest, so they stayed a second-class account having
                // done everything asked of them, and were never told why.
                //
                // Reaching this callback means the token in their inbox was valid,
                // which is proof they own the address. Nothing is migrated: the
                // bookings are already on this row, so their history simply becomes
                // visible to them once they can sign in.
                if ($user->is_guest) {
                    $fields['is_guest'] = false;
                }

                $user->forceFill($fields)->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
