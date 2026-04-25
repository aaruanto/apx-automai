<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $bookings = Booking::with(['service', 'vehicle', 'employee'])
                        ->where('user_id', $user->id)
                        ->latest()
                        ->get();

        $vehicles = Vehicle::where('user_id', $user->id)->get();
        $services = Service::all();

        $upcoming  = $bookings->whereIn('status', ['confirmed', 'pending'])->count();
        $completed = $bookings->where('status', 'completed')->count();

        $bookingsJs = $bookings->map(function($b) {
            return [
                'id'        => '#BK-' . str_pad($b->id, 4, '0', STR_PAD_LEFT),
                'dbId'      => $b->id,
                'service'   => $b->service->name ?? 'N/A',
                'serviceId' => $b->service_id,
                'date'      => $b->booking_date,
                'day'       => date('d', strtotime($b->booking_date)),
                'mon'       => date('M', strtotime($b->booking_date)),
                'yr'        => date('Y', strtotime($b->booking_date)),
                'time'      => date('g:i A', strtotime($b->booking_time)),
                'staff'     => $b->employee->name ?? 'TBA',
                'vehicle'   => ($b->vehicle->make ?? '') . ' (' . ($b->vehicle->plate_number ?? 'N/A') . ')',
                'amount'    => 'TBA',
                'status'    => in_array($b->status, ['confirmed', 'pending']) ? 'upcoming' : $b->status,
            ];
        })->values();

        $vehiclesJs = $vehicles->map(function($v, $i) {
            return [
                'id'      => $v->id,
                'make'    => $v->make . ' ' . $v->model,
                'year'    => $v->year,
                'plate'   => $v->plate_number,
                'color'   => $v->color ?? '',
                'primary' => $i === 0,
            ];
        })->values();

        return view('dashboard.customer-dashboard', compact(
            'bookings', 'vehicles', 'services',
            'upcoming', 'completed',
            'bookingsJs', 'vehiclesJs'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id'   => 'required|exists:services,id',
            'vehicle_id'   => 'nullable|exists:vehicles,id',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
        ]);

        $booking = Booking::create([
            'user_id'          => Auth::id(),
            'service_id'       => $request->service_id,
            'vehicle_id'       => $request->vehicle_id,
            'booking_date'     => $request->booking_date,
            'booking_time'     => $request->booking_time,
            'notes'            => $request->notes,
            'status'           => 'pending',
            'reference_number' => 'BK-' . str_pad(Booking::count() + 1, 4, '0', STR_PAD_LEFT),
        ]);

        return response()->json([
            'success'    => true,
            'reference'  => $booking->reference_number,
            'booking_id' => $booking->id,
        ]);
    }

    public function storeVehicle(Request $request)
{
    $body = json_decode($request->getContent(), true);
    $plate = $body['plate'] ?? 'N/A';

    // If a soft-deleted vehicle with this plate exists, permanently delete it first
    Vehicle::withTrashed()
        ->where('plate_number', $plate)
        ->whereNotNull('deleted_at')
        ->forceDelete();

    $vehicle = Vehicle::create([
        'user_id'      => Auth::id(),
        'make'         => $body['brand'] ?? 'Unknown',
        'model'        => $body['model'] ?? '',
        'plate_number' => $plate,
        'year'         => $body['year'] ?? 2020,
        'color'        => $body['color'] ?? '',
    ]);

    return response()->json([
        'success' => true,
        'vehicle' => [
            'id'      => $vehicle->id,
            'make'    => $vehicle->make . ' ' . $vehicle->model,
            'year'    => $vehicle->year,
            'plate'   => $vehicle->plate_number,
            'color'   => $vehicle->color,
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
}