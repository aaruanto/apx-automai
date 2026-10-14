<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $activityStats = [
            'bookings_created'  => Booking::count(),
            'customers_added'   => User::where('role', 'customer')->count(),
            'bookings_updated'  => 0,
            'services_managed'  => Service::count(),
        ];


        return view('admin.profile', compact('user', 'activityStats'));
    }

    public function update(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
        ]);

        Auth::user()->update($request->only('name', 'email', 'phone'));

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Change the signed-in admin's password.
     *
     * Previously min:8|confirmed only, with no strength requirement and no
     * check that the "new" password was actually different.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            // current_password compares against the signed-in user's hash and
            // never puts the value anywhere it could be logged.
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.current_password' => 'Current password is incorrect.',
        ]);

        if (Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors([
                'password' => 'Your new password must be different from your current one.',
            ]);
        }

        $user = Auth::user();
        $user->update(['password' => $request->password]);

        // Any other browser still holding this account is signed out. Needs
        // AuthenticateSession in the web stack, which bootstrap/app.php appends.
        Auth::logoutOtherDevices($request->password);

        return back()->with('success', 'Password updated. Other devices have been signed out.');
    }
}