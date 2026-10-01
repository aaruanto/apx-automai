<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Employee;
use App\Mail\BookingConfirmed;
use App\Services\BookingAvailability;
use App\Exceptions\SlotUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['user', 'service', 'vehicle', 'employee'])
                        ->latest()
                        ->get();

        $counts = [
            'confirmed'   => $bookings->where('status', 'confirmed')->count(),
            'pending'     => $bookings->where('status', 'pending')->count(),
            'in_progress' => $bookings->where('status', 'in_progress')->count(),
            'cancelled'   => $bookings->where('status', 'cancelled')->count(),
        ];

        $services = Service::orderBy('name')->get();

        return view('admin.bookings.index', compact('bookings', 'counts', 'services'));
    }

    public function create()
    {
        $services  = Service::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('admin.bookings.create', compact('services', 'employees'));
    }

    // ── FIX #2: real validation + one shared availability gate ───────────────
    public function store(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'plate'          => 'required|string|max:20',
            'service_id'     => 'required|exists:services,id',
            'booking_date'   => 'required|date|after_or_equal:today', // no past dates
            'booking_time'   => 'required',
        ]);

        $user = \App\Models\User::firstOrCreate(
            ['phone' => $request->customer_phone],
            [
                'name'     => $request->customer_name,
                'email'    => $request->customer_email
                              ?? strtolower(str_replace(' ', '', $request->customer_name)) . '@apxautomai.local',
                'role'     => 'customer',
                'password' => bcrypt('password'),
            ]
        );

        $vehicle = $user->vehicles()->firstOrCreate(
            ['plate_number' => strtoupper($request->plate)],
            [
                'make'  => $request->car_model ?? 'Unknown',
                'model' => $request->car_model ?? '',
                'year'  => null,
            ]
        );

        try {
            $booking = app(BookingAvailability::class)->reserve([
                'user_id'      => $user->id,
                'vehicle_id'   => $vehicle->id,
                'service_id'   => $request->service_id,
                'staff_id'     => $request->staff_id ?? null,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'status'       => 'pending',
                'notes'        => $request->notes,
            ]);
        } catch (SlotUnavailableException $e) {
            return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
        }

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking #' . $booking->reference_number . ' created successfully.');
    }

    public function edit($id)
    {
        $booking   = Booking::with(['user', 'vehicle', 'service', 'employee'])->findOrFail($id);
        $services  = Service::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('admin.bookings.create', compact('booking', 'services', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $booking   = Booking::findOrFail($id);
        $oldStatus = $booking->status;

        $request->validate([
            'service_id'   => 'required|exists:services,id',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
            'status'       => 'required|in:pending,confirmed,in_progress,completed,cancelled',
        ]);

        // Only re-run the capacity check when the slot this booking occupies
        // actually changes — a pure status/notes edit shouldn't get blocked
        // by the booking's own existing slot.
        $rescheduled = $request->service_id != $booking->service_id
            || $request->booking_date !== $booking->booking_date
            || $request->booking_time !== $booking->booking_time;

        $duration = $booking->duration;

        if ($rescheduled) {
            $duration = Service::find($request->service_id)->duration;

            try {
                app(BookingAvailability::class)->reschedule(
                    $booking, $request->booking_date, $request->booking_time, $duration
                );
            } catch (SlotUnavailableException $e) {
                return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
            }
        }

        $booking->update([
            'service_id'   => $request->service_id,
            'staff_id'     => $request->staff_id ?? $booking->staff_id,
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time,
            'duration'     => $duration,
            'status'       => $request->status,
            'notes'        => $request->notes,
        ]);

        if ($request->plate && $booking->vehicle) {
            $booking->vehicle->update([
                'plate_number' => strtoupper($request->plate),
                'model'        => $request->car_model ?? $booking->vehicle->model,
            ]);
        }

        // Send confirmation email when status changes to 'confirmed'
        if ($oldStatus !== 'confirmed' && $request->status === 'confirmed') {
            $booking->load(['user', 'service', 'vehicle']);
            if (
                $booking->user &&
                $booking->user->email &&
                !str_ends_with($booking->user->email, '@apxautomai.local')
            ) {
                try {
                    Mail::to($booking->user->email)->send(new BookingConfirmed($booking));
                } catch (\Exception $e) {
                    \Log::error('Booking confirmation email failed: ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully.');
    }

    public function schedule()
    {
        $bookings = Booking::with(['user', 'service', 'vehicle'])
                        ->whereDate('booking_date', today())
                        ->orderBy('booking_time')
                        ->get();

        $totalToday = $bookings->count();

        $statuses = [
            'in_progress' => $bookings->where('status', 'in_progress')->count(),
            'confirmed'   => $bookings->where('status', 'confirmed')->count(),
            'pending'     => $bookings->where('status', 'pending')->count(),
            'cancelled'   => $bookings->where('status', 'cancelled')->count(),
        ];

        return view('admin.bookings.schedule', compact('bookings', 'totalToday', 'statuses'));
    }

    public function cancelled()
    {
        $cancelled = Booking::with(['user', 'service', 'vehicle'])
                        ->where('status', 'cancelled')
                        ->latest()
                        ->get();

        $services = Service::orderBy('name')->get();

        return view('admin.bookings.cancelled', compact('cancelled', 'services'));
    }

    public function rebook($id)
    {
        $original  = Booking::with(['user', 'vehicle', 'service', 'employee'])->findOrFail($id);
        $services  = Service::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('admin.bookings.create', [
            'services'  => $services,
            'employees' => $employees,
            'prefill'   => $original,
        ]);
    }

    public function destroy($id)
    {
        Booking::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'cancelled']);

        return response()->json(['success' => true]);
    }

    // ── Mark a booking "Arrived" — in_progress protects it from the
    //    no-show auto-cancel job. ──────────────────────────────────────────
    public function arrive($id)
    {
        $booking = Booking::whereIn('status', ['pending', 'confirmed'])->findOrFail($id);

        $booking->update([
            'status'     => 'in_progress',
            'arrived_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}