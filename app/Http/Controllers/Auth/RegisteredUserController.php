<?php

namespace App\Http\Controllers\Auth;

use App\Support\EmailRule;

use App\Http\Controllers\Controller;
use App\Mail\GuestAccountCreated;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
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

        // Someone already holds this address. Send them the link that actually
        // helps, then let the unique rule below refuse to create anything.
        //
        // Both branches produce the SAME message on screen. If a guest address
        // said "check your email to finish setting up" while a registered one
        // said "already registered", this form would be an enumerator: type an
        // address, learn whether that person has booked at this shop. The reply
        // must not distinguish the two.
        $this->offerTheRightLinkTo($request->input('email'));

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            // unique was missing here, and only here: every other path that
            // creates a user already had it. Without it, the block below was the
            // only thing standing between a stranger and an existing account.
            'email'      => ['required', 'string', 'lowercase', EmailRule::strict(), 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            // Deliberately says nothing about which kind of account it is, and
            // no longer points at "Forgot password?" — that let a guest sign in
            // but left is_guest true, so they kept being refused the account
            // discount with no explanation. The link sent above does the job
            // properly: NewPasswordController clears is_guest when it is used.
            'email.unique' => 'If an account can be set up for that address, we have just emailed a link to it. '
                              .'Please check your inbox to finish signing up, or sign in if you already have a password.',
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

    /**
     * Email whoever owns this address the link that gets them in.
     *
     * A guest gets the "set up your account" link, the same one the first guest
     * booking sent them — following it sets a password and clears is_guest, so
     * the bookings already on that row become theirs to see. A registered
     * account gets an ordinary password reset. An address nobody holds gets
     * nothing, and registration proceeds normally.
     *
     * Throttled through the password broker's own token repository, so this
     * cannot be used to send someone mail repeatedly.
     */
    private function offerTheRightLinkTo(?string $email): void
    {
        if (! $email) {
            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        if (! $user->is_guest) {
            Password::sendResetLink(['email' => $email]);

            return;
        }

        if (Password::getRepository()->recentlyCreatedToken($user)) {
            return;
        }

        try {
            $resetUrl = url(route('password.reset', [
                'token' => Password::createToken($user),
                'email' => $user->email,
            ], false));

            Mail::to($user->email)->send(new GuestAccountCreated($user, $resetUrl));
        } catch (\Throwable $e) {
            // A mail outage must not turn into a 500 on the registration form,
            // and must not change what the customer is told either — that would
            // reintroduce the enumeration difference this method exists to avoid.
            Log::error('Guest claim link failed: '.$e->getMessage());
        }
    }
}
