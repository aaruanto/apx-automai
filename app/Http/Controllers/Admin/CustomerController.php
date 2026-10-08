<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Str;
use App\Support\CsvExport;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        // user.vehicles is eager-loaded rather than counted per row: the list
        // needs both the count and every plate for the search box, and this
        // fetches them all in one query instead of two per customer.
        $customers = Customer::with(['user.vehicles'])
            ->withCount('bookings')
            ->latest()
            ->get()
            ->map(function ($c) {
                $vehicles = $c->user?->vehicles ?? collect();

                $c->name       = $c->user->name ?? 'N/A';
                $c->email      = $c->user->email ?? '';
                $c->phone      = $c->phone ?? $c->user->phone ?? '';
                $c->last_visit = $c->bookings()->latest('booking_date')->value('booking_date');

                // A customer can own several vehicles, so showing one plate
                // here misrepresented them. The count is the honest summary;
                // the plates themselves live on the customer's own page.
                $c->vehicle_count = $vehicles->count();
                $c->plate_search  = $vehicles->pluck('plate_number')->implode(' ');

                return $c;
            });

        return view('admin.customers.index', compact('customers'));
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
            // Not a password anyone knows, by design: a shared default let
            // anyone guessing the email sign in as this customer. They claim
            // the account by registering with the same email, or an admin
            // resets it.
            'password' => Str::password(32),
        ]);

        // Create customer profile
        $customer = Customer::create([
            'user_id' => $user->id,
            'phone'   => $request->phone,
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
        $customer->vehicle  = $customer->user?->vehicles()->latest()->first();
        $customer->vehicles = $customer->user?->vehicles ?? collect();
        $customer->bookings_count = $customer->bookings()->count();
        $customer->last_visit     = $customer->bookings()->latest('booking_date')->value('booking_date');

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
    /** CSV of the customer list, matching the columns shown on screen. */
    public function export()
    {
        $rows = (function () {
            $customers = Customer::with(['user', 'vehicle'])->withCount('bookings')->latest()->get();

            foreach ($customers as $c) {
                $vehicle = $c->vehicle ?? $c->user?->vehicles()->latest()->first();

                yield [
                    $c->user->name ?? 'N/A',
                    $c->user->email ?? '',
                    $c->phone ?? $c->user->phone ?? '',
                    $vehicle?->display_name ?? '',
                    $vehicle?->display_plate ?? 'Not provided',
                    $c->bookings_count,
                    $c->bookings()->latest('booking_date')->value('booking_date') ?? 'Never',
                ];
            }
        })();

        return CsvExport::stream(
            CsvExport::filename('customers'),
            ['Customer', 'Email', 'Phone', 'Vehicle', 'Plate', 'Total Bookings', 'Last Visit'],
            $rows
        );
    }
}