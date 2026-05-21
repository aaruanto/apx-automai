<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Mail\BookingReceived;
use App\Mail\GuestAccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class GuestBookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'guest_name'    => 'required|string|max:255',
            'guest_email'   => 'required|email|max:255',
            'booking_date'  => 'required|date|after_or_equal:today',
            'services'      => 'required|array|min:1',
            'vehicle_make'  => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_year'  => 'required|integer|min:2000',
        ]);

        // ── Block existing registered accounts from booking as guest ──────
        $existingUser = User::where('email', $request->guest_email)->first();

        if ($existingUser && $existingUser->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'This email already has an account. Please login to book a service.',
            ], 422);
        }

        // ── Find or create a guest user account ───────────────────────────
        $user = User::firstOrCreate(
            ['email' => $request->guest_email],
            [
                'name'      => $request->guest_name,
                'phone'     => $request->guest_phone ?? null,
                'role'      => 'customer',
                'password'  => null,
                'is_guest'  => true,
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

        // ── Find or create vehicle (by plate only, ignore user_id) ────────
        $plateKey = $request->vehicle_plate
            ? strtoupper(trim($request->vehicle_plate))
            : ('NO-PLATE-' . $user->id);

        $vehicle = Vehicle::where('plate_number', $plateKey)->first();

        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'user_id'      => $user->id,
                'plate_number' => $plateKey,
                'make'         => $request->vehicle_make,
                'model'        => $request->vehicle_model,
                'year'         => $request->vehicle_year,
                'color'        => null,
            ]);
        }

        // ── Match service names to DB IDs ─────────────────────────────────
        $serviceNames = $request->services;
        $firstService = Service::whereIn('name', $serviceNames)->first();

        if (!$firstService) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected services could not be found. Please try again.',
            ], 422);
        }

        // ── Generate reference number ─────────────────────────────────────
        $ref = 'APX-' . strtoupper(Str::random(6));

        // ── Create booking ────────────────────────────────────────────────
        $booking = Booking::create([
            'user_id'          => $user->id,
            'vehicle_id'       => $vehicle->id,
            'service_id'       => $firstService->id,
            'staff_id'         => null,
            'booking_date'     => $request->booking_date,
            'booking_time'     => $request->booking_time ?? '09:00',
            'status'           => 'pending',
            'notes'            => ($request->notes ?? '') .
                                  (count($serviceNames) > 1
                                    ? "\n[Additional services: " . implode(', ', array_slice($serviceNames, 1)) . "]"
                                    : ''),
            'reference_number' => $ref,
        ]);

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
}