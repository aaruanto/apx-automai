<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Divided by completed bookings, not all of them: revenue only counts
        // completed work, so dividing by every booking in the window would
        // report an average well below what any job was actually worth.
        $completedCount   = $this->completedBetween($from, $to);
        $avgBookingValue  = $completedCount > 0 ? round($totalRevenue / $completedCount, 2) : 0;
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
        // 'completed' was missing, so finished work appeared in no slice of
        // this chart at all — the same blind spot that made revenue vanish.
        $statusCounts = [
            'completed'   => $completedCount,
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
        // Through the pivot, so a service counts on every booking that
        // included it rather than only where it happened to be the legacy
        // single service_id — and at the price it was sold for, on completed
        // work, matching revenueBetween above.
        $serviceStats = Service::select('services.id', 'services.name')
                        ->selectRaw('COUNT(DISTINCT bookings.id) as bookings_count')
                        ->selectRaw(
                            'COALESCE(SUM(CASE WHEN bookings.status = ? THEN booking_service.price ELSE 0 END), 0) as revenue',
                            ['completed']
                        )
                        ->leftJoin('booking_service', 'booking_service.service_id', '=', 'services.id')
                        ->leftJoin('bookings', function ($join) use ($from, $to) {
                            $join->on('bookings.id', '=', 'booking_service.booking_id')
                                 ->whereBetween('bookings.booking_date', [$from, $to])
                                 ->whereNull('bookings.deleted_at');
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
    /**
     * What the shop actually earned between these dates.
     *
     * Three things were wrong here, each verified before this rewrite:
     *
     *  - It filtered on status 'confirmed', so a booking DROPPED OUT of
     *    revenue the moment it was marked completed. Finishing the work
     *    erased it: 1500 while confirmed, 0 once done.
     *  - It summed services.price, the live price list, not the snapshot the
     *    booking was sold at. Raising a price rewrote historical revenue —
     *    a booking sold at 1500 reported 9999 after a price change.
     *  - It joined the legacy single bookings.service_id, so a booking with
     *    two services counted only the first: 1000 reported for a 3000 job.
     *
     * Revenue is now the sum of the per-booking price snapshots on completed
     * work, which is the same basis the customer dashboard and the admin
     * dashboard already use. One definition, four screens.
     */
    private function revenueBetween(Carbon $from, Carbon $to): float
    {
        return (float) DB::table('booking_service')
            ->join('bookings', 'bookings.id', '=', 'booking_service.booking_id')
            ->whereBetween('bookings.booking_date', [$from, $to])
            ->where('bookings.status', 'completed')
            // A query builder join bypasses the SoftDeletes scope, so deleted
            // bookings would otherwise keep earning.
            ->whereNull('bookings.deleted_at')
            ->sum('booking_service.price');
    }

    /** Completed bookings in the window — the denominator for average value. */
    private function completedBetween(Carbon $from, Carbon $to): int
    {
        return Booking::whereBetween('booking_date', [$from, $to])
            ->where('status', 'completed')->count();
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