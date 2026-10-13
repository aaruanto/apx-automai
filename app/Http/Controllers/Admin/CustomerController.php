<?php

namespace App\Http\Controllers\Admin;

use App\Support\EmailRule;

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
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', EmailRule::strict(), 'unique:users,email'],
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

    /**
     * A customer's personal details are not the shop's to edit.
     *
     * Name, email, phone and address belong to the customer and are changed by
     * them from their own dashboard. This used to write all three straight onto
     * the User record. It is refused here rather than only hidden in the form,
     * so a crafted request cannot do what the screen no longer offers.
     */
    public function update(Request $request, $id)
    {
        Customer::findOrFail($id);

        return redirect()
            ->route('admin.customers.index')
            ->with('error', 'Customer personal details are read-only. The customer can update them from their own account; booking details are edited from the booking itself.');
    }


    /**
     * Delete a customer, provided they have no bookings.
     *
     * A booking is a service and financial record, so a customer who has one is
     * not deleted — the request is refused and says how many there are. The
     * customer can still close their own account from their dashboard, which
     * anonymises rather than destroys.
     *
     * The button behind this never worked: it opened a modal whose Delete was a
     * bare <button> with no form, action or CSRF token, so nothing was ever
     * submitted and no customer has ever been deleted through it.
     */
    public function destroy($id)
    {
        $customer = Customer::with('user')->findOrFail($id);

        $bookings = $customer->bookings()->count();

        if ($bookings > 0) {
            return redirect()->route('admin.customers.index')->with(
                'error',
                'This customer has '.$bookings.' booking'.($bookings === 1 ? '' : 's')
                .' on record and cannot be deleted. Their service history would be lost.'
            );
        }

        $name = $customer->user->name ?? 'Customer';

        // Soft deletes: the row stays for referential safety, and the account
        // can be recovered if this was a mistake.
        $customer->user?->delete();
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', $name.' has been deleted.');
    }
    /** CSV of the customer list, matching the columns shown on screen. */
    public function export()
    {
        $rows = (function () {
            $customers = Customer::with(['user.vehicles'])->withCount('bookings')->latest()->get();

            foreach ($customers as $c) {
                // No email, phone or plate: the file would otherwise be a
                // contact list, and a count answers "how many vehicles"
                // without identifying any of them.
                yield [
                    $c->user->name ?? 'N/A',
                    $c->user?->vehicles->count() ?? 0,
                    $c->bookings_count,
                    $c->bookings()->latest('booking_date')->value('booking_date') ?? 'Never',
                ];
            }
        })();

        return CsvExport::stream(
            CsvExport::filename('customers'),
            ['Customer', 'Vehicles', 'Total Bookings', 'Last Visit'],
            $rows
        );
    }
}