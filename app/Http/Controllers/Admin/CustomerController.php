<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with(['user', 'vehicle'])
            ->withCount('bookings')
            ->latest()
            ->get()
            ->map(function ($c) {
                $c->name       = $c->user->name ?? 'N/A';
                $c->email      = $c->user->email ?? '';
                $c->phone      = $c->phone ?? $c->user->phone ?? '';
                $c->loyalty    = $c->tier ?? 'bronze';
                $c->last_visit = $c->bookings()->latest('booking_date')->value('booking_date');
                $c->vehicle    = $c->vehicle ?? $c->user->vehicles()->latest()->first();
                return $c;
            });

        $totalCustomers = $customers->count();
        $goldCount      = $customers->where('loyalty', 'gold')->count();
        $silverCount    = $customers->where('loyalty', 'silver')->count();
        $bronzeCount    = $customers->where('loyalty', 'bronze')->count();

        return view('admin.customers.index', compact(
            'customers', 'totalCustomers', 'goldCount', 'silverCount', 'bronzeCount'
        ));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'plate' => 'required|string|max:20',
        ]);

        // Create user account
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => 'customer',
            'password' => bcrypt('password'), // temporary default
        ]);

        // Create customer profile
        $customer = Customer::create([
            'user_id'        => $user->id,
            'phone'          => $request->phone,
            'loyalty_points' => 0,
            'tier'           => $request->loyalty ?? 'bronze',
        ]);

        // Create vehicle
        if ($request->plate) {
            $user->vehicles()->create([
                'plate_number' => strtoupper($request->plate),
                'model'        => $request->car_model ?? '',
                'color'        => $request->color ?? '',
            ]);
        }

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer added successfully.');
    }

    public function edit($id)
    {
        $customer          = Customer::with(['user', 'user.vehicles'])->findOrFail($id);
        $customer->name    = $customer->user->name ?? '';
        $customer->email   = $customer->user->email ?? '';
        $customer->vehicle = $customer->user->vehicles()->latest()->first();
        $customer->bookings_count = $customer->bookings()->count();
        $customer->last_visit     = $customer->bookings()->latest('booking_date')->value('booking_date');
        $customer->loyalty = $customer->tier;

        return view('admin.customers.create', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->user_id,
            'phone' => 'required|string|max:20',
        ]);

        $customer->user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $customer->update([
            'phone' => $request->phone,
            'tier'  => $request->loyalty ?? $customer->tier,
        ]);

        if ($request->plate) {
            $vehicle = $customer->user->vehicles()->latest()->first();
            if ($vehicle) {
                $vehicle->update([
                    'plate_number' => strtoupper($request->plate),
                    'model'        => $request->car_model ?? $vehicle->model,
                    'color'        => $request->color ?? $vehicle->color,
                ]);
            } else {
                $customer->user->vehicles()->create([
                    'plate_number' => strtoupper($request->plate),
                    'model'        => $request->car_model ?? '',
                    'color'        => $request->color ?? '',
                ]);
            }
        }

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->user->delete(); // soft deletes user
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted.');
    }

    public function loyalty()
    {
        $customers = Customer::with(['user', 'user.vehicles'])
            ->withCount('bookings')
            ->get()
            ->map(function ($c) {
                $c->name    = $c->user->name ?? 'N/A';
                $c->phone   = $c->phone ?? $c->user->phone ?? '';
                $c->vehicle = $c->user->vehicles()->latest()->first();
                $c->loyalty = $c->tier ?? 'bronze';
                return $c;
            });

        $grouped = [
            'gold'   => $customers->where('loyalty', 'gold')->values(),
            'silver' => $customers->where('loyalty', 'silver')->values(),
            'bronze' => $customers->where('loyalty', 'bronze')->values(),
        ];

        return view('admin.customers.loyalty', compact('grouped'));
    }
}