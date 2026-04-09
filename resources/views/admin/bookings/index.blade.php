@extends('layouts.admin')

@section('title', 'All Bookings')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">All <span>Bookings</span></h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active">All Bookings</li>
            </ol>
        </div>
        <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Booking
        </a>
    </div>

    <!-- SUMMARY MINI-CARDS -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;">
        @php
            $mini = [
                ['label'=>'Confirmed',   'key'=>'confirmed',   'icon'=>'fa-circle-check',  'color'=>'var(--success)'],
                ['label'=>'Pending',     'key'=>'pending',     'icon'=>'fa-hourglass-half','color'=>'var(--warning)'],
                ['label'=>'In Progress', 'key'=>'in_progress', 'icon'=>'fa-spinner',       'color'=>'var(--info)'],
                ['label'=>'Cancelled',   'key'=>'cancelled',   'icon'=>'fa-ban',           'color'=>'var(--red)'],
            ];
        @endphp
        @foreach($mini as $m)
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;color:{{ $m['color'] }};font-size:.9rem;">
                <i class="fas {{ $m['icon'] }}"></i>
            </div>
            <div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.5rem;font-weight:800;line-height:1;">{{ $counts[$m['key']] ?? 0 }}</div>
                <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">{{ $m['label'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- TABLE CARD -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-table-list"></i> Booking Records</div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-ghost btn-sm"><i class="fas fa-file-export"></i> Export</button>
                <button class="btn btn-ghost btn-sm"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="filters-bar">
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.75rem;"></i>
                <input class="filter-input" type="text" id="searchInput" placeholder="Search by customer or booking ID..." style="padding-left:32px;" />
            </div>
            <select class="filter-select" id="filterStatus">
                <option value="">All Statuses</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select class="filter-select" id="filterService">
                <option value="">All Services</option>
                @foreach($services as $service)
                <option value="{{ $service->name }}">{{ $service->name }}</option>
                @endforeach
            </select>
            <input class="filter-select" type="date" id="filterDateFrom" title="Date from" />
            <input class="filter-select" type="date" id="filterDateTo"   title="Date to"   />
            <button class="btn btn-ghost btn-sm" onclick="clearFilters()"><i class="fas fa-xmark"></i> Clear</button>
        </div>

        <!-- TABLE -->
        <div class="table-wrap">
            <table class="apx-table" id="bookingsTable">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Vehicle / Plate</th>
                        <th>Service Type</th>
                        <th>Date &amp; Time</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @forelse($bookings as $b)
                <tr data-status="{{ $b->status }}" data-service="{{ $b->service->name ?? '' }}" data-search="{{ strtolower(($b->customer->name ?? '').' #BK-'.str_pad($b->id,4,'0',STR_PAD_LEFT)) }}">
                    <td>
                        <div class="primary-col">{{ $b->customer->name ?? 'N/A' }}</div>
                        <div style="font-size:.76rem;color:var(--text-muted);margin-top:2px;">#BK-{{ str_pad($b->id, 4, '0', STR_PAD_LEFT) }}</div>
                    </td>
                    <td>{{ $b->vehicle->plate_number ?? 'N/A' }}</td>
                    <td>{{ $b->service->name ?? 'N/A' }}</td>
                    <td style="white-space:nowrap;">{{ $b->booking_date }} {{ $b->booking_time }}</td>
                    <td>
                        @php
                            $statusMap = [
                                'confirmed'   => ['label'=>'Confirmed',  'class'=>'badge-confirmed'],
                                'pending'     => ['label'=>'Pending',    'class'=>'badge-pending'],
                                'in_progress' => ['label'=>'In Progress','class'=>'badge-inprogress'],
                                'cancelled'   => ['label'=>'Cancelled',  'class'=>'badge-cancelled'],
                            ];
                            $s = $statusMap[$b->status] ?? ['label'=>ucfirst($b->status),'class'=>'badge-pending'];
                        @endphp
                        <span class="badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <button class="btn btn-ghost btn-sm btn-icon" title="View" onclick="openModal('viewModal')"><i class="fas fa-eye"></i></button>
                            <a href="{{ route('admin.bookings.edit', ['id' => $b->id]) }}" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm btn-icon" title="Cancel"><i class="fas fa-ban"></i></button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No bookings found.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer-bar">
            <span id="rowCount">Showing {{ $bookings->count() }} bookings</span>
            <div style="display:flex;gap:6px;">
                <button class="btn btn-ghost btn-sm">&#8249; Prev</button>
                <button class="btn btn-primary btn-sm">1</button>
                <button class="btn btn-ghost btn-sm">Next &#8250;</button>
            </div>
        </div>
    </div>

@endsection

@section('modals')
<!-- VIEW BOOKING MODAL -->
<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-calendar-check" style="color:var(--red);margin-right:8px;"></i>Booking Details</div>
            <button class="modal-close" onclick="closeModal('viewModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Booking ID</div>
                    <div style="font-family:'Barlow Condensed',sans-serif;font-weight:700;" id="modal-booking-id">—</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Status</div>
                    <span class="badge badge-confirmed" id="modal-status">—</span>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Customer</div>
                    <div id="modal-customer">—</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Vehicle</div>
                    <div id="modal-vehicle">—</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Service</div>
                    <div id="modal-service">—</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Date &amp; Time</div>
                    <div id="modal-datetime">—</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const status  = document.getElementById('filterStatus').value;
    const service = document.getElementById('filterService').value;
    const rows    = document.querySelectorAll('#tableBody tr');
    let visible   = 0;
    rows.forEach(row => {
        const matchSearch  = !search  || (row.dataset.search || '').includes(search);
        const matchStatus  = !status  || row.dataset.status  === status;
        const matchService = !service || row.dataset.service === service;
        const show = matchSearch && matchStatus && matchService;
        row.style.display = show ? '' : 'none';
        if(show) visible++;
    });
    document.getElementById('rowCount').textContent = `Showing ${visible} bookings`;
}
function clearFilters() {
    ['searchInput','filterStatus','filterService','filterDateFrom','filterDateTo']
        .forEach(id => document.getElementById(id).value = '');
    applyFilters();
}
['searchInput','filterStatus','filterService','filterDateFrom','filterDateTo']
    .forEach(id => document.getElementById(id).addEventListener('input', applyFilters));
</script>
@endpush