@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dash-f-label { display:block; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:4px; }

    /* The customer dashboard greets the person by name in a banner; this one
       showed only the product name, so the two halves of the app addressed
       the same user in completely different ways. These rules are copied from
       .welcome-banner in customer-dashboard.css, which the admin layout does
       not load, so the two banners match in card treatment, font, size and
       the red name. Both render the same x-greeting component, so the
       wording and the weekday can only ever come from one place. */
    .welcome-banner {
        background: linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);
        border: 1px solid var(--border);
        border-left: 3px solid var(--red);
        border-radius: 10px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .welcome-text h2 {
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 4px;
        color: var(--text);
    }
    .welcome-text h2 span { color: var(--red); }
    .welcome-text p { font-size: .83rem; color: var(--text-muted); margin: 0; }

    /* Both of these were inline repeat(3,1fr) and 2fr 1fr with no breakpoint,
       so on a phone three stat cards stayed in one row and pushed the page
       80px wider than the screen.

       minmax(0,1fr) rather than plain 1fr: a grid item is min-width:auto by
       default, so a track will refuse to shrink below its content and blow
       the grid out sideways — which is what happened here even before the
       breakpoint ran out. */
    .dash-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .dash-split {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    @media (max-width: 768px) {
        .dash-stat-grid,
        .dash-split { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><span>APX</span> AUTOMAI</h1>
            <ol class="breadcrumb">
                <li>Admin</li>
                <li class="active">Dashboard</li>
            </ol>
        </div>
        <div style="font-size:.83rem;color:var(--text-muted);">
            <i class="far fa-calendar" style="margin-right:6px;color:var(--red);"></i>
            {{ now()->format('l, F j, Y') }}
        </div>
    </div>

    {{-- The same banner the customer dashboard opens with, rendering the same
         x-greeting component, so both sides of the app greet the person the
         same way rather than one saying "Happy Tuesday, Gelo" and the other
         showing only the product name. --}}
    <div class="welcome-banner">
        <div class="welcome-text">
            <x-greeting :name="Auth::user()->name" />
            <p>
                {{ $pending }} booking{{ $pending !== 1 ? 's' : '' }} awaiting confirmation.
            </p>
        </div>
    </div>

    {{-- DASHBOARD FILTERS
         One GET form driving the cards, both charts and the recent list
         together, so everything on the page describes the same slice. The
         live poll reads the same query string, so refreshing cannot quietly
         replace a filtered view with unfiltered numbers. --}}
    <form method="GET" action="{{ route('admin.dashboard') }}" id="dashFilters"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:20px;padding:14px 16px;background:var(--surface);border:1px solid var(--border);border-radius:10px;">

        <div>
            <label class="dash-f-label">From</label>
            <input class="filter-select" type="date" name="from"
                   value="{{ $filters['from']->toDateString() }}" required />
        </div>
        <div>
            <label class="dash-f-label">To</label>
            <input class="filter-select" type="date" name="to"
                   value="{{ $filters['to']->toDateString() }}" required />
        </div>

        <div>
            <label class="dash-f-label">Status</label>
            <select class="filter-select" name="status">
                <option value="">All statuses</option>
                @foreach (['pending'=>'Pending','confirmed'=>'Confirmed','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v => $l)
                <option value="{{ $v }}" @selected($filters['status'] === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="dash-f-label">Service</label>
            <select class="filter-select" name="service">
                <option value="">All services</option>
                @foreach ($services as $svc)
                <option value="{{ $svc->id }}" @selected($filters['service'] === $svc->id)>{{ $svc->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="dash-f-label">Customer <span style="font-weight:400;text-transform:none;">(optional)</span></label>
            <select class="filter-select" name="customer">
                <option value="">All customers</option>
                @foreach ($customers as $cust)
                <option value="{{ $cust->id }}" @selected($filters['customer'] === $cust->id)>{{ $cust->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="dash-f-label">Group by</label>
            <select class="filter-select" name="range">
                @foreach ($ranges as $r)
                <option value="{{ $r }}" @selected($filters['range'] === $r)>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">Clear</a>
        </div>
    </form>

    {{-- STAT CARDS
         Three, as asked: Total (completed + pending), Completed, Pending.
         Today's Bookings and This Week are gone.

         Each View link carries ?status=, which the bookings list now
         preselects, so a card opens that list already filtered instead of
         showing everything. The Completed card used to link to
         status=confirmed, which is a different status entirely. --}}
    <div class="dash-stat-grid">
        @php
            $cards = [
                [
                    'label'  => 'Total Bookings',
                    'note'   => 'Completed + Pending',
                    'id'     => 'statTotal',
                    'value'  => $totalBookings,
                    'icon'   => 'fa-calendar-check',
                    'colour' => 'var(--red)',
                    'tint'   => 'var(--red-glow)',
                    'link'   => route('admin.bookings.index'),
                    'cta'    => 'View All',
                ],
                [
                    'label'  => 'Completed',
                    'note'   => 'Service finished',
                    'id'     => 'statCompleted',
                    'value'  => $completed,
                    'icon'   => 'fa-circle-check',
                    'colour' => '#22c55e',
                    'tint'   => 'rgba(34,197,94,.12)',
                    'link'   => route('admin.bookings.index', ['status' => 'completed']),
                    'cta'    => 'View Completed',
                ],
                [
                    'label'  => 'Pending',
                    'note'   => 'Awaiting confirmation',
                    'id'     => 'statPending',
                    'value'  => $pending,
                    'icon'   => 'fa-hourglass-half',
                    'colour' => '#f59e0b',
                    'tint'   => 'rgba(245,158,11,.12)',
                    'link'   => route('admin.bookings.index', ['status' => 'pending']),
                    'cta'    => 'Review Pending',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
        <a href="{{ $card['link'] }}" style="text-decoration:none;">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:transform .15s,box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="position:absolute;top:-8px;right:-8px;font-size:3.5rem;opacity:.05;color:{{ $card['colour'] }};">
                    <i class="fas {{ $card['icon'] }}"></i>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);">
                        {{ $card['label'] }}
                    </span>
                    <div style="width:32px;height:32px;border-radius:8px;background:{{ $card['tint'] }};display:flex;align-items:center;justify-content:center;color:{{ $card['colour'] }};font-size:.85rem;">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                </div>
                <div id="{{ $card['id'] }}" style="font-family:'Barlow Condensed',sans-serif;font-size:2.4rem;font-weight:800;line-height:1;color:var(--text);">
                    {{ $card['value'] }}
                </div>
                <div style="margin-top:4px;font-size:.72rem;color:var(--text-muted);">{{ $card['note'] }}</div>
                <div style="margin-top:8px;font-size:.75rem;color:{{ $card['colour'] }};font-weight:600;">
                    {{ $card['cta'] }} <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
                </div>
            </div>
        </a>
        @endforeach
    </div>

<!-- CHARTS -->
    <div class="dash-split">
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-chart-area"></i> Bookings Over Time</div>
                <span style="font-size:.75rem;color:var(--text-muted);background:var(--surface-2);padding:3px 8px;border-radius:4px;">{{ ucfirst($filters['range']) }} &middot; {{ $filters['from']->format('M j') }} &ndash; {{ $filters['to']->format('M j, Y') }}</span>
            </div>
            <div class="card-body">
                <canvas id="myAreaChart" width="100%" height="60"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-chart-bar"></i> Revenue by Service</div>
                <span style="font-size:.75rem;color:var(--text-muted);background:var(--surface-2);padding:3px 8px;border-radius:4px;">{{ $filters['status'] ? ucfirst(str_replace('_',' ',$filters['status'])) : 'Completed' }} &middot; {{ $filters['from']->format('M j') }} &ndash; {{ $filters['to']->format('M j, Y') }}</span>
            </div>
            <div class="card-body">
                <canvas id="myBarChart" width="100%" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- RECENT BOOKINGS TABLE -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-table-list"></i> Recent Bookings</div>
            <div style="display:flex;align-items:center;gap:12px;">
                {{-- Visible proof the page is keeping itself current, plus a
                     manual nudge for when someone wants it now. --}}
                <span id="liveUpdated" style="font-size:.72rem;color:var(--text-muted);"></span>
                <button type="button" id="liveRefreshBtn" class="btn btn-ghost btn-sm"
                        onclick="ApxLiveRefresh.refreshNow()" title="Refresh now">
                    <i class="fas fa-rotate"></i>
                </button>
                <a href="{{ route('admin.bookings.index') }}" style="font-size:.8rem;color:var(--red);text-decoration:none;font-weight:600;">
                    View All <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
                </a>
            </div>
        </div>
        <div class="table-wrap">
            <table class="apx-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Service Type</th>
                        <th>Date</th>
                        <th>Assigned Staff</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="recentBookingsBody">
                @forelse($recentBookings as $booking)
                {{-- Clicking a row opens that booking, not the full list. --}}
                <tr style="cursor:pointer;"
                    onclick="window.location='{{ route('admin.bookings.edit', $booking->id) }}'"
                    title="Open {{ $booking->reference_number }}">
                    <td style="font-family:'Barlow Condensed',sans-serif;font-weight:700;color:var(--text);">
                        {{ $booking->reference_number }}
                    </td>
                    <td class="primary-col">{{ $booking->customer->name ?? 'N/A' }}</td>
                    <td>{{ $booking->service_list }}</td>
                    <td>{{ $booking->booking_date }}</td>
                    <td>{{ $booking->staff->name ?? 'Unassigned' }}</td>
                    <td>
                        @php
                            $map = [
                                'confirmed'   => 'badge-confirmed',
                                'pending'     => 'badge-pending',
                                'in_progress' => 'badge-inprogress',
                                'cancelled'   => 'badge-cancelled',
                            ];
                            $cls = $map[$booking->status] ?? 'badge-pending';
                        @endphp
                        <span class="badge {{ $cls }}">{{ ucfirst(str_replace('_',' ',$booking->status)) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No bookings yet.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
Chart.defaults.global.defaultFontColor = '#888';
Chart.defaults.global.defaultFontFamily = "'Barlow', sans-serif";

// Area Chart
const areaCtx = document.getElementById('myAreaChart').getContext('2d');
const areaGrad = areaCtx.createLinearGradient(0, 0, 0, 200);
areaGrad.addColorStop(0, 'rgba(232,25,44,0.30)');
areaGrad.addColorStop(1, 'rgba(232,25,44,0.0)');
// Registered globally by the CDN script, so off everywhere unless asked for.
if (window.ChartDataLabels) { Chart.plugins.unregister(ChartDataLabels); }

new Chart(areaCtx, {
    plugins: window.ChartDataLabels ? [ChartDataLabels] : [],
    type: 'line',
    data: {
        labels: {!! json_encode($chartLabels) !!},
        datasets: [{
            label: 'Bookings',
            data: {!! json_encode($chartData) !!},
            borderColor: '#E8192C',
            backgroundColor: areaGrad,
            borderWidth: 2,
            pointBackgroundColor: '#E8192C',
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            lineTension: 0.4,
        }]
    },
    options: {
        responsive: true, legend: { display: false },
        plugins: {
            datalabels: {
                align: 'top', offset: 4,
                color: '#888', font: { size: 10, weight: 600 },
                // Hide zeroes so quiet days do not litter the line with 0s.
                display: ctx => ctx.dataset.data[ctx.dataIndex] > 0,
                formatter: v => v,
            }
        },
        scales: {
            xAxes: [{ gridLines: { color: 'rgba(0,0,0,0.05)' }, ticks: { fontColor: '#888' } }],
            // Bookings are whole things. Without stepSize the axis split a
            // range of 0-1 into 0.1, 0.2, ... The callback drops any
            // fractional tick Chart.js still generates at larger ranges.
            yAxes: [{
                gridLines: { color: 'rgba(0,0,0,0.05)' },
                ticks: {
                    fontColor: '#888',
                    beginAtZero: true,
                    stepSize: 1,
                    callback: v => (Number.isInteger(v) ? v : undefined),
                }
            }]
        }
    }
});

// Bar Chart
const barCtx = document.getElementById('myBarChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($revenueLabels) !!},
        datasets: [{
            label: 'Revenue (₱)',
            data: {!! json_encode($revenueData) !!},
            // Six colours were listed for sixteen services, so every bar past
            // the sixth fell through to Chart.js's default grey — the "black
            // column". One colour for all of them instead.
            backgroundColor: '#E8192C',
            hoverBackgroundColor: '#B5101E',
            borderRadius: 4, borderWidth: 0,
        }]
    },
    options: {
        responsive: true, legend: { display: false },
        scales: {
            xAxes: [{ gridLines: { display: false }, ticks: { fontColor: '#888' } }],
            // Full peso values: the old callback rounded to thousands, so
            // 1,000 and 1,050 both read "1k".
            yAxes: [{
                gridLines: { color: 'rgba(0,0,0,0.05)' },
                ticks: {
                    fontColor: '#888',
                    beginAtZero: true,
                    // Whole pesos only: with no revenue yet Chart.js falls back
                    // to a 0-1 range, which rendered as ₱0.9, ₱0.8 and so on.
                    stepSize: 1,
                    callback: v => (Number.isInteger(v) ? '₱' + Number(v).toLocaleString('en-PH') : undefined),
                }
            }]
        }
    }
});

// ── Keep the dashboard current without a reload ─────────────────────────
(function () {
    const STATUS_CLASS = {
        confirmed:   'badge-confirmed',
        pending:     'badge-pending',
        in_progress: 'badge-inprogress',
        completed:   'badge-completed',
        cancelled:   'badge-cancelled',
    };

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => (
            { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]
        ));
    }

    function setStat(id, value) {
        const el = document.getElementById(id);
        if (el && el.textContent !== String(value)) el.textContent = value;
    }

    ApxLiveRefresh.start({
        // Same query string as the page, so a refresh keeps the filter.
        url:    '{{ route('admin.dashboard.live') }}' + window.location.search,
        stamp:  '#liveUpdated',
        button: '#liveRefreshBtn',
        apply(data) {
            setStat('statTotal',     data.stats.total);
            setStat('statCompleted', data.stats.completed);
            setStat('statPending',   data.stats.pending);

            // One poll serves both: no second timer for the bell.
            if (window.ApxBell && typeof data.unread !== 'undefined') {
                ApxBell.setCount(data.unread);
            }

            const body = document.getElementById('recentBookingsBody');
            if (!body) return;

            if (!data.bookings.length) {
                body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No bookings yet.</td></tr>';
                return;
            }

            body.innerHTML = data.bookings.map(b => `
                <tr style="cursor:pointer;" onclick="window.location='/admin/bookings/${b.id}/edit'" title="Open ${esc(b.reference)}">
                    <td style="font-family:'Barlow Condensed',sans-serif;font-weight:700;color:var(--text);">${esc(b.reference)}</td>
                    <td class="primary-col">${esc(b.customer)}</td>
                    <td>${esc(b.services)}</td>
                    <td>${esc(b.date)}</td>
                    <td>${esc(b.staff)}</td>
                    <td><span class="badge ${STATUS_CLASS[b.status] || 'badge-pending'}">${esc(b.label)}</span></td>
                </tr>`).join('');
        }
    });
})();
</script>
@endpush