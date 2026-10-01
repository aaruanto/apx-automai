<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AccountAnonymizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, AccountAnonymizer $anonymizer): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // A plain $user->delete() used to hard-delete the row. bookings.user_id
        // and vehicles.user_id are both onDelete('cascade'), so that silently
        // destroyed the customer's booking history — and with it the revenue
        // those bookings represent in the admin reports. Route through the same
        // anonymizer the customer dashboard uses instead.
        if ($anonymizer->inProgressBookings($user)->isNotEmpty()) {
            return Redirect::route('profile.edit')
                ->withErrors(['userDeletion' => 'Your vehicle is currently being serviced and we may need to contact you about it. You can delete your account once the service is completed.'], 'userDeletion');
        }

        $anonymizer->deactivate($user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
