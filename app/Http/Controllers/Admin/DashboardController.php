<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Rows shown in the "recent bookings" table. */
    private const RECENT_LIMIT = 12;

    /** Groupings the "Bookings Over Time" axis offers. */
    private const RANGES = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $stats   = $this->stats($filters);

        [$chartLabels, $chartData] = $this->bookingsOverTime($filters);
        [$revenueLabels, $revenueData] = $this->revenueByService($filters);

        return view('dashboard.admin-dashboard', [
            'totalBookings'  => $stats['total'],
            'pending'        => $stats['pending'],
            'completed'      => $stats['completed'],
            'recentBookings' => $this->recent($filters),
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,
            'revenueLabels'  => $revenueLabels,
            'revenueData'    => $revenueData,
            'filters'        => $filters,
            'services'       => Service::orderBy('name')->get(['id', 'name']),
            'customers'      => User::where('role', 'customer')->orderBy('name')->get(['id', 'name']),
            'ranges'         => self::RANGES,
        ]);
    }

    /**
     * The same figures the page renders, as JSON, for the dashboard to poll.
     *
     * Read-only and deliberately narrow: just the stat cards and the recent
     * table. The charts are left out because they are rebuilt wholesale and
     * barely move within a shift, so re-sending them every 25 seconds would
     * cost more than it is worth.
     *
     * It takes the same filters as the page, so polling cannot quietly replace
     * a filtered view with unfiltered numbers.
     */
    public function live(Request $request)
    {
        $filters = $this->filters($request);

        return response()->json([
            'stats'      => $this->stats($filters),
            'bookings'   => $this->recent($filters)->map(fn (Booking $b) => [
                'id'        => $b->id,
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

    // ── Filters ─────────────────────────────────────────────────────────

    /**
     * Read the dashboard filters, with defaults so the page works unfiltered.
     *
     * Everything is validated against a known set rather than trusted: these
     * values reach date parsing and query builders.
     */
    private function filters(Request $request): array
    {
        $range = in_array($request->query('range'), self::RANGES, true)
            ? $request->query('range')
            : 'daily';

        $to   = $this->date($request->query('to'))   ?? Carbon::today();
        $from = $this->date($request->query('from')) ?? $this->defaultFrom($range, $to);

        // A backwards range would silently return nothing.
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $status = $request->query('status');
        $status = in_array($status, ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'], true)
            ? $status
            : null;

        return [
            'range'    => $range,
            'from'     => $from,
            'to'       => $to,
            'status'   => $status,
            'service'  => $request->query('service') ? (int) $request->query('service') : null,
            'customer' => $request->query('customer') ? (int) $request->query('customer') : null,
        ];
    }

    private function date(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** A window that shows something useful for the chosen grouping. */
    private function defaultFrom(string $range, Carbon $to): Carbon
    {
        return match ($range) {
            'weekly'    => $to->copy()->subWeeks(11)->startOfWeek(),
            'monthly'   => $to->copy()->subMonths(11)->startOfMonth(),
            'quarterly' => $to->copy()->subMonths(23)->firstOfQuarter(),
            'yearly'    => $to->copy()->subYears(4)->startOfYear(),
            default     => $to->copy()->subDays(6),
        };
    }

    /** Every filter except status, which some callers apply differently. */
    private function base(array $filters)
    {
        return Booking::query()
            ->whereBetween('booking_date', [$filters['from']->toDateString(), $filters['to']->toDateString()])
            ->when($filters['customer'], fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['service'], fn ($q, $id) => $q->whereHas(
                'services',
                fn ($s) => $s->where('services.id', $id)
            ));
    }

    // ── Figures ─────────────────────────────────────────────────────────

    /**
     * The three figures the dashboard shows.
     *
     * Total is completed plus pending rather than every row, so the three
     * cards agree with each other: anything cancelled or still in progress is
     * in neither column and should not inflate the total.
     *
     * @return array<string,int>
     */
    private function stats(array $filters): array
    {
        // A status filter narrows the cards to that status; the other two then
        // read zero, which is honest rather than ignoring the filter.
        $completed = $filters['status'] && $filters['status'] !== 'completed'
            ? 0
            : (clone $this->base($filters))->where('status', 'completed')->count();

        $pending = $filters['status'] && $filters['status'] !== 'pending'
            ? 0
            : (clone $this->base($filters))->where('status', 'pending')->count();

        return [
            'completed' => $completed,
            'pending'   => $pending,
            'total'     => $completed + $pending,
        ];
    }

    private function recent(array $filters)
    {
        // Eager-loaded: service_list reads the pivot, and the table shows the
        // customer and mechanic on every row.
        return $this->base($filters)
            ->when($filters['status'], fn ($q, $s) => $q->where('status', $s))
            ->with(['customer', 'service', 'staff', 'services'])
            ->latest()
            ->take(self::RECENT_LIMIT)
            ->get();
    }

    /**
     * Booking counts grouped by the chosen interval.
     *
     * Counted in PHP from one query rather than with a database date function,
     * because the SQL for truncating a date to a week or quarter differs
     * between SQLite and PostgreSQL and this runs on both.
     *
     * @return array{0: list<string>, 1: list<int>}
     */
    private function bookingsOverTime(array $filters): array
    {
        $dates = $this->base($filters)
            ->when($filters['status'], fn ($q, $s) => $q->where('status', $s))
            ->pluck('booking_date');

        $buckets = [];
        foreach ($this->buckets($filters) as $key => $label) {
            $buckets[$key] = ['label' => $label, 'count' => 0];
        }

        foreach ($dates as $date) {
            $key = $this->bucketKey(Carbon::parse($date), $filters['range']);
            if (isset($buckets[$key])) {
                $buckets[$key]['count']++;
            }
        }

        return [
            array_values(array_column($buckets, 'label')),
            array_values(array_column($buckets, 'count')),
        ];
    }

    /** Every bucket in the window, so quiet periods still appear as zero. */
    private function buckets(array $filters): array
    {
        $out    = [];
        $cursor = $filters['from']->copy();
        $end    = $filters['to']->copy()->endOfDay();
        $guard  = 0;

        while ($cursor->lte($end) && $guard++ < 400) {
            $out[$this->bucketKey($cursor, $filters['range'])] = $this->bucketLabel($cursor, $filters['range']);

            $cursor = match ($filters['range']) {
                'weekly'    => $cursor->addWeek(),
                'monthly'   => $cursor->addMonth(),
                'quarterly' => $cursor->addMonths(3),
                'yearly'    => $cursor->addYear(),
                default     => $cursor->addDay(),
            };
        }

        return $out;
    }

    private function bucketKey(Carbon $date, string $range): string
    {
        return match ($range) {
            'weekly'    => $date->copy()->startOfWeek()->toDateString(),
            'monthly'   => $date->format('Y-m'),
            'quarterly' => $date->format('Y').'-Q'.$date->quarter,
            'yearly'    => $date->format('Y'),
            default     => $date->toDateString(),
        };
    }

    private function bucketLabel(Carbon $date, string $range): string
    {
        return match ($range) {
            'weekly'    => $date->copy()->startOfWeek()->format('M j'),
            'monthly'   => $date->format('M Y'),
            'quarterly' => 'Q'.$date->quarter.' '.$date->format('Y'),
            'yearly'    => $date->format('Y'),
            default     => $date->format('D j M'),
        };
    }

    /**
     * Revenue actually taken per service, from the booking price snapshots.
     *
     * This used to plot Service::pluck('price') — the price list, which is the
     * same numbers whether or not anything was ever booked.
     *
     * @return array{0: list<string>, 1: list<float>}
     */
    private function revenueByService(array $filters): array
    {
        $bookings = $this->base($filters)
            // Revenue means work that was actually completed, unless the admin
            // has asked to look at another status specifically.
            ->when($filters['status'], fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->where('status', 'completed'))
            ->with('services')
            ->get();

        $totals = [];
        foreach ($bookings as $booking) {
            foreach ($booking->services as $service) {
                $totals[$service->name] = ($totals[$service->name] ?? 0) + (float) $service->pivot->price;
            }
        }

        arsort($totals);
        $totals = array_slice($totals, 0, 8, true);

        return [array_keys($totals), array_values($totals)];
    }
}
