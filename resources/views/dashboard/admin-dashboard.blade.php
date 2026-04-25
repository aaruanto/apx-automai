@extends('layouts.admin')

@section('title', 'Dashboard')

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

    <!-- STAT CARDS -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
        <!-- Today's Bookings -->
        <a href="{{ route('admin.bookings.schedule') }}" style="text-decoration:none;">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:transform .15s,box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="position:absolute;top:-8px;right:-8px;font-size:3.5rem;opacity:.05;color:var(--red);"><i class="fas fa-calendar-day"></i></div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);">Today's Bookings</span>
                    <div style="width:32px;height:32px;border-radius:8px;background:var(--red-glow);display:flex;align-items:center;justify-content:center;color:var(--red);font-size:.85rem;">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.4rem;font-weight:800;line-height:1;color:var(--text);">{{ $todayBookings }}</div>
                <div style="margin-top:10px;font-size:.75rem;color:var(--red);font-weight:600;">
                    View Schedule <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
                </div>
            </div>
        </a>
        <!-- Pending -->
        <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" style="text-decoration:none;">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:transform .15s,box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="position:absolute;top:-8px;right:-8px;font-size:3.5rem;opacity:.05;color:#f59e0b;"><i class="fas fa-hourglass-half"></i></div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);">Pending</span>
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,.12);display:flex;align-items:center;justify-content:center;color:#f59e0b;font-size:.85rem;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.4rem;font-weight:800;line-height:1;color:var(--text);">{{ $pending }}</div>
                <div style="margin-top:10px;font-size:.75rem;color:#f59e0b;font-weight:600;">
                    Review Pending <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
                </div>
            </div>
        </a>
        <!-- This Week -->
        <a href="{{ route('admin.bookings.index') }}" style="text-decoration:none;">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:transform .15s,box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="position:absolute;top:-8px;right:-8px;font-size:3.5rem;opacity:.05;color:#22c55e;"><i class="fas fa-chart-bar"></i></div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);">This Week</span>
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(34,197,94,.12);display:flex;align-items:center;justify-content:center;color:#22c55e;font-size:.85rem;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.4rem;font-weight:800;line-height:1;color:var(--text);">{{ $thisWeek }}</div>
                <div style="margin-top:10px;font-size:.75rem;color:#22c55e;font-weight:600;">
                    All Bookings <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
                </div>
            </div>
        </a>
        <!-- Completed -->
        <a href="{{ route('admin.bookings.index', ['status' => 'confirmed']) }}" style="text-decoration:none;">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:transform .15s,box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="position:absolute;top:-8px;right:-8px;font-size:3.5rem;opacity:.05;color:#3b82f6;"><i class="fas fa-circle-check"></i></div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);">Completed</span>
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(59,130,246,.12);display:flex;align-items:center;justify-content:center;color:#3b82f6;font-size:.85rem;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                </div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.4rem;font-weight:800;line-height:1;color:var(--text);">{{ $completed }}</div>
                <div style="margin-top:10px;font-size:.75rem;color:#3b82f6;font-weight:600;">
                    View Completed <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- CHARTS -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-chart-area"></i> Bookings Over Time</div>
                <span style="font-size:.75rem;color:var(--text-muted);background:var(--surface-2);padding:3px 8px;border-radius:4px;">Last 7 days</span>
            </div>
            <div class="card-body">
                <canvas id="myAreaChart" width="100%" height="60"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-chart-bar"></i> Revenue by Service</div>
                <span style="font-size:.75rem;color:var(--text-muted);background:var(--surface-2);padding:3px 8px;border-radius:4px;">This month</span>
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
            <a href="{{ route('admin.bookings.index') }}" style="font-size:.8rem;color:var(--red);text-decoration:none;font-weight:600;">
                View All <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
            </a>
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
                <tbody>
                @forelse($recentBookings as $booking)
                <tr>
                    <td style="font-family:'Barlow Condensed',sans-serif;font-weight:700;color:var(--text);">
                        #BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="primary-col">{{ $booking->customer->name ?? 'N/A' }}</td>
                    <td>{{ $booking->service->name ?? 'N/A' }}</td>
                    <td>{{ $booking->booking_date }}</td>
                    <td>{{ $booking->employee->name ?? 'N/A' }}</td>
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
new Chart(areaCtx, {
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
        scales: {
            xAxes: [{ gridLines: { color: 'rgba(0,0,0,0.05)' }, ticks: { fontColor: '#888' } }],
            yAxes: [{ gridLines: { color: 'rgba(0,0,0,0.05)' }, ticks: { fontColor: '#888', beginAtZero: true } }]
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
            backgroundColor: ['#E8192C','#B5101E','#E8192C','#B5101E','#E8192C','#B5101E'],
            borderRadius: 4, borderWidth: 0,
        }]
    },
    options: {
        responsive: true, legend: { display: false },
        scales: {
            xAxes: [{ gridLines: { display: false }, ticks: { fontColor: '#888' } }],
            yAxes: [{ gridLines: { color: 'rgba(0,0,0,0.05)' }, ticks: { fontColor: '#888', beginAtZero: true, callback: v => '₱' + (v/1000).toFixed(0) + 'k' } }]
        }
    }
});
</script>
@endpush