<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Bundle;
use App\Models\Customer;
use App\Models\LoyaltyLedger;
use App\Models\Service;
use App\Models\Vehicle;
use App\Services\AccountAnonymizer;
use App\Services\BookingAvailability;
use App\Services\BookingPricing;
use App\Services\LoyaltyService;
use App\Support\BookingPolicy;
use App\Support\PlateNumber;
use App\Exceptions\SlotUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = Auth::user();
        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['phone' => $user->phone ?? null]
        );

        $bookings = Booking::with(['service', 'vehicle', 'mechanic', 'services'])
                        ->where('user_id', $user->id)
                        ->latest()
                        ->get();

        $vehicles = Vehicle::where('user_id', $user->id)->get();
        $services = Service::all();

        $upcoming      = $bookings->whereIn('status', ['confirmed', 'pending'])->count();
        $completed     = $bookings->where('status', 'completed')->count();
        $totalBookings = $bookings->count();
        // Completed work only, from the per-booking price snapshots.
        $totalSpent    = $bookings->where('status', 'completed')->sum(fn ($b) => $b->total_price);

        $bookingsJs = $bookings->map(function($b) {
            return [
                'id'        => '#' . $b->reference_number,
                'dbId'      => $b->id,
                'service'   => $b->service->name ?? 'N/A',
                'serviceId' => $b->service_id,
                'date'      => $b->booking_date,
                'day'       => date('d', strtotime($b->booking_date)),
                'mon'       => date('M', strtotime($b->booking_date)),
                'yr'        => date('Y', strtotime($b->booking_date)),
                'time'      => date('g:i A', strtotime($b->booking_time)),
                'mechanic'  => $b->mechanic->name ?? 'TBA',
                'vehicle'   => ($b->vehicle->make ?? '') . ' (' . ($b->vehicle?->display_plate ?? 'Not provided') . ')',
                'vehicle_id'=> $b->vehicle_id,
                'amount'    => 'TBA',
                'status'    => in_array($b->status, ['confirmed', 'pending']) ? 'upcoming' : $b->status,
                // So a customer can see why a booking of theirs was cancelled,
                // including when the shop or the no-show sweep did it.
                'cancelReason' => $b->cancel_reason,
                'cancelledAt'  => $b->cancelled_at?->format('M j, Y g:i A'),
            ];
        })->values();

        // The same calculation the reminder emails use, so the dashboard and
        // the inbox cannot disagree about whether a car is due. Rules are
        // loaded once here rather than per vehicle.
        $dueCalculator = app(\App\Services\MaintenanceDue::class);
        $rules         = \App\Models\MaintenanceRule::active()
            ->with(['bundle', 'service'])
            ->orderBy('sort_order')
            ->get();

        $vehiclesJs = $vehicles->map(function ($v) use ($dueCalculator, $rules) {
            $due = $dueCalculator->forVehicle($v, $rules)
                ->sortBy(fn (array $d) => [$d['overdue'] ? 0 : 1, $d['due_date']->timestamp])
                ->first();

            return [
                'id'      => $v->id,
                'make'    => $v->make . ' ' . $v->model,
                'year'    => $v->year,
                'plate'   => $v->display_plate,
                'color'   => $v->color ?? '',
                'type'    => $v->vehicle_type ?? 'car',
                'primary' => (bool) $v->is_primary,
                'mileage' => $v->mileage,

                'lastService' => $v->last_service_date?->format('M j, Y'),

                // null when nothing is due, which is the common case — the
                // badge is drawn only when there is something to say.
                'due' => $due ? [
                    'name'           => $due['rule']->name,
                    'dueDate'        => $due['due_date']->format('M j, Y'),
                    'reason'         => $due['reason'],
                    'overdue'        => $due['overdue'],
                    'recommendation' => $due['rule']->recommendation(),
                ] : null,
            ];
        })->values();

        // Replaces the four hardcoded "promos" this panel used to show, which
        // were invented placeholders with 2025 expiry dates and discount
        // codes that nothing in the app would ever honour.
        $bundlesJs = Bundle::with('services')->bookable()->orderBy('price')->get()
            ->map(fn ($b) => [
                'id'          => $b->id,
                'name'        => $b->name,
                'description' => $b->description,
                'price'       => (float) $b->price,
                'list'        => $b->listPrice(),
                'saves'       => $b->savings(),
                'percent'     => $b->savingsPercent(),
                'vehicle'     => $b->vehicle_type,
                'ends'        => $b->ends_at?->format('M j, Y'),
                'includes'    => $b->services->pluck('name')->all(),
                'serviceIds'  => $b->services->pluck('id')->all(),
                'duration'    => $b->totalDuration(),
            ])->values();

        // Loyalty, for the rewards panel and the booking summary. The
        // balance comes from the ledger rather than a stored counter, so it
        // is always the sum of the movements the customer can see listed.
        $loyaltyService = app(LoyaltyService::class);
        $loyalty = [
            'enabled'    => $loyaltyService->enabled(),
            'balance'    => $loyaltyService->balanceFor(Auth::user()),
            'value'      => $loyaltyService->pointValue(),
            'perPeso'    => $loyaltyService->pesosPerPoint(),
            'minRedeem'  => $loyaltyService->minimumRedemption(),
            'maxPercent' => $loyaltyService->maxRedeemPercent(),
            'history'    => $loyaltyService->enabled()
                ? LoyaltyLedger::with('booking:id,reference_number')
                    ->where('user_id', Auth::id())
                    ->recentFirst()->limit(25)->get()
                    ->map(fn ($row) => [
                        'points'  => $row->points,
                        'balance' => $row->balance_after,
                        'what'    => $row->describe(),
                        'when'    => $row->created_at->format('M j, Y'),
                        'expires' => $row->expires_at?->format('M j, Y'),
                    ])->values()
                : collect(),
        ];

        return view('dashboard.customer-dashboard', compact(
            'bookings', 'vehicles', 'services',
            'upcoming', 'completed', 'totalBookings', 'totalSpent',
            'bookingsJs', 'vehiclesJs', 'bundlesJs', 'loyalty',
            'customer'
        ));
    }

    /**
     * The customer's own figures and bookings, for polling.
     *
     * Scoped to the signed-in user throughout, so this cannot leak another
     * customer's bookings even if the id were tampered with — there is no id
     * to tamper with.
     */
    public function live()
    {
        $user = Auth::user();

        $bookings = Booking::with(['service', 'vehicle', 'mechanic', 'services'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'stats' => [
                'upcoming'  => $bookings->whereIn('status', ['confirmed', 'pending'])->count(),
                'completed' => $bookings->where('status', 'completed')->count(),
                'total'     => $bookings->count(),
                'spent'     => $bookings->where('status', 'completed')->sum(fn ($b) => $b->total_price),
            ],
            'bookings' => $bookings->take(20)->map(fn (Booking $b) => [
                'reference' => $b->reference_number,
                'services'  => $b->service_list,
                'date'      => $b->booking_date,
                'time'      => $b->booking_time,
                'mechanic'  => $b->mechanic->name ?? 'TBA',
                'status'    => $b->status,
            ])->values(),
            'unread'     => $user->unreadNotifications()->count(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    // FIX #3: validate input, confirm vehicle ownership, and run the availability gate
    public function store(Request $request)
    {
        // The booking form posts a JSON body. Make sure it's parsed whether or not the
        // Content-Type header was set, so validation actually sees the fields.
        $json = json_decode($request->getContent(), true);
        if (is_array($json)) {
            $request->merge($json);
        }

        // A booking may cover several services. service_id is still accepted
        // so an older client posting a single id keeps working.
        if (! $request->filled('service_ids') && $request->filled('service_id')) {
            $request->merge(['service_ids' => [$request->input('service_id')]]);
        }

        // A bundle supplies its own services, so service_ids is only required
        // when no bundle was chosen.
        $bundle = $request->filled('bundle_id')
            ? Bundle::with('services')->bookable()->find($request->input('bundle_id'))
            : null;

        if ($request->filled('bundle_id') && ! $bundle) {
            return response()->json([
                'success' => false,
                'message' => 'That bundle is no longer available. Please choose again.',
            ], 422);
        }

        $data = $request->validate([
            'service_ids'   => [$bundle ? 'nullable' : 'required', 'array'],
            'service_ids.*' => 'integer|exists:services,id',
            'vehicle_id'    => 'required|exists:vehicles,id',
            'booking_date'  => 'required|date|after_or_equal:today',
            'booking_time'  => 'required',
            'notes'         => 'nullable|string|max:500',
        ]);

        // The vehicle must belong to THIS customer — stops booking with someone else's vehicle_id.
        $vehicle = Vehicle::where('id', $data['vehicle_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($bundle && ! $bundle->suitsVehicleType($vehicle->vehicle_type)) {
            return response()->json([
                'success' => false,
                'message' => 'That bundle is for '.$bundle->vehicle_type.'s only.',
            ], 422);
        }

        // Not every service suits every vehicle. Checked against the vehicle
        // already fetched above, which is proven to belong to this customer,
        // rather than against anything the request said about it. A bundle
        // carries its own vehicle_type and was checked a few lines up.
        if (! $bundle) {
            $chosen       = Service::whereIn('id', $data['service_ids'])->get();
            $wrongVehicle = BookingPolicy::servicesNotFor($chosen, $vehicle->vehicle_type);

            if ($wrongVehicle->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => BookingPolicy::wrongVehicleMessage($wrongVehicle, $vehicle->vehicle_type),
                ], 422);
            }
        }

        // Complimentary work accompanies paid work; it is not a booking of its
        // own. Checked before reserve() so a refused booking never takes a slot
        // and never has to be cleaned up afterwards.
        // The bundle goes to the policy rather than being short-circuited here,
        // so "a bundle is paid work whatever it contains" is stated once, in
        // BookingPolicy, instead of being re-decided at each call site — which
        // is how the two drift apart. The guest controller already does this.
        //
        // service_ids stays guarded: validation makes it nullable when a bundle
        // was chosen, so with a bundle the key may not be there at all.
        if (BookingPolicy::isFreeOnly(
            Service::whereIn('id', $data['service_ids'] ?? [])->get(),
            $bundle
        )) {
            return response()->json([
                'success' => false,
                'message' => BookingPolicy::FREE_ONLY_MESSAGE,
            ], 422);
        }

        try {
            $booking = app(BookingAvailability::class)->reserve([
                'user_id'      => Auth::id(),
                // The bundle decides what is on the booking, not the browser.
                'service_ids'  => $bundle
                    ? $bundle->services->pluck('id')->all()
                    : $data['service_ids'],
                'vehicle_id'   => $data['vehicle_id'],
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'notes'        => $data['notes'] ?? null,
                'status'       => 'pending',
            ]);

            // Price it. Server side, from database rows — nothing the browser
            // posted about money is read. Done after reserve so the slot
            // check still runs against the full service duration.
            $pricing  = app(BookingPricing::class);
            $priced   = $bundle
                ? $bundle->services
                : Service::whereIn('id', $data['service_ids'])->get();

            // How many points the customer asked to spend is a ceiling, not
            // an instruction: quote() caps it against their real balance and
            // the maximum share of a booking points may cover.
            $pricing->apply(
                $booking,
                $pricing->quote($priced, $bundle, Auth::user(), (int) $request->input('points', 0)),
                $priced
            );
        } catch (SlotUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->notifyAdminsOfBooking($booking);

        return response()->json([
            'success'    => true,
            'reference'  => $booking->reference_number,
            'booking_id' => $booking->id,
        ]);
    }

    public function storeVehicle(Request $request)
    {
        // The form posts a JSON body; merge it so validation can see the fields.
        $json = json_decode($request->getContent(), true);
        if (is_array($json)) {
            $request->merge($json);
        }

        // Normalised before validating, not after: the rules reject a leading,
        // trailing or doubled space, so cleaning has to happen first or a
        // customer typing "abc 1234 " is refused rather than tidied.
        $request->merge(['plate' => PlateNumber::normalise($request->input('plate'))]);

        $data = $request->validate([
            'brand'        => 'required|string|max:100',
            'model'        => 'nullable|string|max:100',
            'plate'        => PlateNumber::rules(),
            'year'         => 'required|integer|min:1990|max:2100',
            'color'        => 'nullable|string|max:40',
            'vehicle_type' => 'nullable|in:car,motorcycle',

            // Optional on purpose. Plenty of customers do not know their
            // odometer reading, and nothing depends on it: maintenance
            // reminders are time-only. It is kept because the shop finds the
            // record useful, not because the system reads it.
            'mileage'      => 'nullable|integer|min:0|max:2000000',
        ], [
            ...PlateNumber::messages('plate'),
        ]);

        $plate = PlateNumber::normalise($data['plate']);

        // Scoped to the current user — this used to purge any soft-deleted row
        // with a matching plate, which let one customer permanently destroy
        // another customer's vehicle record.
        Vehicle::withTrashed()
            ->where('plate_number', $plate)
            ->where('user_id', Auth::id())
            ->whereNotNull('deleted_at')
            ->forceDelete();

        if (Vehicle::where('plate_number', $plate)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'That plate number is already registered.',
            ], 422);
        }

        // The booking form preselects the primary vehicle, so a customer whose
        // vehicles are all non-primary got no default. The first one they add
        // becomes primary.
        $isFirst = ! Vehicle::where('user_id', Auth::id())->exists();

        $vehicle = Vehicle::create([
            'user_id'      => Auth::id(),
            'make'         => $data['brand'],
            'model'        => $data['model'] ?? '',
            'plate_number' => $plate,
            'vehicle_type' => $data['vehicle_type'] ?? 'car',
            'year'         => $data['year'],
            'color'        => $data['color'] ?? '',
            'is_primary'   => $isFirst,
            'mileage'      => $data['mileage'] ?? null,

            // Dated so the reading can be shown with its age. A number from
            // two years ago should not be presented as current.
            'mileage_updated_at' => isset($data['mileage']) ? now()->toDateString() : null,
        ]);

        return response()->json([
            'success' => true,
            'vehicle' => [
                'id'      => $vehicle->id,
                'make'    => $vehicle->make . ' ' . $vehicle->model,
                'year'    => $vehicle->year,
                'plate'   => $vehicle->display_plate,
                'color'   => $vehicle->color,
                'type'    => $vehicle->vehicle_type,
                'mileage' => $vehicle->mileage,
                'primary' => (bool) $vehicle->is_primary,
            ]
        ]);
    }

    /**
     * Edit one of the signed-in customer's own vehicles.
     *
     * Looked up by owner as well as id, so passing someone else's vehicle id
     * is a 404 rather than an edit. destroyVehicle already did this; there was
     * simply no update path at all before.
     */
    public function updateVehicle(Request $request, $id)
    {
        $json = json_decode($request->getContent(), true);
        if (is_array($json)) {
            $request->merge($json);
        }

        $vehicle = Vehicle::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        // Normalised before validating, not after: the rules reject a leading,
        // trailing or doubled space, so cleaning has to happen first or a
        // customer typing "abc 1234 " is refused rather than tidied.
        $request->merge(['plate' => PlateNumber::normalise($request->input('plate'))]);

        $data = $request->validate([
            'brand'        => 'required|string|max:100',
            'model'        => 'nullable|string|max:100',
            'plate'        => PlateNumber::rules(),
            'year'         => 'required|integer|min:1990|max:2100',
            'color'        => 'nullable|string|max:40',
            'vehicle_type' => 'nullable|in:car,motorcycle',

            // Optional on purpose. Plenty of customers do not know their
            // odometer reading, and nothing depends on it: maintenance
            // reminders are time-only. It is kept because the shop finds the
            // record useful, not because the system reads it.
            'mileage'      => 'nullable|integer|min:0|max:2000000',
        ], [
            ...PlateNumber::messages('plate'),
        ]);

        $plate = PlateNumber::normalise($data['plate']);

        // Plates are unique across the system, since a plate identifies one
        // real vehicle. The vehicle's own current plate is allowed through so
        // editing anything else about it does not trip the check.
        $taken = Vehicle::where('plate_number', $plate)
            ->where('id', '!=', $vehicle->id)
            ->exists();

        if ($taken) {
            return response()->json([
                'success' => false,
                'message' => 'That plate number is already registered.',
            ], 422);
        }

        $vehicle->update([
            'make'         => $data['brand'],
            'model'        => $data['model'] ?? '',
            'plate_number' => $plate,
            'vehicle_type' => $data['vehicle_type'] ?? $vehicle->vehicle_type,
            'year'         => $data['year'],
            'color'        => $data['color'] ?? '',
        ]);

        // Only touched when a reading was actually submitted. A form saved
        // with the mileage field left blank is someone editing their colour,
        // not someone telling us their car has done zero kilometres.
        if (array_key_exists('mileage', $data) && $data['mileage'] !== null) {
            $vehicle->update([
                'mileage'            => $data['mileage'],
                'mileage_updated_at' => now()->toDateString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'vehicle' => [
                'id'      => $vehicle->id,
                'make'    => trim($vehicle->make . ' ' . $vehicle->model),
                'year'    => $vehicle->year,
                'plate'   => $vehicle->display_plate,
                'color'   => $vehicle->color,
                'type'    => $vehicle->vehicle_type,
                'mileage' => $vehicle->mileage,
                'primary' => (bool) $vehicle->is_primary,
            ],
        ]);
    }

    public function destroyVehicle($id)
    {
        $vehicle = Vehicle::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $vehicle->delete();

        return response()->json(['success' => true]);
    }

    public function setPrimaryVehicle($id)
    {
        $user = Auth::user();

        // Unset all primaries for this user first
        Vehicle::where('user_id', $user->id)->update(['is_primary' => false]);

        // Set the chosen one
        $vehicle = Vehicle::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $vehicle->update(['is_primary' => true]);

        return response()->json(['success' => true]);
    }

    public function cancelBooking(Request $request, $id)
    {
        $booking = Booking::where('id', $id)
                          ->where('user_id', Auth::id())
                          ->whereIn('status', ['pending', 'confirmed'])
                          ->firstOrFail();

        // Optional here, unlike the admin side: a customer cancelling their
        // own booking owes no justification, but the shop still wants to know
        // who cancelled and when, and the reason when one is offered.
        $reason = trim((string) ($request->input('reason') ?? ''));

        $booking->cancel($reason !== '' ? $reason : 'Cancelled by customer', Auth::id());

        return response()->json(['success' => true]);
    }

    public function updateProfile(Request $request)
    {
        // The form posts a JSON body; merge it so validation can see the fields.
        $json = json_decode($request->getContent(), true);
        if (is_array($json)) {
            $request->merge($json);
        }

        // Empty strings from untouched date/text inputs would otherwise fail
        // the date rule rather than reading as "not set".
        foreach (['dob', 'address', 'phone'] as $optional) {
            if ($request->input($optional) === '') {
                $request->merge([$optional => null]);
            }
        }

        // There was no validation here at all, so a bad value failed silently.
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'dob'     => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Your full name is required.',
        ]);

        $user = Auth::user();

        // name was never written here. The form offered it, the request never
        // carried it, and this method never looked for it, so saving appeared
        // to work and changed nothing.
        $user->update([
            'name'  => trim($data['name']),
            'phone' => $data['phone'] ?? $user->phone,
        ]);

        // Email is intentionally absent: the field is readonly on the form and
        // is not accepted here, so Phase 1's uniqueness rules have nothing to
        // guard on this path.

        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['phone'   => $user->phone ?? null]
        );

        $customer->update([
            'phone'   => $data['phone']   ?? $customer->phone,
            'dob'     => $data['dob']     ?? $customer->dob,
            'address' => $data['address'] ?? $customer->address,
        ]);

        return response()->json([
            'success' => true,
            'name'    => $user->name,
            'initials' => strtoupper(mb_substr($user->name, 0, 2)),
        ]);
    }

    /**
     * Change the signed-in customer's password from inside the dashboard.
     *
     * The Settings tab previously linked to /forgot-password, which emails a
     * reset link — and with mail unconfigured nothing ever arrived, so
     * customers had no way to change their password at all.
     */
    public function updatePassword(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        if (is_array($json)) {
            $request->merge($json);
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
        ]);

        $user = Auth::user();

        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Your new password must be different from your current one.',
            ], 422);
        }

        // The hashed cast handles hashing; the plain value is never stored,
        // logged, or returned.
        $user->update(['password' => $request->password]);

        // Signs out anywhere else this account is open.
        Auth::logoutOtherDevices($request->password);

        return response()->json([
            'success' => true,
            'message' => 'Password updated. Any other devices have been signed out.',
        ]);
    }
    // ── Account deletion (self-service, no admin approval) ─────────────────

    /**
     * What stands in the way of deleting this account, so the UI can explain
     * it before asking for a password.
     */
    public function accountDeletionStatus(AccountAnonymizer $anonymizer)
    {
        $user = Auth::user();

        $inProgress = $anonymizer->inProgressBookings($user);
        $upcoming   = $anonymizer->upcomingBookings($user)->load('service');

        return response()->json([
            'blocked'     => $inProgress->isNotEmpty(),
            'in_progress' => $inProgress->count(),
            'upcoming'    => $upcoming->map(fn ($b) => [
                'service' => $b->service->name ?? 'Service',
                'date'    => $b->booking_date,
                'time'    => $b->booking_time,
            ])->values(),
        ]);
    }

    public function destroyAccount(Request $request, AccountAnonymizer $anonymizer)
    {
        $request->validate(['password' => 'required|string']);

        $user = Auth::user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'That password is incorrect.',
            ], 422);
        }

        // The shop is physically holding this customer's vehicle and still
        // needs to be able to reach them about it.
        if ($anonymizer->inProgressBookings($user)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your vehicle is currently being serviced and we may need to contact you about it. You can delete your account once the service is completed.',
            ], 422);
        }

        $record = $anonymizer->deactivate($user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success'  => true,
            'redirect' => url('/'),
            'purge_at' => $record->purge_at->format('F j, Y'),
            'grace'    => AccountAnonymizer::GRACE_DAYS,
        ]);
    }

    /**
     * Alert every admin that a booking has come in.
     *
     * Only the admin is notified: mechanics have no login to be notified in, and
     * notifying every mechanic for every booking would be noise.
     */
    private function notifyAdminsOfBooking(\App\Models\Booking $booking): void
    {
        $admins = \App\Models\User::where('role', 'admin')->get();

        \Illuminate\Support\Facades\Notification::send(
            $admins,
            \App\Notifications\BookingNotification::requested($booking->fresh()->load(['user', 'services']))
        );
    }}
