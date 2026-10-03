<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use App\Support\CsvExport;
use Carbon\Carbon;
use Throwable;

class ReportController extends Controller
{
    /** Ranges offered by the period selector; anything else is rejected. */
    private const PERIODS = [7, 30, 90, 365];

    private const DEFAULT_PERIOD = 30;

    public function index(Request $request)
    {
        $days = $this->period($request);

        // A report is read-only and non-essential: a failure here should not
        // hand an admin a raw 500 page. The exception still reaches the log via
        // report(), but the page renders with zeroed figures and a visible
        // explanation instead of disappearing.
        try {
            $data = $this->build($days);
        } catch (Throwable $e) {
            report($e);

            $data = $this->blank() + [
                'reportError' => 'This report could not be generated, so the figures below are not accurate. '
                                 .'Please try again, and if it keeps happening note the time it occurred.',
            ];
        }

        return view('admin.reports.index', $data + ['period' => $days]);
    }

    /**
     * ?period= is user input that reaches subDays() and the chart loop below,
     * so an absurd value (period=100000) would otherwise run tens of thousands
     * of queries and time the page out. Only the selector values are accepted.
     */
    private function period(Request $request): int
    {
        $requested = (int) $request->query('period', self::DEFAULT_PERIOD);

        return in_array($requested, self::PERIODS, true) ? $requested : self::DEFAULT_PERIOD;
    }

    private function build(int $days): array
    {
        $from = Carbon::now()->subDays($days)->startOfDay();
        $to   = Carbon::now()->endOfDay();

        // -- KPI cards -----------------------------------------------------
        $totalBookings    = Booking::whereBetween('booking_date', [$from, $to])->count();
        $totalRevenue     = $this->revenueBetween($from, $to);
        $avgBookingValue  = $totalBookings > 0 ? round($totalRevenue / $totalBookings, 2) : 0;
        $totalCustomers   = User::where('role', 'customer')->count();
        $newCustomers     = User::where('role', 'customer')
                                ->whereBetween('created_at', [$from, $to])->count();
        $cancelledCount   = Booking::whereBetween('booking_date', [$from, $to])
                                ->where('status', 'cancelled')->count();
        $cancellationRate = $totalBookings > 0
                                ? round($cancelledCount / $totalBookings * 100, 1)
                                : 0;

        // -- Growth vs previous period -------------------------------------
        $prevFrom = Carbon::now()->subDays($days * 2)->startOfDay();
        $prevTo   = Carbon::now()->subDays($days)->endOfDay();

        $prevBookings = Booking::whereBetween('booking_date', [$prevFrom, $prevTo])->count();
        $prevRevenue  = $this->revenueBetween($prevFrom, $prevTo);

        $bookingsGrowth = $prevBookings > 0
                            ? round(($totalBookings - $prevBookings) / $prevBookings * 100, 1)
                            : 0;
        $revenueGrowth  = $prevRevenue > 0
                            ? round(($totalRevenue - $prevRevenue) / $prevRevenue * 100, 1)
                            : 0;

        // -- Status breakdown ----------------------------------------------
        $statusCounts = [
            'confirmed'   => Booking::whereBetween('booking_date', [$from, $to])->where('status', 'confirmed')->count(),
            'pending'     => Booking::whereBetween('booking_date', [$from, $to])->where('status', 'pending')->count(),
            'in_progress' => Booking::whereBetween('booking_date', [$from, $to])->where('status', 'in_progress')->count(),
            'cancelled'   => $cancelledCount,
        ];

        // -- Revenue chart: daily points, weekly beyond 30 days ------------
        $revenueChartLabels = [];
        $revenueChartData   = [];

        $interval = $days <= 30 ? 'day' : 'week';
        $current  = $from->copy();

        while ($current <= $to) {
            $periodEnd = $interval === 'day'
                ? $current->copy()->endOfDay()
                : $current->copy()->endOfWeek();

            $revenueChartLabels[] = $current->format('M d');
            $revenueChartData[]   = $this->revenueBetween($current, $periodEnd);

            $interval === 'day' ? $current->addDay() : $current->addWeek();
        }

        // -- Top services --------------------------------------------------
        // The status was previously compared against a double-quoted
        // "confirmed" inside DB::raw. SQLite tolerates that as a string literal
        // when it does not resolve to a column, so it worked locally;
        // PostgreSQL reads double quotes as a quoted identifier and failed with
        //   column "confirmed" does not exist
        // which returned a 500 for this entire page on Render. Bound as a
        // parameter now, so no string literal is embedded in the SQL at all.
        $serviceStats = Service::select('services.id', 'services.name')
                        ->selectRaw('COUNT(bookings.id) as bookings_count')
                        ->selectRaw(
                            'COALESCE(SUM(CASE WHEN bookings.status = ? THEN services.price ELSE 0 END), 0) as revenue',
                            ['confirmed']
                        )
                        ->leftJoin('bookings', function ($join) use ($from, $to) {
                            $join->on('bookings.service_id', '=', 'services.id')
                                 ->whereBetween('bookings.booking_date', [$from, $to]);
                        })
                        ->groupBy('services.id', 'services.name')
                        ->orderByDesc('bookings_count')
                        ->limit(6)
                        ->get()
                        ->map(fn ($s) => [
                            'name'    => $s->name,
                            'count'   => (int) $s->bookings_count,
                            'revenue' => (float) $s->revenue,
                        ])
                        ->toArray();

        // -- Booking summary table: last 6 months --------------------------
        $bookingSummary = [];
        $cursor = Carbon::now()->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $mStart = $cursor->copy()->startOfMonth();
            $mEnd   = $cursor->copy()->endOfMonth();

            $mTotal     = Booking::whereBetween('booking_date', [$mStart, $mEnd])->count();
            $mConfirmed = Booking::whereBetween('booking_date', [$mStart, $mEnd])->where('status', 'confirmed')->count();
            $mCancelled = Booking::whereBetween('booking_date', [$mStart, $mEnd])->where('status', 'cancelled')->count();
            $mRevenue   = $this->revenueBetween($mStart, $mEnd);

            $pmStart = $cursor->copy()->subMonth()->startOfMonth();
            $pmEnd   = $cursor->copy()->subMonth()->endOfMonth();
            $pmTotal = Booking::whereBetween('booking_date', [$pmStart, $pmEnd])->count();
            $growth  = $pmTotal > 0 ? round(($mTotal - $pmTotal) / $pmTotal * 100, 1) : 0;

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

        return compact(
            'totalBookings', 'totalRevenue', 'avgBookingValue',
            'totalCustomers', 'newCustomers', 'cancellationRate', 'cancelledCount',
            'bookingsGrowth', 'revenueGrowth',
            'statusCounts',
            'revenueChartLabels', 'revenueChartData',
            'serviceStats',
            'bookingSummary'
        );
    }

    /** Confirmed-booking revenue in a window, summed from service prices. */
    private function revenueBetween(Carbon $from, Carbon $to): float
    {
        return (float) Booking::whereBetween('booking_date', [$from, $to])
            ->where('status', 'confirmed')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->sum('services.price');
    }

    /** The shape the view expects, with nothing in it, for the failure path. */
    private function blank(): array
    {
        return [
            'totalBookings'      => 0,
            'totalRevenue'       => 0,
            'avgBookingValue'    => 0,
            'totalCustomers'     => 0,
            'newCustomers'       => 0,
            'cancellationRate'   => 0,
            'cancelledCount'     => 0,
            'bookingsGrowth'     => 0,
            'revenueGrowth'      => 0,
            'statusCounts'       => ['confirmed' => 0, 'pending' => 0, 'in_progress' => 0, 'cancelled' => 0],
            'revenueChartLabels' => [],
            'revenueChartData'   => [],
            'serviceStats'       => [],
            'bookingSummary'     => [],
        ];
    }
    /**
     * CSV of the booking summary table, for the period currently selected.
     * Reuses build() so the file and the on-screen figures cannot drift apart.
     */
    public function export(Request $request)
    {
        $days = $this->period($request);
        $data = $this->build($days);

        $rows = array_map(fn ($r) => [
            $r["period"],
            $r["total"],
            $r["confirmed"],
            $r["cancelled"],
            number_format($r["revenue"], 2, ".", ""),
            number_format($r["avg_value"], 2, ".", ""),
            $r["growth"]."%",
        ], $data["bookingSummary"]);

        return CsvExport::stream(
            CsvExport::filename("booking-summary"),
            ["Period", "Total Bookings", "Confirmed", "Cancelled", "Revenue (PHP)", "Average Value (PHP)", "Growth"],
            $rows
        );
    }
}