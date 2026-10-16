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
            <a href="{{ route('admin.customers.export') }}" class="btn btn-ghost btn-sm"><i class="fas fa-file-export"></i> Export CSV</a>
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
                        <th>Vehicles</th>
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
                <tr data-search="{{ strtolower($c->name.' '.$c->phone.' '.$c->plate_search) }}">
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
                        @if($c->vehicle_count)
                        <div style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.05rem;">
                            {{ $c->vehicle_count }}
                        </div>
                        <div style="font-size:.74rem;color:var(--text-muted);">
                            {{ \Illuminate\Support\Str::plural('vehicle', $c->vehicle_count) }}
                        </div>
                        @else
                        <div style="color:var(--text-muted);">—</div>
                        <div style="font-size:.74rem;color:var(--text-muted);">none registered</div>
                        @endif
                    </td>
                    <td>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.1rem;color:var(--text);">{{ $c->bookings_count ?? 0 }}</span>
                    </td>
                    <td style="font-size:.8rem;">{{ $c->last_visit ? \Carbon\Carbon::parse($c->last_visit)->format('M d, Y') : '—' }}</td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <button class="btn btn-ghost btn-sm btn-icon" title="View Details"
                                    onclick="viewCustomer(this)"
                                    data-name="{{ $c->name }}"
                                    data-cid="{{ $c->id_display ?? 'C-'.str_pad($c->id, 3, '0', STR_PAD_LEFT) }}"
                                    data-email="{{ $c->email }}"
                                    data-phone="{{ $c->phone }}"
                                    data-vehicles="{{ $c->vehicle_count }}"
                                    data-plates="{{ $c->user?->vehicles->pluck('plate_number')->implode(', ') }}"
                                    data-bookings="{{ $c->bookings_count ?? 0 }}"
                                    data-last="{{ $c->last_visit ? \Carbon\Carbon::parse($c->last_visit)->format('M d, Y') : 'Never' }}"
                            ><i class="fas fa-eye"></i></button>
                            @if ($loyaltyEnabled)
                            <button class="btn btn-ghost btn-sm btn-icon" title="Adjust Points"
                                    onclick="adjustPoints(this)"
                                    data-name="{{ $c->name }}"
                                    data-balance="{{ $c->points_balance }}"
                                    data-action="{{ route('admin.customers.points', $c->id) }}"
                            ><i class="fas fa-star"></i></button>
                            @endif
                            <button class="btn btn-danger btn-sm btn-icon" title="Delete"
                                    onclick="deleteCustomer(this)"
                                    data-name="{{ $c->name }}"
                                    data-action="{{ route('admin.customers.destroy', $c->id) }}"
                            ><i class="fas fa-trash"></i></button>
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

@push('styles')
<style>
    .vc-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:3px; }
    .vc-value { font-size:.9rem; color:var(--text); }
</style>
@endpush

@section('modals')

@if ($loyaltyEnabled)
{{-- ADJUST POINTS
     Points are money, so the reason is required and the change lands in the
     audit trail. Informational framing rather than a warning — adding points
     is a normal act of goodwill, not a destructive one. --}}
<div class="modal-overlay" id="pointsModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-star" style="color:var(--red);margin-right:8px;"></i> Adjust Points</div>
            <button class="modal-close" onclick="closeModal('pointsModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="pointsForm" method="POST" action="">
            @csrf
            <div class="modal-body">
                <p style="color:var(--text-muted);font-size:.86rem;line-height:1.6;margin:0 0 14px;">
                    <strong id="pointsCustomerName" style="color:var(--text);"></strong>
                    currently has <strong id="pointsCurrentBalance" style="color:var(--text);"></strong> points.
                </p>
                <div class="form-group">
                    <label class="form-label">Adjustment <span style="color:var(--red);">*</span></label>
                    <input class="form-control" type="number" name="points" id="pointsAmount"
                           placeholder="e.g. 250, or -100 to remove" required />
                    <div class="form-hint">Positive adds, negative removes. A balance can never go below zero.</div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Reason <span style="color:var(--red);">*</span></label>
                    <textarea class="form-control" name="reason" rows="2"
                              placeholder="e.g. goodwill after a delayed service" required></textarea>
                    <div class="form-hint">Recorded against your account in the audit trail.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('pointsModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Apply</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- VIEW CUSTOMER MODAL
     Both modals used to be static placeholders opened with no customer id, so
     every row showed the same empty panel. They are filled from the clicked
     row's data attributes now.

     Read-only by design: personal details belong to the customer and are not
     the shop's to edit (see CustomerController::update). --}}
<div class="modal-overlay" id="viewCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user" style="color:var(--red);margin-right:8px;"></i> Customer Details</div>
            <button class="modal-close" onclick="closeModal('viewCustomerModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 18px;">
                <div>
                    <div class="vc-label">Name</div>
                    <div class="vc-value" id="vc-name">—</div>
                </div>
                <div>
                    <div class="vc-label">Customer ID</div>
                    <div class="vc-value" id="vc-id">—</div>
                </div>
                <div>
                    <div class="vc-label">Email</div>
                    <div class="vc-value" id="vc-email">—</div>
                </div>
                <div>
                    <div class="vc-label">Contact Number</div>
                    <div class="vc-value" id="vc-phone">—</div>
                </div>
                <div>
                    <div class="vc-label">Vehicles</div>
                    <div class="vc-value" id="vc-vehicles">—</div>
                </div>
                <div>
                    <div class="vc-label">Total Bookings</div>
                    <div class="vc-value" id="vc-bookings">—</div>
                </div>
                <div style="grid-column:1/-1;">
                    <div class="vc-label">Last Visit</div>
                    <div class="vc-value" id="vc-last">—</div>
                </div>
                <div style="grid-column:1/-1;">
                    <div class="vc-label">Registered Plates</div>
                    <div class="vc-value" id="vc-plates">—</div>
                </div>
            </div>
            <p style="margin:16px 0 0;font-size:.76rem;color:var(--text-muted);">
                <i class="fas fa-lock" style="margin-right:5px;"></i>
                Personal details are read-only. The customer updates these from their own account.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('viewCustomerModal')">Close</button>
        </div>
    </div>
</div>

<!-- DELETE CUSTOMER MODAL -->
<div class="modal-overlay" id="deleteCustomerModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Customer?</div>
            <button class="modal-close" onclick="closeModal('deleteCustomerModal')"><i class="fas fa-xmark"></i></button>
        </div>
        {{-- A real form: the Delete button here was a bare <button> with no
             action, method or token, so it submitted nothing. --}}
        <form method="POST" id="deleteCustomerForm" action="">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;margin:0;">
                    Delete <strong id="dc-name" style="color:var(--text);">this customer</strong>?
                </p>
                <p style="color:var(--text-muted);font-size:.84rem;line-height:1.6;margin:10px 0 0;">
                    A customer with bookings on record cannot be deleted, so their
                    service history is never lost. If they have none, the account is
                    removed and can be restored by a developer if this was a mistake.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('deleteCustomerModal')">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function viewCustomer(btn) {
    const d = btn.dataset;
    const set = (id, v) => document.getElementById(id).textContent = (v && v !== '') ? v : '\u2014';
    set('vc-name', d.name);
    set('vc-id', d.cid);
    set('vc-email', d.email);
    set('vc-phone', d.phone);
    set('vc-vehicles', d.vehicles);
    set('vc-plates', d.plates);
    set('vc-bookings', d.bookings);
    set('vc-last', d.last);
    openModal('viewCustomerModal');
}

function adjustPoints(btn) {
    document.getElementById('pointsForm').action = btn.dataset.action;
    document.getElementById('pointsCustomerName').textContent = btn.dataset.name;
    document.getElementById('pointsCurrentBalance').textContent = Number(btn.dataset.balance || 0).toLocaleString();
    document.getElementById('pointsAmount').value = '';
    document.querySelector('#pointsForm [name="reason"]').value = '';
    openModal('pointsModal');
}

function deleteCustomer(btn) {
    // The form action is per-customer, so the modal deletes the row that
    // opened it rather than nothing at all.
    document.getElementById('dc-name').textContent = btn.dataset.name || 'this customer';
    document.getElementById('deleteCustomerForm').action = btn.dataset.action;
    openModal('deleteCustomerModal');
}
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