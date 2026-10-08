<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Vehicle;
use App\Services\AccountAnonymizer;
use App\Services\BookingAvailability;
use App\Exceptions\SlotUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $bookings = Booking::with(['service', 'vehicle', 'employee'])
                        ->where('user_id', $user->id)
                        ->latest()
                        ->get();

        $vehicles = Vehicle::where('user_id', $user->id)->get();
        $services = Service::all();

        $upcoming      = $bookings->whereIn('status', ['confirmed', 'pending'])->count();
        $completed     = $bookings->where('status', 'completed')->count();
        $totalBookings = $bookings->count();

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
                'staff'     => $b->employee->name ?? 'TBA',
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

        $vehiclesJs = $vehicles->map(function($v) {
            return [
                'id'      => $v->id,
                'make'    => $v->make . ' ' . $v->model,
                'year'    => $v->year,
                'plate'   => $v->display_plate,
                'color'   => $v->color ?? '',
                'type'    => $v->vehicle_type ?? 'car',
                'primary' => (bool) $v->is_primary,
            ];
        })->values();

        return view('dashboard.customer-dashboard', compact(
            'bookings', 'vehicles', 'services',
            'upcoming', 'completed', 'totalBookings',
            'bookingsJs', 'vehiclesJs',
            'customer'
        ));
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

        $data = $request->validate([
            'service_id'   => 'required|exists:services,id',
            'vehicle_id'   => 'required|exists:vehicles,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required',
            'notes'        => 'nullable|string|max:500',
        ]);

        // The vehicle must belong to THIS customer — stops booking with someone else's vehicle_id.
        Vehicle::where('id', $data['vehicle_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        try {
            $booking = app(BookingAvailability::class)->reserve([
                'user_id'      => Auth::id(),
                'service_id'   => $data['service_id'],
                'vehicle_id'   => $data['vehicle_id'],
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'notes'        => $data['notes'] ?? null,
                'status'       => 'pending',
            ]);
        } catch (SlotUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

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

        $data = $request->validate([
            'brand'        => 'required|string|max:100',
            'model'        => 'nullable|string|max:100',
            'plate'        => ['required', 'string', 'max:20', 'regex:/^[A-Z]{3} \d{3,4}$/i'],
            'year'         => 'required|integer|min:1990|max:2100',
            'color'        => 'nullable|string|max:40',
            'vehicle_type' => 'nullable|in:car,motorcycle',
        ], [
            'plate.regex' => 'Enter a valid plate number (e.g. ABC 1234).',
        ]);

        $plate = strtoupper(trim($data['plate']));

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

        $vehicle = Vehicle::create([
            'user_id'      => Auth::id(),
            'make'         => $data['brand'],
            'model'        => $data['model'] ?? '',
            'plate_number' => $plate,
            'vehicle_type' => $data['vehicle_type'] ?? 'car',
            'year'         => $data['year'],
            'color'        => $data['color'] ?? '',
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
                'primary' => false,
            ]
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
        $body = json_decode($request->getContent(), true);
        $user = Auth::user();

        // Save phone to users table
        if (!empty($body['phone'])) {
            $user->update(['phone' => $body['phone']]);
        }

        // Save dob and address to customers table
        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['phone'   => $user->phone ?? null]
        );

        $customer->update([
            'phone'   => $body['phone']   ?? $customer->phone,
            'dob'     => $body['dob']     ?? $customer->dob,
            'address' => $body['address'] ?? $customer->address,
        ]);

        return response()->json(['success' => true]);
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
}
