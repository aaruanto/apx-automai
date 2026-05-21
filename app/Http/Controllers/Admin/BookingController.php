<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Mail\BookingConfirmed;
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

    public function store(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'plate'          => 'required|string|max:20',
            'service_id'     => 'required|exists:services,id',
            'booking_date'   => 'required|date',
            'booking_time'   => 'required',
        ]);

        $user = \App\Models\User::firstOrCreate(
            ['phone' => $request->customer_phone],
            [
                'name'     => $request->customer_name,
                'email'    => $request->customer_email ?? strtolower(str_replace(' ', '', $request->customer_name)) . '@apxautomai.local',
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

        $booking = Booking::create([
            'user_id'          => $user->id,
            'vehicle_id'       => $vehicle->id,
            'service_id'       => $request->service_id,
            'staff_id'         => $request->staff_id ?? null,
            'booking_date'     => $request->booking_date,
            'booking_time'     => $request->booking_time,
            'status'           => 'pending',
            'notes'            => $request->notes,
            'reference_number' => 'BK-' . str_pad((Booking::withTrashed()->max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT),
        ]);

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

        $booking->update([
            'service_id'   => $request->service_id,
            'staff_id'     => $request->staff_id ?? $booking->staff_id,
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time,
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
}