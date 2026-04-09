@extends('layouts.admin')

@section('title', "Today's Schedule")

@push('styles')
<style>
.now-line {
    display:flex;align-items:center;gap:10px;margin-bottom:4px;
}
.now-line::before {
    content:'NOW';font-family:'Barlow Condensed',sans-serif;font-size:.65rem;
    font-weight:800;letter-spacing:.1em;color:var(--red);background:var(--red-glow);
    padding:1px 6px;border-radius:3px;
}
.now-line::after {
    content:'';flex:1;height:1px;background:var(--red);opacity:.5;
}
.status-dot { width:8px;height:8px;border-radius:50%;flex-shrink:0; }
.legend-dot { width:12px;height:12px;border-radius:2px;flex-shrink:0; }
.dot-in_progress { background: var(--info); }
.dot-confirmed   { background: var(--success); }
.dot-pending     { background: var(--warning); }
.dot-cancelled   { background: var(--red); }
</style>
@endpush

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Today's <span>Schedule</span></h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active">Today's Schedule</li>
            </ol>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:700;color:var(--text);">{{ now()->format('l, F j, Y') }}</span>
            <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary btn-sm" style="margin-left:8px;"><i class="fas fa-plus"></i> Add</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;">

        <!-- TIMELINE -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-clock"></i> Timeline</div>
                <span style="font-size:.8rem;color:var(--text-muted);">{{ $totalToday }} bookings today</span>
            </div>
            <div class="card-body" style="padding:20px;">
                @forelse($bookings as $booking)
                @php
                    $statusBadge = [
                        'confirmed'   => 'badge-confirmed',
                        'pending'     => 'badge-pending',
                        'in_progress' => 'badge-inprogress',
                        'cancelled'   => 'badge-cancelled',
                    ];
                    $statusLabel = [
                        'confirmed'   => 'Confirmed',
                        'pending'     => 'Pending',
                        'in_progress' => 'In Progress',
                        'cancelled'   => 'Cancelled',
                    ];
                    $badge = $statusBadge[$booking->status] ?? 'badge-pending';
                    $label = $statusLabel[$booking->status] ?? ucfirst($booking->status);
                @endphp
                <div class="event-block {{ $booking->status }}" style="margin-bottom:12px;">
                    <div>
                        <div class="event-title">{{ $booking->service->name ?? 'N/A' }}</div>
                        <div class="event-meta">
                            {{ $booking->customer->name ?? 'N/A' }} &middot;
                            <span style="font-family:'Barlow Condensed',sans-serif;font-weight:700;">
                                {{ $booking->vehicle->plate_number ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                        <span style="font-size:.75rem;color:var(--text-muted);">{{ $booking->booking_time }}</span>
                        <span class="badge {{ $badge }}">{{ $label }}</span>
                        <div style="display:flex;gap:4px;">
                            <button class="btn btn-ghost btn-sm btn-icon" title="View"><i class="fas fa-eye"></i></button>
                            <a href="{{ route('admin.bookings.edit', ['id' => $booking->id]) }}" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:40px;color:var(--text-muted);">
                    <i class="fas fa-calendar-day" style="font-size:2rem;margin-bottom:12px;display:block;opacity:.3;"></i>
                    No bookings scheduled for today.
                </div>
                @endforelse
            </div>
        </div>

        <!-- SIDEBAR -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- DAILY SUMMARY -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-chart-pie"></i> Today's Summary</div>
                </div>
                <div class="card-body" style="padding:16px;">
                    @php
                        $summaryItems = [
                            ['label'=>'In Progress','key'=>'in_progress','dotClass'=>'dot-in_progress'],
                            ['label'=>'Confirmed',  'key'=>'confirmed',  'dotClass'=>'dot-confirmed'],
                            ['label'=>'Pending',    'key'=>'pending',    'dotClass'=>'dot-pending'],
                            ['label'=>'Cancelled',  'key'=>'cancelled',  'dotClass'=>'dot-cancelled'],
                        ];
                    @endphp
                    @foreach($summaryItems as $si)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="status-dot {{ $si['dotClass'] }}"></div>
                            <span style="font-size:.83rem;">{{ $si['label'] }}</span>
                        </div>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1.1rem;">{{ $statuses[$si['key']] ?? 0 }}</span>
                    </div>
                    @endforeach
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;">
                        <span style="font-size:.83rem;font-weight:600;">Total</span>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.3rem;color:var(--red);">{{ $totalToday }}</span>
                    </div>
                </div>
            </div>

            <!-- QUICK ADD -->
            <div class="card" style="border-color:rgba(232,25,44,.2);">
                <div class="card-body" style="padding:16px;">
                    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:12px;">Need to add a walk-in booking?</p>
                    <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
            </div>

            <!-- LEGEND -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title" style="font-size:.8rem;"><i class="fas fa-circle-info"></i> Legend</div>
                </div>
                <div class="card-body" style="padding:12px 16px;">
                    @foreach($summaryItems as $l)
                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0;">
                        <div class="legend-dot {{ $l['dotClass'] }}"></div>
                        <span style="font-size:.8rem;color:var(--text-muted);">{{ $l['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

@endsection