<?php

namespace App\Http\Controllers\Admin;

use App\Support\EmailRule;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletion;
use App\Models\AuditLog;
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

        return view('admin.settings', compact('settings', 'services', 'categories', 'accountDeletions'));
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
            // Capped well below 100: a discount that large is almost
            // certainly a typo, and the consequence is giving work away.
            'account_discount_percent' => 'required|integer|min:0|max:50',
        ], [
            'account_discount_percent.max' => 'An account discount above 50% is almost certainly a mistake.',
        ]);

        // Unchecked boxes are absent from the payload entirely, so read them
        // explicitly rather than expecting them in the validated array.
        $data['allow_walkin']             = $request->boolean('allow_walkin');
        $data['email_reminders']          = $request->boolean('email_reminders');
        $data['account_discount_enabled'] = $request->boolean('account_discount_enabled');

        $old = (int) Setting::get('account_discount_percent');
        $wasOn = (bool) Setting::get('account_discount_enabled');

        // Logged like a price change, for the same reason: it sets what every
        // future booking by an account holder is charged.
        if ($old !== (int) $data['account_discount_percent'] || $wasOn !== $data['account_discount_enabled']) {
            AuditLog::record(
                'settings.account_discount_changed', null,
                ['enabled' => $wasOn, 'percent' => $old],
                ['enabled' => $data['account_discount_enabled'], 'percent' => (int) $data['account_discount_percent']],
                label: 'Account discount',
            );
        }

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

        $service  = Service::findOrFail($id);
        $oldPrice = (float) $service->price;

        $service->update($request->only(
            'name', 'category', 'price', 'duration', 'description'
        ));

        // Only the price is logged. Renaming or re-describing a service
        // changes nothing about what anything earned; re-pricing it sets the
        // rate every future booking is quoted at, so it belongs in the trail.
        if ($oldPrice !== (float) $service->price) {
            AuditLog::record(
                'service.price_changed', $service,
                ['price' => $oldPrice], ['price' => (float) $service->price],
                label: $service->name,
            );
        }

        return back()->with('success', 'Service updated.');
    }

    public function destroyService($id)
    {
        $service = Service::findOrFail($id);

        AuditLog::record(
            'service.deleted', $service,
            ['name' => $service->name, 'price' => (float) $service->price], [],
            label: $service->name,
        );

        $service->delete();

        return back()->with('success', 'Service deleted.');
    }
}