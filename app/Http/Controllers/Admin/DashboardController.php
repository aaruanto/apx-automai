<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $todayBookings = Booking::whereDate('booking_date', today())->count();
        $pending       = Booking::where('status', 'pending')->count();
        $thisWeek      = Booking::whereBetween('booking_date', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                         ])->count();
        $completed     = Booking::where('status', 'completed')->count();
        $recentBookings = Booking::with(['customer', 'service', 'employee'])
                            ->latest()
                            ->take(12)
                            ->get();

        // Last 7 days chart
        $chartLabels = [];
        $chartData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $chartLabels[] = $day->format('D');
            $chartData[]   = Booking::whereDate('booking_date', $day->toDateString())->count();
        }

        // Revenue by service
        $services      = Service::all();
        $revenueLabels = $services->pluck('name');
        $revenueData   = $services->pluck('price');

        return view('dashboard.admin-dashboard', compact(
            'todayBookings', 'pending', 'thisWeek', 'completed',
            'recentBookings', 'chartLabels', 'chartData',
            'revenueLabels', 'revenueData'
        ));
    }
}