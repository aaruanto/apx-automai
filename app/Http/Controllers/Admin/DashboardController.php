<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /** Rows shown in the "recent bookings" table. */
    private const RECENT_LIMIT = 12;

    public function index()
    {
        $stats = $this->stats();

        $recentBookings = $this->recent();

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

        return view('dashboard.admin-dashboard', [
            'todayBookings'  => $stats['today'],
            'pending'        => $stats['pending'],
            'thisWeek'       => $stats['this_week'],
            'completed'      => $stats['completed'],
            'recentBookings' => $recentBookings,
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,
            'revenueLabels'  => $revenueLabels,
            'revenueData'    => $revenueData,
        ]);
    }

    /**
     * The same figures the page renders, as JSON, for the dashboard to poll.
     *
     * Read-only and deliberately narrow: just the stat cards and the recent
     * table. The charts are left out because they are rebuilt wholesale and
     * barely move within a shift, so re-sending them every 25 seconds would
     * cost more than it is worth.
     */
    public function live()
    {
        return response()->json([
            'stats'      => $this->stats(),
            'bookings'   => $this->recent()->map(fn (Booking $b) => [
                'reference' => $b->reference_number,
                'customer'  => $b->customer->name ?? 'N/A',
                'services'  => $b->service_list,
                'date'      => $b->booking_date,
                'staff'     => $b->staff->name ?? 'Unassigned',
                'status'    => $b->status,
                'label'     => ucfirst(str_replace('_', ' ', $b->status)),
            ])->values(),
            // Folded in here so the bell needs no poller of its own.
            'unread'     => auth()->user()->unreadNotifications()->count(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /** @return array<string,int> */
    private function stats(): array
    {
        return [
            'today'     => Booking::whereDate('booking_date', today())->count(),
            'pending'   => Booking::where('status', 'pending')->count(),
            'this_week' => Booking::whereBetween('booking_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count(),
            'completed' => Booking::where('status', 'completed')->count(),
        ];
    }

    private function recent()
    {
        // Eager-loaded: service_list reads the pivot, and the table shows the
        // customer and mechanic on every row.
        return Booking::with(['customer', 'service', 'staff', 'services'])
            ->latest()
            ->take(self::RECENT_LIMIT)
            ->get();
    }
}
