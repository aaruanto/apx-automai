@extends('layouts.admin')

@section('title', 'Reports')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><span>Reports</span> & Analytics</h1>
            <ol class="breadcrumb">
                <li>Insights</li>
                <li class="active">Reports</li>
            </ol>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <select class="filter-select" id="reportPeriod" onchange="updatePeriod(this.value)">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 3 Months</option>
                <option value="365">This Year</option>
            </select>
            <button class="btn btn-ghost"><i class="fas fa-file-export"></i> Export PDF</button>
        </div>
    </div>

    <!-- KPI SUMMARY ROW -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
        @php
            $kpis = [
                ['label'=>'Total Bookings', 'value'=>$totalBookings ?? 0, 'icon'=>'fa-calendar-check', 'color'=>'var(--red)', 'bg'=>'var(--red-glow)', 'sub'=>'+'.($bookingsGrowth ?? 0).'% vs last period'],
                ['label'=>'Revenue',        'value'=>'₱'.number_format($totalRevenue ?? 0,0), 'icon'=>'fa-peso-sign', 'color'=>'#22c55e', 'bg'=>'rgba(34,197,94,.1)', 'sub'=>'+'.($revenueGrowth ?? 0).'% vs last period'],
                ['label'=>'Avg / Booking',  'value'=>'₱'.number_format($avgBookingValue ?? 0,0), 'icon'=>'fa-calculator', 'color'=>'#3b82f6', 'bg'=>'rgba(59,130,246,.1)', 'sub'=>'Average transaction value'],
                ['label'=>'Customers',      'value'=>$totalCustomers ?? 0, 'icon'=>'fa-users', 'color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,.1)', 'sub'=>($newCustomers ?? 0).' new this period'],
                ['label'=>'Cancellation %', 'value'=>($cancellationRate ?? 0).'%', 'icon'=>'fa-ban', 'color'=>'#6b7280', 'bg'=>'rgba(107,114,128,.1)', 'sub'=>($cancelledCount ?? 0).' cancelled bookings'],
            ];
        @endphp
        @foreach($kpis as $kpi)
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:18px;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-6px;right:-6px;font-size:3rem;opacity:.05;color:{{ $kpi['color'] }};"><i class="fas {{ $kpi['icon'] }}"></i></div>
            <div style="width:34px;height:34px;border-radius:8px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;color:{{ $kpi['color'] }};font-size:.85rem;margin-bottom:10px;">
                <i class="fas {{ $kpi['icon'] }}"></i>
            </div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.9rem;font-weight:800;line-height:1;color:var(--text);">{{ $kpi['value'] }}</div>
            <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:var(--text-muted);margin-top:4px;">{{ $kpi['label'] }}</div>
            <div style="font-size:.72rem;color:{{ $kpi['color'] }};margin-top:5px;">{{ $kpi['sub'] }}</div>
        </div>
        @endforeach
    </div>

    <!-- CHARTS ROW 1 -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">

        <!-- Revenue Over Time -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-chart-area"></i> Revenue Over Time</div>
                <span style="font-size:.75rem;color:var(--text-muted);">Selected period</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="65"></canvas>
            </div>
        </div>

        <!-- Booking Status Breakdown -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-chart-pie"></i> Booking Status</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:16px;">
                <canvas id="statusDonut" width="180" height="180" style="max-width:180px;"></canvas>
                <div style="width:100%;display:flex;flex-direction:column;gap:6px;">
                    @php
                        $statusBreakdown = [
                            ['label'=>'Confirmed',   'count'=>$statusCounts['confirmed'] ?? 0,   'color'=>'#22c55e'],
                            ['label'=>'Pending',     'count'=>$statusCounts['pending'] ?? 0,     'color'=>'#f59e0b'],
                            ['label'=>'In Progress', 'count'=>$statusCounts['in_progress'] ?? 0, 'color'=>'#3b82f6'],
                            ['label'=>'Cancelled',   'count'=>$statusCounts['cancelled'] ?? 0,   'color'=>'#E8192C'],
                        ];
                        $total = array_sum(array_column($statusBreakdown, 'count')) ?: 1;
                    @endphp
                    @foreach($statusBreakdown as $s)
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:8px;height:8px;border-radius:2px;background:{{ $s['color'] }};flex-shrink:0;"></div>
                        <span style="font-size:.78rem;flex:1;color:var(--text-muted);">{{ $s['label'] }}</span>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.9rem;">{{ $s['count'] }}</span>
                        <span style="font-size:.72rem;color:var(--text-muted);width:36px;text-align:right;">{{ round($s['count']/$total*100) }}%</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 2 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

        <!-- Top Services -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-ranking-star"></i> Top Services by Bookings</div>
            </div>
            <div class="card-body">
                @php
                    $topServices = $serviceStats ?? [];
                    $maxService  = $topServices ? (max(array_column($topServices,'count')) ?: 1) : 1;
                @endphp
                @forelse($topServices as $svc)
                <div style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:.82rem;font-weight:600;color:var(--text);">{{ $svc['name'] }}</span>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.9rem;color:var(--red);">{{ $svc['count'] }}</span>
                    </div>
                    <div style="height:6px;background:var(--surface-2);border-radius:3px;">
                        <div style="height:100%;width:{{ round($svc['count']/$maxService*100) }}%;background:var(--red);border-radius:3px;transition:width .3s;"></div>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:20px;color:var(--text-muted);">No data available.</div>
                @endforelse
            </div>
        </div>

        <!-- Revenue by Service -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-chart-bar"></i> Revenue by Service</div>
            </div>
            <div class="card-body">
                <canvas id="revenueByServiceChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- BOOKING SUMMARY TABLE -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-table-list"></i> Booking Summary</div>
            <button class="btn btn-ghost btn-sm"><i class="fas fa-file-export"></i> Export CSV</button>
        </div>
        <div class="table-wrap">
            <table class="apx-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Total Bookings</th>
                        <th>Confirmed</th>
                        <th>Cancelled</th>
                        <th>Revenue (₱)</th>
                        <th>Avg Value (₱)</th>
                        <th>Growth</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($bookingSummary ?? [] as $row)
                <tr>
                    <td class="primary-col">{{ $row['period'] }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td><span class="badge badge-confirmed">{{ $row['confirmed'] }}</span></td>
                    <td><span class="badge badge-cancelled">{{ $row['cancelled'] }}</span></td>
                    <td style="font-family:'Barlow Condensed',sans-serif;font-weight:700;color:var(--text);">₱{{ number_format($row['revenue'],2) }}</td>
                    <td>₱{{ number_format($row['avg_value'],2) }}</td>
                    <td>
                        @if(($row['growth'] ?? 0) >= 0)
                            <span style="color:#22c55e;font-size:.8rem;font-weight:600;"><i class="fas fa-arrow-up"></i> {{ $row['growth'] }}%</span>
                        @else
                            <span style="color:var(--red);font-size:.8rem;font-weight:600;"><i class="fas fa-arrow-down"></i> {{ abs($row['growth']) }}%</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">No report data yet.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- LOYALTY DISTRIBUTION -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-trophy"></i> Customer Loyalty Distribution</div>
            <a href="{{ route('admin.customers.loyalty') }}" style="font-size:.8rem;color:var(--red);text-decoration:none;font-weight:600;">
                View All <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
            </a>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                @php
                    $loyaltyDist = [
                        ['label'=>'Gold',   'count'=>$loyaltyCounts['gold']   ?? 0, 'color'=>'#F59E0B', 'bg'=>'rgba(245,158,11,.1)', 'icon'=>'fa-star',            'desc'=>'10+ bookings'],
                        ['label'=>'Silver', 'count'=>$loyaltyCounts['silver'] ?? 0, 'color'=>'#94A3B8', 'bg'=>'rgba(148,163,184,.1)', 'icon'=>'fa-star-half-stroke', 'desc'=>'5–9 bookings'],
                        ['label'=>'Bronze', 'count'=>$loyaltyCounts['bronze'] ?? 0, 'color'=>'#CD7C4F', 'bg'=>'rgba(205,124,79,.1)',  'icon'=>'fa-circle',          'desc'=>'1–4 bookings'],
                    ];
                @endphp
                @foreach($loyaltyDist as $ld)
                <div style="background:{{ $ld['bg'] }};border:1px solid rgba(0,0,0,.04);border-radius:10px;padding:18px;display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;color:{{ $ld['color'] }};font-size:1.1rem;">
                        <i class="fas {{ $ld['icon'] }}"></i>
                    </div>
                    <div>
                        <div style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:800;line-height:1;color:{{ $ld['color'] }};">{{ $ld['count'] }}</div>
                        <div style="font-weight:700;font-size:.85rem;color:{{ $ld['color'] }};">{{ $ld['label'] }}</div>
                        <div style="font-size:.72rem;color:var(--text-muted);">{{ $ld['desc'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
Chart.defaults.global.defaultFontColor = '#888';
Chart.defaults.global.defaultFontFamily = "'Barlow', sans-serif";

// Revenue Over Time
const rCtx = document.getElementById('revenueChart').getContext('2d');
const rGrad = rCtx.createLinearGradient(0,0,0,200);
rGrad.addColorStop(0,'rgba(34,197,94,.25)');
rGrad.addColorStop(1,'rgba(34,197,94,.0)');
new Chart(rCtx, {
    type:'line',
    data:{
        labels:{!! json_encode($revenueChartLabels ?? []) !!},
        datasets:[{
            label:'Revenue (₱)',
            data:{!! json_encode($revenueChartData ?? []) !!},
            borderColor:'#22c55e', backgroundColor:rGrad,
            borderWidth:2, pointBackgroundColor:'#22c55e',
            pointRadius:3, fill:true, lineTension:0.4,
        }]
    },
    options:{
        responsive:true, legend:{display:false},
        scales:{
            xAxes:[{gridLines:{color:'rgba(0,0,0,.04)'},ticks:{fontColor:'#888'}}],
            yAxes:[{gridLines:{color:'rgba(0,0,0,.04)'},ticks:{fontColor:'#888',beginAtZero:true,callback:v=>'₱'+v.toLocaleString()}}]
        }
    }
});

// Status Donut
new Chart(document.getElementById('statusDonut'), {
    type:'doughnut',
    data:{
        labels:['Confirmed','Pending','In Progress','Cancelled'],
        datasets:[{
            data:[
                {!! $statusCounts['confirmed']   ?? 0 !!},
                {!! $statusCounts['pending']     ?? 0 !!},
                {!! $statusCounts['in_progress'] ?? 0 !!},
                {!! $statusCounts['cancelled']   ?? 0 !!}
            ],
            backgroundColor:['#22c55e','#f59e0b','#3b82f6','#E8192C'],
            borderWidth:0, hoverOffset:4,
        }]
    },
    options:{
        responsive:false, cutoutPercentage:72,
        legend:{display:false},
        tooltips:{callbacks:{label:function(i,d){return d.labels[i.index]+': '+d.datasets[0].data[i.index];}}}
    }
});

// Revenue by Service Bar
new Chart(document.getElementById('revenueByServiceChart'), {
    type:'bar',
    data:{
        labels:{!! json_encode(array_column($serviceStats ?? [], 'name')) !!},
        datasets:[{
            label:'Revenue (₱)',
            data:{!! json_encode(array_column($serviceStats ?? [], 'revenue')) !!},
            backgroundColor:'#E8192C', borderRadius:4, borderWidth:0,
        }]
    },
    options:{
        responsive:true, legend:{display:false},
        scales:{
            xAxes:[{gridLines:{display:false},ticks:{fontColor:'#888'}}],
            yAxes:[{gridLines:{color:'rgba(0,0,0,.04)'},ticks:{fontColor:'#888',beginAtZero:true,callback:v=>'₱'+(v/1000).toFixed(0)+'k'}}]
        }
    }
});

function updatePeriod(days) {
    // In production, reload page with query param: window.location.href = '?period=' + days;
    console.log('Period changed to', days, 'days');
}
</script>
@endpush