<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletion;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::withDefaults();

        $staffAccounts = User::whereIn('role', ['admin', 'staff'])->latest()->get();
        $services      = Service::orderBy('name')->get();

        // Base categories plus anything already in use, so the dropdown never
        // drifts out of sync with what services actually exist.
        $categories = collect(['Engine & Oil', 'CVT & Transmission', 'Brakes & Pipes', 'Inspection', 'Free Services'])
            ->merge($services->pluck('category')->filter())
            ->unique()
            ->sort()
            ->values();

        // Passive record only — nothing here needs an admin to act on it.
        $accountDeletions = AccountDeletion::latest()->limit(25)->get();

        return view('admin.settings', compact('settings', 'staffAccounts', 'services', 'categories', 'accountDeletions'));
    }

    // ── General settings ───────────────────────────────────────────────────
    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'business_name'  => 'required|string|max:255',
            'branch_name'    => 'required|string|max:255',
            'address'        => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:40',
            'contact_email'  => 'nullable|email|max:255',
        ]);

        Setting::setMany($data);

        return back()->with('success', 'Business information saved.');
    }
    // ── Booking settings ───────────────────────────────────────────────────
    public function updateBooking(Request $request)
    {
        $data = $request->validate([
            'open_time'          => 'required|date_format:H:i',
            'close_time'         => 'required|date_format:H:i|after:open_time',
            'weekend_open_time'  => 'required|date_format:H:i',
            'weekend_close_time' => 'required|date_format:H:i|after:weekend_open_time',
            'max_bookings'  => 'required|integer|min:1|max:100',
            'slot_duration' => 'required|integer|in:30,45,60,90,120',
        ]);

        // Unchecked boxes are absent from the payload entirely, so read them
        // explicitly rather than expecting them in the validated array.
        $data['allow_walkin']    = $request->boolean('allow_walkin');
        $data['email_reminders'] = $request->boolean('email_reminders');

        Setting::setMany($data);

        return back()->with('success', 'Booking settings saved.');
    }

    // ── System preferences ─────────────────────────────────────────────────
    public function updateSystem(Request $request)
    {
        $data = $request->validate([
            'default_theme' => 'required|in:light,dark',
        ]);

        $data['show_id_prefix'] = $request->boolean('show_id_prefix');
        $data['maintenance']    = $request->boolean('maintenance');

        Setting::setMany($data);

        return back()->with('success', 'System preferences saved.');
    }
    // ── Staff CRUD ─────────────────────────────────────────────────────────
    public function storeStaff(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,staff',
            'password' => 'required|min:8|confirmed',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Staff account created.');
    }

    public function updateStaff(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role'  => 'required|in:admin,staff',
        ]);

        // Constrained to staff/admin so the staff screen can't silently
        // overwrite (or promote) a customer account by id.
        User::whereIn('role', ['admin', 'staff'])->findOrFail($id)
            ->update($request->only('name', 'email', 'role'));

        return back()->with('success', 'Staff account updated.');
    }

    public function destroyStaff($id)
    {
        User::whereIn('role', ['admin', 'staff'])->findOrFail($id)->delete();
        return back()->with('success', 'Staff account removed.');
    }

    // ── Services CRUD ──────────────────────────────────────────────────────
    public function storeService(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'price'    => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        Service::create([
            'name'        => $request->name,
            'category'    => $request->category ?? 'General',
            'price'       => $request->price,
            'duration'    => $request->duration,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Service added.');
    }

    public function updateService(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'price'    => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        Service::findOrFail($id)->update($request->only(
            'name', 'category', 'price', 'duration', 'description'
        ));

        return back()->with('success', 'Service updated.');
    }

    public function destroyService($id)
    {
        Service::findOrFail($id)->delete();
        return back()->with('success', 'Service deleted.');
    }
}