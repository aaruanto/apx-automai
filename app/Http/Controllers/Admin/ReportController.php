<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $days = $request->get('period', 30);
        $from = Carbon::now()->subDays($days)->startOfDay();
        $to   = Carbon::now()->endOfDay();

        // ── KPI cards ──────────────────────────────────────────────────────
        $totalBookings    = Booking::whereBetween('booking_date', [$from, $to])->count();
        $totalRevenue     = Booking::whereBetween('booking_date', [$from, $to])
                                ->where('status', 'confirmed')
                                ->join('services', 'bookings.service_id', '=', 'services.id')
                                ->sum('services.price');
        $avgBookingValue  = $totalBookings > 0 ? round($totalRevenue / $totalBookings, 2) : 0;
        $totalCustomers   = User::where('role', 'customer')->count();
        $newCustomers     = User::where('role', 'customer')
                                ->whereBetween('created_at', [$from, $to])->count();
        $cancelledCount   = Booking::whereBetween('booking_date', [$from, $to])
                                ->where('status', 'cancelled')->count();
        $cancellationRate = $totalBookings > 0
                                ? round($cancelledCount / $totalBookings * 100, 1)
                                : 0;

        // ── Growth vs previous period ──────────────────────────────────────
        $prevFrom = Carbon::now()->subDays($days * 2)->startOfDay();
        $prevTo   = Carbon::now()->subDays($days)->endOfDay();

        $prevBookings = Booking::whereBetween('booking_date', [$prevFrom, $prevTo])->count();
        $prevRevenue  = Booking::whereBetween('booking_date', [$prevFrom, $prevTo])
                            ->where('status', 'confirmed')
                            ->join('services', 'bookings.service_id', '=', 'services.id')
                            ->sum('services.price');

        $bookingsGrowth = $prevBookings > 0
                            ? round(($totalBookings - $prevBookings) / $prevBookings * 100, 1)
                            : 0;
        $revenueGrowth  = $prevRevenue > 0
                            ? round(($totalRevenue - $prevRevenue) / $prevRevenue * 100, 1)
                            : 0;

        // ── Status breakdown ───────────────────────────────────────────────
        $statusCounts = [
            'confirmed'   => Booking::whereBetween('booking_date', [$from, $to])->where('status', 'confirmed')->count(),
            'pending'     => Booking::whereBetween('booking_date', [$from, $to])->where('status', 'pending')->count(),
            'in_progress' => Booking::whereBetween('booking_date', [$from, $to])->where('status', 'in_progress')->count(),
            'cancelled'   => $cancelledCount,
        ];

        // ── Revenue chart (daily points) ───────────────────────────────────
        $revenueChartLabels = [];
        $revenueChartData   = [];

        // Use weekly points for periods > 30 days to avoid too many labels
        $interval = $days <= 30 ? 'day' : 'week';
        $current  = $from->copy();

        while ($current <= $to) {
            $periodEnd = $interval === 'day'
                ? $current->copy()->endOfDay()
                : $current->copy()->endOfWeek();

            $revenueChartLabels[] = $interval === 'day'
                ? $current->format('M d')
                : $current->format('M d');

            $revenueChartData[] = (float) Booking::whereBetween('booking_date', [$current, $periodEnd])
                ->where('status', 'confirmed')
                ->join('services', 'bookings.service_id', '=', 'services.id')
                ->sum('services.price');

            $interval === 'day' ? $current->addDay() : $current->addWeek();
        }

        // ── Top services ───────────────────────────────────────────────────
        $serviceStats = Service::select(
                            'services.id',
                            'services.name',
                            \DB::raw('COUNT(bookings.id) as bookings_count'),
                            \DB::raw('SUM(CASE WHEN bookings.status = "confirmed" THEN services.price ELSE 0 END) as revenue')
                        )
                        ->leftJoin('bookings', function ($join) use ($from, $to) {
                            $join->on('bookings.service_id', '=', 'services.id')
                                 ->whereBetween('bookings.booking_date', [$from, $to]);
                        })
                        ->groupBy('services.id', 'services.name')
                        ->orderByDesc('bookings_count')
                        ->limit(6)
                        ->get()
                        ->map(fn($s) => [
                            'name'    => $s->name,
                            'count'   => (int) $s->bookings_count,
                            'revenue' => (float) $s->revenue,
                        ])
                        ->toArray();

        // ── Booking summary table (monthly rows) ───────────────────────────
        $bookingSummary = [];
        $cursor = Carbon::now()->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $mStart = $cursor->copy()->startOfMonth();
            $mEnd   = $cursor->copy()->endOfMonth();

            $mTotal     = Booking::whereBetween('booking_date', [$mStart, $mEnd])->count();
            $mConfirmed = Booking::whereBetween('booking_date', [$mStart, $mEnd])->where('status', 'confirmed')->count();
            $mCancelled = Booking::whereBetween('booking_date', [$mStart, $mEnd])->where('status', 'cancelled')->count();
            $mRevenue   = (float) Booking::whereBetween('booking_date', [$mStart, $mEnd])
                            ->where('status', 'confirmed')
                            ->join('services', 'bookings.service_id', '=', 'services.id')
                            ->sum('services.price');

            // Previous month for growth calc
            $pmStart  = $cursor->copy()->subMonth()->startOfMonth();
            $pmEnd    = $cursor->copy()->subMonth()->endOfMonth();
            $pmTotal  = Booking::whereBetween('booking_date', [$pmStart, $pmEnd])->count();
            $growth   = $pmTotal > 0 ? round(($mTotal - $pmTotal) / $pmTotal * 100, 1) : 0;

            $bookingSummary[] = [
                'period'    => $cursor->format('F Y'),
                'total'     => $mTotal,
                'confirmed' => $mConfirmed,
                'cancelled' => $mCancelled,
                'revenue'   => $mRevenue,
                'avg_value' => $mTotal > 0 ? round($mRevenue / $mTotal, 2) : 0,
                'growth'    => $growth,
            ];

            $cursor->subMonth();
        }

        // ── Loyalty distribution ───────────────────────────────────────────
        // TODO: update these queries once a loyalty column/table is added to your schema
        $loyaltyCounts = [
            'gold'   => \App\Models\Customer::where('tier', 'gold')->count(),
            'silver' => \App\Models\Customer::where('tier', 'silver')->count(),
            'bronze' => \App\Models\Customer::where('tier', 'bronze')->count(),
        ];

        return view('admin.reports.index', compact(
            'totalBookings', 'totalRevenue', 'avgBookingValue',
            'totalCustomers', 'newCustomers', 'cancellationRate', 'cancelledCount',
            'bookingsGrowth', 'revenueGrowth',
            'statusCounts',
            'revenueChartLabels', 'revenueChartData',
            'serviceStats',
            'bookingSummary',
            'loyaltyCounts'
        ));
    }
}