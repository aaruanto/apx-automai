<?php

namespace App\Http\Controllers\Admin;

use App\Support\EmailRule;
use App\Support\PlateNumber;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Services\LoyaltyService;
use Illuminate\Support\Str;
use App\Support\CsvExport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $loyalty        = app(LoyaltyService::class);
        $loyaltyEnabled = $loyalty->enabled();

        if ($loyaltyEnabled) {
            // One grouped query for every balance rather than one per row.
            $balances = \App\Models\LoyaltyLedger::selectRaw('user_id, SUM(points) as total')
                ->groupBy('user_id')->pluck('total', 'user_id');

            $customers->each(fn ($c) => $c->points_balance = (int) ($balances[$c->user_id] ?? 0));
        }

        return view('admin.customers.index', compact('customers', 'loyaltyEnabled'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        // Normalised before validating, not after: the rules below reject a
        // leading, trailing or doubled space, so cleaning has to happen first
        // or a customer typing "abc 1234 " is refused rather than tidied.
        $request->merge(['plate' => PlateNumber::normalise($request->input('plate'))]);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', EmailRule::strict(), 'unique:users,email'],
            'phone' => 'required|string|max:20',
            'plate' => PlateNumber::rules(),
            // Defaults to Walk-in below when absent, because that is what a
            // customer added on this screen is. Accepted so the shop can say
            // otherwise — a customer who rang up and has since registered,
            // say — rather than having to correct it immediately afterwards.
            'customer_type' => ['nullable', Rule::in(\App\Enums\CustomerType::values())],
            // Separate columns, separately posted. This form had one
            // "Car Model" field and wrote it into both.
            'car_make'  => 'nullable|string|max:100',
            'car_model' => 'nullable|string|max:100',
            'car_year'  => 'nullable|integer|min:1990|max:'.(date('Y') + 1),
            'car_type'  => 'nullable|in:car,motorcycle',
        ]);

        // plate_number is globally unique and a soft delete does not release
        // it, so a plate already in the system cannot be given to the account
        // about to be created. This path did not check at all: it inserted and
        // let the constraint throw, which returned a 500 — after the user and
        // customer rows had already been written, leaving a customer with no
        // vehicle and no explanation. Checked before anything is created.
        $held = Vehicle::holdingPlate($request->plate);

        if ($held) {
            $owner = $held->user?->name;

            return back()->withInput()->withErrors(['plate' => $owner
                ? "That plate is already registered to {$owner}. Add the vehicle to that customer instead, or correct the plate."
                : 'That plate is already registered to another account. Check the customer records, or correct the plate.']);
        }

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
        //
        // Walk-in: this is the shop typing a customer in at the counter, and
        // it is one of only two paths that can say so — nothing on the users
        // row records who created it, which is why the type has to be written
        // here rather than inferred later.
        $customer = Customer::create([
            'user_id'       => $user->id,
            'customer_type' => \App\Enums\CustomerType::tryFrom((string) $request->customer_type)
                               ?? \App\Enums\CustomerType::WalkIn,
            'phone'         => $request->phone,
        ]);

        // Create vehicle
        if ($request->plate) {
            $user->vehicles()->create([
                'plate_number' => PlateNumber::normalise($request->plate),
                // vehicles.make is NOT NULL and this payload omitted it, so
                // adding a customer with a plate threw a constraint violation
                // and returned a 500. It then over-corrected and wrote the
                // model into both columns, which read back as "Toyota Vios
                // Toyota Vios". The form asks for each separately now; make
                // keeps the fallback because the column is NOT NULL.
                'make'         => $request->car_make ?: 'Unknown',
                'model'        => $request->car_model ?: '',
                'year'         => $request->car_year ?: null,
                'vehicle_type' => $request->car_type ?: 'car',
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
     * A customer's personal details are not the shop's to edit. Their type is.
     *
     * Name, email, phone and address belong to the customer and are changed by
     * them from their own dashboard. This used to write all three straight onto
     * the User record. They are still refused here rather than only hidden in
     * the form, so a crafted request cannot do what the screen does not offer.
     *
     * customer_type is the exception, and it is a different kind of thing: it
     * is how the shop came to have the record, which is the shop's own
     * classification and not a detail about the person. It is also the one
     * field the migration's backfill could get wrong — a customer added at
     * the counter WITH a real email address is indistinguishable from one who
     * registered, so those read Verified — and the shop needs somewhere to
     * put that right.
     *
     * Audited, because it changes what the shop believes about a record and
     * there is no other trace of who changed it.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::with('user')->findOrFail($id);

        // nullable, not required. A post that does not mention the type is
        // not an invalid request — it is somebody trying to change a field
        // this screen does not accept, and the right answer to that is the
        // read-only message below rather than a validation error about a
        // field they never touched.
        $data = $request->validate([
            'customer_type' => ['nullable', Rule::in(\App\Enums\CustomerType::values())],
        ]);

        $was = $customer->customer_type;
        $now = \App\Enums\CustomerType::tryFrom((string) ($data['customer_type'] ?? '')) ?? $was;

        // Everything else on the form is read-only, so a submission that
        // changes nothing should say so rather than claiming a save.
        if ($was === $now) {
            return redirect()->route('admin.customers.index')->with(
                'error',
                'Nothing was changed. A customer\'s name, email, phone and address are theirs to edit '
                .'from their own account; booking details are edited from the booking itself. '
                .'Only the customer type can be changed here.'
            );
        }

        $customer->update(['customer_type' => $now]);

        AuditLog::record(
            'customer.type_changed', $customer,
            ['customer_type' => $was->value], ['customer_type' => $now->value],
            label: $customer->user->name ?? 'Customer',
        );

        return redirect()->route('admin.customers.index')->with(
            'success',
            ($customer->user->name ?? 'That customer').' is now recorded as '.$now->label().'. '
            .'Their name, email, phone and address remain theirs to edit from their own account.'
        );
    }

    /**
     * Adjust a customer's loyalty points by hand.
     *
     * Points are money, so an adjustment always carries a reason and always
     * lands in the audit trail — the Phase 2 decision was that admin may
     * change points provided every change is attributable.
     */
    public function adjustPoints(Request $request, LoyaltyService $loyalty, $id)
    {
        $customer = Customer::with('user')->findOrFail($id);

        if (! $loyalty->enabled()) {
            return back()->with('error', 'The loyalty programme is switched off.');
        }

        if (! $customer->user) {
            return back()->with('error', 'That customer has no account to hold points.');
        }

        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'points.not_in'   => 'Enter a positive number to add points, or a negative one to remove them.',
            'reason.required' => 'Give a reason for this adjustment.',
            'reason.min'      => 'Give a little more detail than that.',
        ]);

        $applied = $loyalty->adjust($customer->user, (int) $data['points'], $data['reason']);

        if ($applied === 0) {
            return back()->with('error', 'Nothing to remove — that customer has no points.');
        }

        AuditLog::record(
            'loyalty.adjusted', $customer->user,
            ['balance' => $loyalty->balanceFor($customer->user) - $applied],
            ['points' => $applied, 'balance' => $loyalty->balanceFor($customer->user)],
            $data['reason'],
            label: $customer->user->name,
        );

        return back()->with('success', sprintf(
            '%s %d point%s %s %s. New balance: %d.',
            $applied > 0 ? 'Added' : 'Removed',
            abs($applied), abs($applied) === 1 ? '' : 's',
            $applied > 0 ? 'to' : 'from',
            $customer->user->name,
            $loyalty->balanceFor($customer->user),
        ));
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
                    // Identifies nobody, and answers the question the shop
                    // would otherwise have to open every row for: how much of
                    // this list is accounts versus counter records.
                    $c->customer_type->label(),
                    $c->user?->vehicles->count() ?? 0,
                    $c->bookings_count,
                    $c->bookings()->latest('booking_date')->value('booking_date') ?? 'Never',
                ];
            }
        })();

        return CsvExport::stream(
            CsvExport::filename('customers'),
            ['Customer', 'Type', 'Vehicles', 'Total Bookings', 'Last Visit'],
            $rows
        );
    }
}