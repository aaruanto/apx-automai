<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        // Load persisted settings — swap with DB/config reads when ready
        $settings = [
            'business_name'  => config('apx.business_name', 'APX Motors Service Center'),
            'branch_name'    => config('apx.branch_name',   'Tandang Sora Branch'),
            'address'        => config('apx.address',       'Tandang Sora Ave., Quezon City'),
            'contact_number' => config('apx.contact_number',''),
            'contact_email'  => config('apx.contact_email', ''),
            'open_time'      => config('apx.open_time',     '08:00'),
            'close_time'     => config('apx.close_time',    '17:00'),
            'max_bookings'   => config('apx.max_bookings',  20),
            'slot_duration'  => config('apx.slot_duration', 60),
            'allow_walkin'   => true,
            'email_reminders'=> false,
            'maintenance'    => false,
            'loyalty_bronze' => 1,
            'loyalty_silver' => 5,
            'loyalty_gold'   => 10,
        ];

        $staffAccounts = User::whereIn('role', ['admin', 'staff'])->latest()->get();
        $services      = Service::orderBy('name')->get();

        return view('admin.settings', compact('settings', 'staffAccounts', 'services'));
    }

    // ── General settings ───────────────────────────────────────────────────
    public function updateGeneral(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'branch_name'   => 'required|string|max:255',
        ]);

        // TODO: persist to a settings table or .env when ready
        return back()->with('success', 'Business information saved.');
    }

    // ── Booking settings ───────────────────────────────────────────────────
    public function updateBooking(Request $request)
    {
        $request->validate([
            'open_time'    => 'required',
            'close_time'   => 'required',
            'max_bookings' => 'required|integer|min:1',
            'slot_duration'=> 'required|integer',
        ]);

        return back()->with('success', 'Booking settings saved.');
    }

    // ── Loyalty settings ───────────────────────────────────────────────────
    public function updateLoyalty(Request $request)
    {
        $request->validate([
            'loyalty_bronze' => 'required|integer|min:0',
            'loyalty_silver' => 'required|integer|min:0',
            'loyalty_gold'   => 'required|integer|min:0',
        ]);

        return back()->with('success', 'Loyalty tiers saved.');
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

        User::findOrFail($id)->update($request->only('name', 'email', 'role'));

        return back()->with('success', 'Staff account updated.');
    }

    public function destroyStaff($id)
    {
        User::findOrFail($id)->delete();
        return back()->with('success', 'Staff account removed.');
    }

    // ── Services CRUD ──────────────────────────────────────────────────────
    public function storeService(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        Service::create([
            'name'             => $request->name,
            'category'         => $request->category ?? 'General',
            'price'            => $request->price,
            'duration_minutes' => $request->duration_minutes,
            'description'      => $request->description,
            'is_active'        => true,
        ]);

        return back()->with('success', 'Service added.');
    }

    public function updateService(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        Service::findOrFail($id)->update($request->only(
            'name', 'category', 'price', 'duration_minutes', 'description'
        ));

        return back()->with('success', 'Service updated.');
    }

    public function destroyService($id)
    {
        Service::findOrFail($id)->delete();
        return back()->with('success', 'Service deleted.');
    }
}