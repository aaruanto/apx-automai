@extends('layouts.admin')

@section('title', 'All Bookings')

@php
    $bookings = [
        ['id'=>'#BK-0041','customer'=>'Juan dela Cruz','plate'=>'ABC 1234','service'=>'Full Car Wash',    'datetime'=>'2024-01-15 09:00','status'=>'confirmed'],
        ['id'=>'#BK-0040','customer'=>'Maria Santos',  'plate'=>'XYZ 5678','service'=>'Oil Change',       'datetime'=>'2024-01-15 10:30','status'=>'inprogress'],
        ['id'=>'#BK-0039','customer'=>'Roberto Lim',   'plate'=>'DEF 9012','service'=>'Paint Protection', 'datetime'=>'2024-01-14 08:00','status'=>'confirmed'],
        ['id'=>'#BK-0038','customer'=>'Ana Reyes',      'plate'=>'GHI 3456','service'=>'Interior Detailing','datetime'=>'2024-01-14 14:00','status'=>'pending'],
        ['id'=>'#BK-0037','customer'=>'Carlo Mendoza',  'plate'=>'JKL 7890','service'=>'Tire Rotation',   'datetime'=>'2024-01-13 11:00','status'=>'confirmed'],
        ['id'=>'#BK-0036','customer'=>'Lisa Tan',       'plate'=>'MNO 1234','service'=>'Engine Check',    'datetime'=>'2024-01-13 13:30','status'=>'cancelled'],
        ['id'=>'#BK-0035','customer'=>'Paulo Garcia',   'plate'=>'PQR 5678','service'=>'Full Car Wash',   'datetime'=>'2024-01-12 09:30','status'=>'confirmed'],
        ['id'=>'#BK-0034','customer'=>'Diane Uy',       'plate'=>'STU 9012','service'=>'Oil Change',      'datetime'=>'2024-01-12 15:00','status'=>'confirmed'],
        ['id'=>'#BK-0033','customer'=>'Ben Cruz',       'plate'=>'VWX 3456','service'=>'Paint Protection','datetime'=>'2024-01-11 10:00','status'=>'pending'],
        ['id'=>'#BK-0032','customer'=>'Nina Flores',    'plate'=>'YZA 7890','service'=>'Interior Detailing','datetime'=>'2024-01-11 11:30','status'=>'confirmed'],
    ];

    $statusLabels = [
        'confirmed'  => ['label'=>'Confirmed',   'class'=>'badge-confirmed'],
        'pending'    => ['label'=>'Pending',      'class'=>'badge-pending'],
        'inprogress' => ['label'=>'In Progress',  'class'=>'badge-inprogress'],
        'cancelled'  => ['label'=>'Cancelled',    'class'=>'badge-cancelled'],
    ];
@endphp

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
            $counts = ['confirmed'=>0,'pending'=>0,'inprogress'=>0,'cancelled'=>0];
            foreach($bookings as $b) $counts[$b['status']]++;
            $mini = [
                ['label'=>'Confirmed',   'key'=>'confirmed',  'icon'=>'fa-circle-check',   'color'=>'var(--success)'],
                ['label'=>'Pending',     'key'=>'pending',    'icon'=>'fa-hourglass-half', 'color'=>'var(--warning)'],
                ['label'=>'In Progress', 'key'=>'inprogress', 'icon'=>'fa-spinner',        'color'=>'var(--info)'],
                ['label'=>'Cancelled',   'key'=>'cancelled',  'icon'=>'fa-ban',            'color'=>'var(--red)'],
            ];
        @endphp
        @foreach($mini as $m)
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 18px;display:flex;align-items:center;gap:14px;">
            <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;color:{{ $m['color'] }};font-size:.9rem;">
                <i class="fas {{ $m['icon'] }}"></i>
            </div>
            <div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.5rem;font-weight:800;line-height:1;">{{ $counts[$m['key']] }}</div>
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
                <option value="inprogress">In Progress</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select class="filter-select" id="filterService">
                <option value="">All Services</option>
                <option value="Full Car Wash">Full Car Wash</option>
                <option value="Oil Change">Oil Change</option>
                <option value="Paint Protection">Paint Protection</option>
                <option value="Interior Detailing">Interior Detailing</option>
                <option value="Tire Rotation">Tire Rotation</option>
                <option value="Engine Check">Engine Check</option>
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
                @foreach($bookings as $b)
                @php $s = $statusLabels[$b['status']]; @endphp
                <tr data-status="{{ $b['status'] }}" data-service="{{ $b['service'] }}" data-search="{{ strtolower($b['customer'].' '.$b['id']) }}">
                    <td>
                        <div class="primary-col">{{ $b['customer'] }}</div>
                        <div style="font-size:.76rem;color:var(--text-muted);margin-top:2px;">{{ $b['id'] }}</div>
                    </td>
                    <td>{{ $b['plate'] }}</td>
                    <td>{{ $b['service'] }}</td>
                    <td style="white-space:nowrap;">{{ $b['datetime'] }}</td>
                    <td><span class="badge {{ $s['class'] }}">{{ $s['label'] }}</span></td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <button class="btn btn-ghost btn-sm btn-icon" title="View" onclick="openModal('viewModal')"><i class="fas fa-eye"></i></button>
                            <a href="{{ route('admin.bookings.edit', ['id' => urlencode($b['id'])]) }}" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm btn-icon" title="Cancel"><i class="fas fa-ban"></i></button>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer-bar">
            <span id="rowCount">Showing {{ count($bookings) }} bookings</span>
            <div style="display:flex;gap:6px;">
                <button class="btn btn-ghost btn-sm">&#8249; Prev</button>
                <button class="btn btn-primary btn-sm">1</button>
                <button class="btn btn-ghost btn-sm">2</button>
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
                    <div style="font-family:'Barlow Condensed',sans-serif;font-weight:700;">#BK-0041</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Status</div>
                    <span class="badge badge-confirmed">Confirmed</span>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Customer</div>
                    <div>Juan dela Cruz</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Vehicle</div>
                    <div>ABC 1234</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Service</div>
                    <div>Full Car Wash</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Date &amp; Time</div>
                    <div>2024-01-15 09:00</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('viewModal')">Close</button>
            <a href="{{ route('admin.bookings.edit', ['id' => '%23BK-0041']) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Edit Booking</a>
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
        const matchSearch  = !search  || row.dataset.search.includes(search);
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