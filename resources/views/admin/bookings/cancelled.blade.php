@extends('layouts.admin')

@section('title', 'Cancelled Bookings')

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
                @foreach($services as $service)
                <option value="{{ $service->name }}">{{ $service->name }}</option>
                @endforeach
            </select>
            <input class="filter-select" type="date" id="filterDateFrom" />
            <input class="filter-select" type="date" id="filterDateTo" />
            <button class="btn btn-ghost btn-sm" onclick="clearFilters()"><i class="fas fa-xmark"></i> Clear</button>
            <span style="font-size:.8rem;color:var(--text-muted);" id="rowCount">{{ $cancelled->count() }} records</span>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @forelse($cancelled as $b)
                <tr id="row-{{ $b->id }}"
                    data-search="{{ strtolower(($b->customer->name ?? '').' '.$b->reference_number) }}"
                    data-service="{{ $b->service->name ?? '' }}">
                    <td>
                        <div class="primary-col">{{ $b->customer->name ?? 'N/A' }}</div>
                        <div style="font-size:.76rem;color:var(--text-muted);margin-top:2px;">{{ $b->reference_number }}</div>
                    </td>
                    <td>{{ $b->vehicle->plate_number ?? 'N/A' }}</td>
                    <td>{{ $b->service->name ?? 'N/A' }}</td>
                    <td style="white-space:nowrap;">{{ $b->booking_date }} {{ $b->booking_time }}</td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <a href="{{ route('admin.bookings.rebook', ['id' => $b->id]) }}" class="btn btn-ghost btn-sm" title="Rebook">
                                <i class="fas fa-rotate-right"></i> Rebook
                            </a>
                            <button class="btn btn-danger btn-sm btn-icon" title="Delete permanently"
                                onclick="openDeleteModal({{ $b->id }}, '{{ $b->reference_number }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No cancelled bookings.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer-bar">
            <span id="footerCount">{{ $cancelled->count() }} cancelled bookings</span>
        </div>
    </div>

@endsection

@section('modals')
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Record?</div>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">
                This will <strong style="color:var(--red);">permanently delete</strong> booking <strong id="deleteRefNo" style="color:var(--text);"></strong>. It cannot be recovered. Are you absolutely sure?
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteModal')">Go Back</button>
            <button class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash"></i> Delete Permanently</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let deleteTargetId = null;

function openDeleteModal(id, refNo) {
    deleteTargetId = id;
    document.getElementById('deleteRefNo').textContent = refNo;
    openModal('deleteModal');
}

function confirmDelete() {
    if (!deleteTargetId) return;
    fetch(`/admin/bookings/${deleteTargetId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeModal('deleteModal');
            const row = document.getElementById('row-' + deleteTargetId);
            if (row) row.remove();
            deleteTargetId = null;
        }
    })
    .catch(err => console.error('Delete failed:', err));
}

function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const service = document.getElementById('filterService').value;
    const rows    = document.querySelectorAll('#tableBody tr');
    let visible   = 0;
    rows.forEach(row => {
        const ms = !search  || (row.dataset.search || '').includes(search);
        const mv = !service || row.dataset.service === service;
        row.style.display = ms && mv ? '' : 'none';
        if(ms && mv) visible++;
    });
    document.getElementById('rowCount').textContent = visible + ' records';
    document.getElementById('footerCount').textContent = visible + ' cancelled bookings';
}
function clearFilters() {
    ['searchInput','filterService','filterDateFrom','filterDateTo']
        .forEach(id => document.getElementById(id).value = '');
    applyFilters();
}
['searchInput','filterService','filterDateFrom','filterDateTo']
    .forEach(id => document.getElementById(id).addEventListener('input', applyFilters));
</script>
@endpush