@extends('layouts.admin')

@section('title', 'Cancelled Bookings')

@php
    // TODO: Replace with DB query
    // $cancelled = Booking::where('status', 'cancelled')->orderByDesc('datetime')->get();
    $cancelled = [
        ['id'=>'#BK-0036','customer'=>'Lisa Tan',      'plate'=>'MNO 1234','service'=>'Engine Check',      'datetime'=>'2024-01-13 13:30','reason'=>'Customer request','cancelled_by'=>'Customer'],
        ['id'=>'#BK-0031','customer'=>'Kevin Sy',       'plate'=>'ABC 9999','service'=>'Full Car Wash',     'datetime'=>'2024-01-10 09:00','reason'=>'No show',         'cancelled_by'=>'Admin'],
        ['id'=>'#BK-0028','customer'=>'Rose Villanueva','plate'=>'XYZ 0001','service'=>'Oil Change',        'datetime'=>'2024-01-09 11:00','reason'=>'Vehicle issue',   'cancelled_by'=>'Customer'],
        ['id'=>'#BK-0025','customer'=>'Nico Bautista',  'plate'=>'DEF 7777','service'=>'Tire Rotation',    'datetime'=>'2024-01-08 14:00','reason'=>'Reschedule',      'cancelled_by'=>'Customer'],
        ['id'=>'#BK-0020','customer'=>'Grace Padilla',  'plate'=>'GHI 5555','service'=>'Paint Protection', 'datetime'=>'2024-01-05 10:30','reason'=>'No show',         'cancelled_by'=>'Admin'],
        ['id'=>'#BK-0018','customer'=>'Dante Ramos',    'plate'=>'JKL 3333','service'=>'Interior Detailing','datetime'=>'2024-01-04 09:00','reason'=>'Customer request','cancelled_by'=>'Customer'],
    ];
@endphp

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><span>Cancelled</span> Bookings</h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active">Cancelled</li>
            </ol>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> All Bookings</a>
    </div>

    <!-- ALERT BANNER -->
    <div style="background:rgba(232,25,44,.08);border:1px solid rgba(232,25,44,.2);border-radius:8px;padding:12px 18px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <i class="fas fa-circle-info" style="color:var(--red);font-size:1rem;flex-shrink:0;"></i>
        <span style="font-size:.85rem;color:var(--text-muted);">
            Cancelled bookings are kept for record-keeping. You can <strong style="color:var(--text);">rebook</strong> a cancelled booking or permanently delete it.
        </span>
    </div>

    <!-- FILTERS -->
    <div class="card" style="margin-bottom:20px;">
        <div class="filters-bar" style="border:none;">
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.75rem;"></i>
                <input class="filter-input" type="text" id="searchInput" placeholder="Search by customer or booking ID..." style="padding-left:32px;" />
            </div>
            <select class="filter-select" id="filterService">
                <option value="">All Services</option>
                <option>Full Car Wash</option>
                <option>Oil Change</option>
                <option>Paint Protection</option>
                <option>Interior Detailing</option>
                <option>Tire Rotation</option>
                <option>Engine Check</option>
            </select>
            <select class="filter-select" id="filterBy">
                <option value="">Cancelled By (All)</option>
                <option value="Customer">Customer</option>
                <option value="Admin">Admin</option>
            </select>
            <input class="filter-select" type="date" id="filterDateFrom" />
            <input class="filter-select" type="date" id="filterDateTo" />
            <button class="btn btn-ghost btn-sm" onclick="clearFilters()"><i class="fas fa-xmark"></i> Clear</button>
            <div class="spacer"></div>
            <span style="font-size:.8rem;color:var(--text-muted);" id="rowCount">{{ count($cancelled) }} records</span>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-ban"></i> Cancelled Records</div>
            <button class="btn btn-ghost btn-sm"><i class="fas fa-file-export"></i> Export</button>
        </div>
        <div class="table-wrap">
            <table class="apx-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Vehicle / Plate</th>
                        <th>Service Type</th>
                        <th>Scheduled For</th>
                        <th>Reason</th>
                        <th>Cancelled By</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @foreach($cancelled as $b)
                <tr data-search="{{ strtolower($b['customer'].' '.$b['id']) }}"
                    data-service="{{ $b['service'] }}"
                    data-cancelledby="{{ $b['cancelled_by'] }}">
                    <td>
                        <div class="primary-col">{{ $b['customer'] }}</div>
                        <div style="font-size:.76rem;color:var(--text-muted);margin-top:2px;">{{ $b['id'] }}</div>
                    </td>
                    <td>{{ $b['plate'] }}</td>
                    <td>{{ $b['service'] }}</td>
                    <td style="white-space:nowrap;">{{ $b['datetime'] }}</td>
                    <td>
                        <span style="font-size:.82rem;color:var(--text-muted);">{{ $b['reason'] }}</span>
                    </td>
                    <td>
                        @if($b['cancelled_by'] === 'Admin')
                        <span style="font-size:.78rem;font-weight:600;color:var(--red);">Admin</span>
                        @else
                        <span style="font-size:.78rem;color:var(--text-muted);">Customer</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <a href="{{ route('admin.bookings.rebook', ['id' => urlencode($b['id'])]) }}" class="btn btn-ghost btn-sm" title="Rebook" style="gap:5px;">
                                <i class="fas fa-rotate-right"></i> Rebook
                            </a>
                            <button class="btn btn-danger btn-sm btn-icon" title="Delete permanently" onclick="openModal('deleteModal')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer-bar">
            <span id="footerCount">{{ count($cancelled) }} cancelled bookings</span>
            <div style="display:flex;gap:6px;">
                <button class="btn btn-ghost btn-sm">&#8249; Prev</button>
                <button class="btn btn-primary btn-sm">1</button>
                <button class="btn btn-ghost btn-sm">Next &#8250;</button>
            </div>
        </div>
    </div>

@endsection

@section('modals')
<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Record?</div>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">
                This will <strong style="color:var(--red);">permanently delete</strong> this booking record. It cannot be recovered. Are you absolutely sure?
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteModal')">Go Back</button>
            <button class="btn btn-danger"><i class="fas fa-trash"></i> Delete Permanently</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const service = document.getElementById('filterService').value;
    const by      = document.getElementById('filterBy').value;
    const rows    = document.querySelectorAll('#tableBody tr');
    let visible   = 0;
    rows.forEach(row => {
        const ms = !search  || row.dataset.search.includes(search);
        const mv = !service || row.dataset.service === service;
        const mb = !by      || row.dataset.cancelledby === by;
        row.style.display = ms && mv && mb ? '' : 'none';
        if(ms && mv && mb) visible++;
    });
    document.getElementById('rowCount').textContent = visible + ' records';
    document.getElementById('footerCount').textContent = visible + ' cancelled bookings';
}
function clearFilters() {
    ['searchInput','filterService','filterBy','filterDateFrom','filterDateTo']
        .forEach(id => document.getElementById(id).value = '');
    applyFilters();
}
['searchInput','filterService','filterBy','filterDateFrom','filterDateTo']
    .forEach(id => document.getElementById(id).addEventListener('input', applyFilters));
</script>
@endpush