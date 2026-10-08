<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Mail\BookingReceived;
use App\Mail\GuestAccountCreated;
use App\Services\BookingAvailability;
use App\Exceptions\SlotUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class GuestBookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'guest_name'     => 'required|string|max:255',
            'guest_email'    => 'required|email|max:255',
            'booking_date'   => 'required|date|after_or_equal:today',
            'booking_time'   => 'required',
            'service_ids'    => 'required|array|min:1',
            'service_ids.*'  => 'integer|exists:services,id',
            'vehicle_make'   => 'required|string|max:100',
            'vehicle_model'  => 'required|string|max:100',
            'vehicle_year'   => 'required|integer|min:2000',
            'vehicle_plate'  => ['required', 'string', 'max:20', 'regex:/^[A-Z]{3} \d{3,4}$/i'],
            'vehicle_type'   => 'nullable|in:car,motorcycle',
        ], [
            'vehicle_plate.regex' => 'Enter a valid plate number (e.g. ABC 1234).',
        ]);

        // ── Block existing registered accounts from booking as guest ──────
        $existingUser = User::where('email', $request->guest_email)->first();

        if ($existingUser && $existingUser->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'This email already has an account. Please login to book a service.',
            ], 422);
        }

        // ── Find or create a guest/customer user account ──────────────────
        $user = User::firstOrCreate(
            ['email' => $request->guest_email],
            [
                'name'      => $request->guest_name,
                'phone'     => $request->guest_phone ?? null,
                'role'      => 'customer',
                'password'  => bcrypt(Str::random(16)),
                'is_active' => true,
            ]
        );

        $isNewGuest = $user->wasRecentlyCreated;

        if (!$isNewGuest) {
            $user->update([
                'name'  => $request->guest_name,
                'phone' => $request->guest_phone ?? $user->phone,
            ]);
        }

        // ── Find or create the vehicle ────────────────────────────────────
        // Scoped to this user on purpose. Looking up by plate alone used to bind
        // the booking to whichever account already owned that plate, which both
        // leaked that customer's vehicle details and let anyone who could read a
        // plate off a car attach themselves to it.
        $plateKey = strtoupper(trim($request->vehicle_plate));

        $vehicle = Vehicle::where('plate_number', $plateKey)
            ->where('user_id', $user->id)
            ->first();

        // plate_number is globally unique, so a plate held by someone else can't
        // be reused — say so rather than failing on the constraint.
        if (! $vehicle && Vehicle::where('plate_number', $plateKey)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'That plate number is already registered to another account. Please log in to book with it, or contact us if this is your vehicle.',
            ], 422);
        }

        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'user_id'      => $user->id,
                'plate_number' => $plateKey,
                'make'         => $request->vehicle_make,
                'model'        => $request->vehicle_model,
                'vehicle_type' => in_array($request->vehicle_type, ['car', 'motorcycle']) ? $request->vehicle_type : 'car',
                'year'         => $request->vehicle_year,
                'color'        => null,
            ]);
        }

        // ── Load the selected services by ID ───────────────────────────────
        $services = Service::whereIn('id', $request->service_ids)->get();

        if ($services->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected services could not be found. Please try again.',
            ], 422);
        }

        $firstService = $services->first();

        // ── Create booking — one summed-duration block across all selected
        //    services, gated by the same capacity/hours rules as every
        //    other booking flow ────────────────────────────────────────────
        $availability = app(BookingAvailability::class);

        try {
            $booking = $availability->reserve([
                'user_id'      => $user->id,
                'vehicle_id'   => $vehicle->id,
                'service_id'   => $firstService->id,
                'service_ids'  => $request->service_ids,
                'staff_id'     => null,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'status'       => 'pending',
                // Extra services used to be appended to the note as
                // "[Additional services: ...]" because only one could be
                // stored. They are real pivot rows now, so the note is the
                // customer's own text again.
                'notes'        => $request->notes ?: null,
            ]);
        } catch (SlotUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $ref = $booking->reference_number;

        $this->notifyAdminsOfBooking($booking);

        // ── Send emails ───────────────────────────────────────────────────
        try {
            $booking->load(['user', 'service', 'vehicle']);

            // Always send booking received email
            Mail::to($request->guest_email)->send(new BookingReceived($booking));

            // If brand new guest account, send password setup email
            if ($isNewGuest) {
                $token    = Password::createToken($user);
                $resetUrl = url(route('password.reset', [
                    'token' => $token,
                    'email' => $user->email,
                ], false));

                Mail::to($request->guest_email)->send(new GuestAccountCreated($user, $resetUrl));
            }

        } catch (\Exception $e) {
            \Log::error('Guest booking email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'        => true,
            'reference'      => $ref,
            'booking_id'     => $booking->id,
            'create_account' => $request->boolean('create_account'),
            'register_url'   => route('register'),
        ]);
    }

    /**
     * Alert every admin that a booking has come in.
     *
     * Staff are left out on purpose: the shop's admins triage requests, and
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
