<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestBookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'guest_name'   => 'required|string|max:255',
            'guest_email'  => 'required|email|max:255',
            'booking_date' => 'required|date|after_or_equal:today',
            'services'     => 'required|array|min:1',
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model'=> 'required|string|max:100',
            'vehicle_year' => 'required|integer|min:2000',
        ]);

        // ── Find or create a guest/customer user account ───────────────────
        $user = User::firstOrCreate(
            ['email' => $request->guest_email],
            [
                'name'     => $request->guest_name,
                'phone'    => $request->guest_phone ?? null,
                'role'     => 'customer',
                'password' => bcrypt(Str::random(16)), // random — they didn't register
                'is_active'=> true,
            ]
        );

        // Update name/phone if user already exists but name differs
        if ($user->wasRecentlyCreated === false) {
            $user->update([
                'name'  => $request->guest_name,
                'phone' => $request->guest_phone ?? $user->phone,
            ]);
        }

        // ── Find or create vehicle ─────────────────────────────────────────
        $plateRaw = $request->vehicle_plate
            ? strtoupper(trim($request->vehicle_plate))
            : null;

        $vehicle = Vehicle::firstOrCreate(
            [
                'user_id'      => $user->id,
                'plate_number' => $plateRaw ?? ('NO-PLATE-' . $user->id),
            ],
            [
                'make'  => $request->vehicle_make,
                'model' => $request->vehicle_model,
                'year'  => $request->vehicle_year,
                'color' => null,
            ]
        );

        // ── Match service names to DB IDs ──────────────────────────────────
        $serviceNames = $request->services; // array of strings
        $firstService = Service::whereIn('name', $serviceNames)->first();

        if (!$firstService) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected services could not be found. Please try again.',
            ], 422);
        }

        // ── Generate reference number ──────────────────────────────────────
        $ref = 'APX-' . strtoupper(Str::random(6));

        // ── Create booking (using first/primary service) ───────────────────
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

        // ── If "create_account" flag sent, redirect to register after ──────
        // (handled on frontend — we just return success + flag)

        return response()->json([
            'success'        => true,
            'reference'      => $ref,
            'booking_id'     => $booking->id,
            'create_account' => $request->boolean('create_account'),
            'register_url'   => route('register'),
        ]);
    }
}