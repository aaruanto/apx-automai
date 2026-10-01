@extends('layouts.admin')

@section('title', 'All Customers')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">All <span>Customers</span></h1>
            <ol class="breadcrumb">
                <li>Customers</li>
                <li class="active">All Customers</li>
            </ol>
        </div>
        <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Customer
        </a>
    </div>

    <!-- TABLE CARD -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-users"></i> Customer Records</div>
            <button class="btn btn-ghost btn-sm"><i class="fas fa-file-export"></i> Export</button>
        </div>

        <!-- FILTERS -->
        <div class="filters-bar">
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.75rem;"></i>
                <input class="filter-input" type="text" id="searchInput" placeholder="Search by name, phone, or plate..." style="padding-left:32px;" />
            </div>
            <button class="btn btn-ghost btn-sm" onclick="clearFilters()"><i class="fas fa-xmark"></i> Clear</button>
            <span style="font-size:.8rem;color:var(--text-muted);" id="rowCount">{{ $customers->count() }} records</span>
        </div>

        <!-- TABLE -->
        <div class="table-wrap">
            <table class="apx-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Vehicle</th>
                        <th>Bookings</th>
                        <th>Last Visit</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @forelse($customers as $c)
                @php
                    $initials = strtoupper(substr($c->name,0,1).substr(strrchr($c->name,' '),1,1));
                @endphp
                <tr data-search="{{ strtolower($c->name.' '.$c->phone.' '.($c->vehicle->plate_number ?? '')) }}">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);
                                        display:flex;align-items:center;justify-content:center;
                                        font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:.8rem;color:var(--text);flex-shrink:0;">
                                {{ $initials }}
                            </div>
                            <div>
                                <div class="primary-col">{{ $c->name }}</div>
                                <div style="font-size:.74rem;color:var(--text-muted);">{{ $c->id_display ?? 'C-'.str_pad($c->id,3,'0',STR_PAD_LEFT) }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:.83rem;">{{ $c->phone }}</div>
                        <div style="font-size:.74rem;color:var(--text-muted);">{{ $c->email }}</div>
                    </td>
                    <td>
                        <div style="font-family:'Barlow Condensed',sans-serif;font-weight:700;letter-spacing:.04em;">{{ $c->vehicle?->display_plate ?? '—' }}</div>
                        <div style="font-size:.74rem;color:var(--text-muted);">{{ $c->vehicle->model ?? '' }}</div>
                    </td>
                    <td>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.1rem;color:var(--text);">{{ $c->bookings_count ?? 0 }}</span>
                    </td>
                    <td style="font-size:.8rem;">{{ $c->last_visit ? \Carbon\Carbon::parse($c->last_visit)->format('M d, Y') : '—' }}</td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <button class="btn btn-ghost btn-sm btn-icon" title="View History" onclick="openModal('viewCustomerModal')"><i class="fas fa-eye"></i></button>
                            <a href="{{ route('admin.customers.edit', $c->id) }}" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="openModal('deleteCustomerModal')"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No customers found.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer-bar">
            <span id="footerCount">{{ $customers->count() }} customers</span>
        </div>
    </div>

@endsection

@section('modals')
<!-- VIEW CUSTOMER MODAL -->
<div class="modal-overlay" id="viewCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user" style="color:var(--red);margin-right:8px;"></i> Customer Details</div>
            <button class="modal-close" onclick="closeModal('viewCustomerModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.85rem;">Select a customer row to view their full details here.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('viewCustomerModal')">Close</button>
        </div>
    </div>
</div>

<!-- DELETE CUSTOMER MODAL -->
<div class="modal-overlay" id="deleteCustomerModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Customer?</div>
            <button class="modal-close" onclick="closeModal('deleteCustomerModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">
                This will <strong style="color:var(--red);">permanently delete</strong> this customer and all associated data. Are you sure?
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteCustomerModal')">Cancel</button>
            <button class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows   = document.querySelectorAll('#tableBody tr');
    let visible  = 0;
    rows.forEach(row => {
        const ms = !search || (row.dataset.search || '').includes(search);
        row.style.display = ms ? '' : 'none';
        if(ms) visible++;
    });
    document.getElementById('rowCount').textContent = visible + ' records';
    document.getElementById('footerCount').textContent = visible + ' customers';
}
function clearFilters() {
    document.getElementById('searchInput').value = '';
    applyFilters();
}
document.getElementById('searchInput').addEventListener('input', applyFilters);
</script>
@endpush