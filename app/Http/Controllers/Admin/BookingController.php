<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['customer', 'service', 'vehicle', 'employee'])
                        ->latest()
                        ->get();

        $counts = [
            'confirmed'   => $bookings->where('status', 'confirmed')->count(),
            'pending'     => $bookings->where('status', 'pending')->count(),
            'in_progress' => $bookings->where('status', 'in_progress')->count(),
            'cancelled'   => $bookings->where('status', 'cancelled')->count(),
        ];

        $services = Service::all();

        return view('admin.bookings.index', compact('bookings', 'counts', 'services'));
    }

    public function create()
    {
        return view('admin.bookings.create');
    }

    public function store()
    {
        // TODO: validate + save to DB
    }

    public function edit($id)
    {
        return view('admin.bookings.create');
    }

    public function update($id)
    {
        // TODO: validate + update in DB
    }

    public function schedule()
    {
        $bookings = Booking::with(['customer', 'service', 'vehicle'])
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
    $cancelled = Booking::with(['customer', 'service', 'vehicle'])
                    ->where('status', 'cancelled')
                    ->latest()
                    ->get();

    $services = Service::all();

    return view('admin.bookings.cancelled', compact('cancelled', 'services'));
}

    public function rebook($id)
    {
        // TODO: clone cancelled booking as new pending booking
    }
}