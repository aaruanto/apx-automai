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
    <div class="stat-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;">
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
                <a href="{{ route('admin.bookings.export') }}" class="btn btn-ghost btn-sm"><i class="fas fa-file-export"></i> Export CSV</a>
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
            <select class="filter-select" id="filterStaff">
                <option value="">All Mechanics</option>
                <option value="__unassigned">Unassigned</option>
                @foreach($staffMembers as $sm)
                <option value="{{ $sm->id }}">{{ $sm->name }}</option>
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
                        <th>Vehicle</th>
                        <th>Plate</th>
                        <th>Service Type</th>
                        <th>Date &amp; Time</th>
                        <th>Mechanic</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @forelse($bookings as $b)
                <tr data-staff="{{ $b->staff_id ?? '__unassigned' }}" data-status="{{ $b->status }}" data-service="{{ $b->services->pluck('name')->implode('|') }}" data-search="{{ strtolower(($b->customer->name ?? '').' '.$b->reference_number) }}">
                    <td>
                        <div class="primary-col">{{ $b->customer->name ?? 'N/A' }}</div>
                        <div style="font-size:.76rem;color:var(--text-muted);margin-top:2px;">{{ $b->reference_number }}</div>
                    </td>
                    <td style="white-space:nowrap;">{{ $b->vehicle?->display_name ?? '—' }}</td>
                    <td style="white-space:nowrap;font-family:'Barlow Condensed',sans-serif;font-weight:700;letter-spacing:.04em;">{{ $b->vehicle?->display_plate ?? 'Not provided' }}</td>
                    <td>{{ $b->service_list }}</td>
                    <td style="white-space:nowrap;">{{ $b->booking_date }} {{ $b->booking_time }}</td>
                    <td>
                        {{-- Assignable in place; opening the full edit form just
                             to put a mechanic on a job is more than it needs. --}}
                        <select class="filter-select staff-assign" style="min-width:150px;font-size:.78rem;"
                                data-booking="{{ $b->id }}" onchange="assignStaff(this)">
                            <option value="" {{ $b->staff_id ? '' : 'selected' }}>— Unassigned —</option>
                            @foreach($staffMembers as $sm)
                            <option value="{{ $sm->id }}" {{ $b->staff_id == $sm->id ? 'selected' : '' }}>{{ $sm->name }}</option>
                            @endforeach
                        </select>
                    </td>
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
                            <button class="btn btn-ghost btn-sm btn-icon" title="View"
                                onclick="openViewModal(this)"
                                data-id="{{ $b->reference_number }}"
                                data-status="{{ $b->status }}"
                                data-customer="{{ $b->customer->name ?? 'N/A' }}"
                                data-vehicle="{{ $b->vehicle?->display_plate ?? 'Not provided' }}"
                                data-model="{{ $b->vehicle?->display_name ?? '' }}"
                                data-service="{{ $b->service_list }}"
                                data-datetime="{{ $b->booking_date }} {{ $b->booking_time }}"
                                data-notes="{{ $b->notes ?? '—' }}"
                                data-cancel-reason="{{ $b->cancel_reason }}"
                                data-cancel-by="{{ $b->cancelledBy->name ?? ($b->status === 'cancelled' ? 'System' : '') }}"
                                data-cancel-at="{{ $b->cancelled_at?->format('M j, Y g:i A') }}"
                            ><i class="fas fa-eye"></i></button>
                            <a href="{{ route('admin.bookings.edit', ['id' => $b->id]) }}" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                            @if($b->canStart())
                            <button class="btn btn-ghost btn-sm btn-icon" title="Arrived / Start Service" onclick="markArrived({{ $b->id }}, this)"><i class="fas fa-person-walking-arrow-right"></i></button>
                            @endif
                            @if($b->canCancel())
                            <button class="btn btn-danger btn-sm btn-icon" title="Cancel" onclick="cancelBooking({{ $b->id }}, this)"><i class="fas fa-ban"></i></button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">No bookings found.</td>
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
                    <span class="badge badge-pending" id="modal-status">—</span>
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
                <div style="grid-column:1/-1;">
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Notes</div>
                    <div id="modal-notes">—</div>
                </div>
                {{-- Shown only for a cancelled booking. Everything above stays
                     populated: cancelling records the reason, it does not strip
                     the booking of its details. --}}
                <div style="grid-column:1/-1;display:none;" id="modal-cancel-block">
                    <div style="border:1px solid var(--red);border-left-width:3px;border-radius:7px;padding:10px 14px;background:var(--red-glow);">
                        <div style="font-size:.72rem;color:var(--red);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;font-weight:700;">Cancellation</div>
                        <div id="modal-cancel-reason">—</div>
                        <div style="font-size:.74rem;color:var(--text-muted);margin-top:3px;" id="modal-cancel-meta"></div>
                    </div>
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
function openViewModal(btn) {
    document.getElementById('modal-booking-id').textContent = btn.dataset.id;
    document.getElementById('modal-customer').textContent   = btn.dataset.customer;
    document.getElementById('modal-service').textContent    = btn.dataset.service;
    document.getElementById('modal-datetime').textContent   = btn.dataset.datetime;
    document.getElementById('modal-notes').textContent      = btn.dataset.notes;

    const plate   = btn.dataset.vehicle;
    const vehicle = btn.dataset.model;
    document.getElementById('modal-vehicle').textContent = vehicle ? vehicle + ' (' + plate + ')' : plate;

    const cancelBlock = document.getElementById('modal-cancel-block');
    if (btn.dataset.status === 'cancelled') {
        document.getElementById('modal-cancel-reason').textContent =
            btn.dataset.cancelReason || 'No reason recorded';
        document.getElementById('modal-cancel-meta').textContent =
            [btn.dataset.cancelBy, btn.dataset.cancelAt].filter(Boolean).join(' \u00b7 ');
        cancelBlock.style.display = '';
    } else {
        cancelBlock.style.display = 'none';
    }

    const statusEl = document.getElementById('modal-status');
    const statusMap = {
        confirmed:   ['Confirmed',   'badge-confirmed'],
        pending:     ['Pending',     'badge-pending'],
        in_progress: ['In Progress', 'badge-inprogress'],
        cancelled:   ['Cancelled',   'badge-cancelled'],
    };
    const s = statusMap[btn.dataset.status] ?? [btn.dataset.status, 'badge-pending'];
    statusEl.textContent = s[0];
    statusEl.className   = 'badge ' + s[1];

    openModal('viewModal');
}
 
function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const status  = document.getElementById('filterStatus').value;
    const service = document.getElementById('filterService').value;
    const staff   = document.getElementById('filterStaff').value;
    const rows    = document.querySelectorAll('#tableBody tr');
    let visible   = 0;
    rows.forEach(row => {
        const matchSearch  = !search  || (row.dataset.search || '').includes(search);
        const matchStatus  = !status  || row.dataset.status  === status;
        // A booking can carry several services; match if any of them is the one picked.
        const matchService = !service || (row.dataset.service || '').split('|').includes(service);
        const matchStaff   = !staff || row.dataset.staff === staff;
        const show = matchSearch && matchStatus && matchService && matchStaff;
        row.style.display = show ? '' : 'none';
        if(show) visible++;
    });
    document.getElementById('rowCount').textContent = `Showing ${visible} bookings`;
}
function clearFilters() {
    ['searchInput','filterStatus','filterService','filterStaff','filterDateFrom','filterDateTo']
        .forEach(id => document.getElementById(id).value = '');
    applyFilters();
}
function cancelBooking(id, btn) {
    // Reason prompt, validation and error reporting live in
    // assets/js/booking-actions.js. The previous version used a bare
    // confirm() and swallowed every failure into console.error.
    ApxBookingActions.cancelBooking(id, btn, data => {
        const row   = btn.closest('tr');
        const badge = row.querySelector('.badge');
        row.dataset.status = data.status;
        badge.className    = 'badge badge-cancelled';
        badge.textContent  = data.status_label;

        // Keep the view modal truthful without a reload.
        const view = row.querySelector('[data-status]');
        if (view) {
            view.dataset.status       = data.status;
            view.dataset.cancelReason = data.cancel_reason || '';
            view.dataset.cancelBy     = data.cancelled_by || '';
            view.dataset.cancelAt     = data.cancelled_at || '';
        }

        // Cancel and Start Service no longer apply to this row.
        btn.remove();
        const start = row.querySelector('button[title="Arrived / Start Service"]');
        if (start) start.remove();

        applyFilters();
    });
}


function assignStaff(select) {
    const bookingId = select.dataset.booking;
    const staffId   = select.value || null;
    // What the dropdown showed before this change, so a refusal or a failure
    // can put it back rather than leaving the UI claiming something untrue.
    const before    = select.dataset.initial || '';

    send(false);

    function send(force) {
        select.disabled = true;

        fetch(`/admin/bookings/${bookingId}/staff`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ staff_id: staffId, force: force })
        })
        .then(r => r.text().then(t => {
            let d = null; try { d = JSON.parse(t); } catch (e) {}
            return { ok: r.ok, status: r.status, data: d };
        }))
        .then(res => {
            select.disabled = false;

            // 409 means that mechanic already has an overlapping booking.
            // Warn and let the admin decide: double-booking is sometimes
            // deliberate, but it must never happen silently.
            if (res.status === 409 && res.data) {
                ApxAlertModal.show({
                    variant: 'confirm',
                    title: 'Mechanic already booked',
                    message: res.data.message,
                    confirmText: 'Assign anyway',
                    cancelText: 'Pick someone else',
                    onConfirm: () => send(true),
                    onCancel:  () => { select.value = before; }
                });
                return;
            }

            if (!res.ok || !res.data || !res.data.success) {
                select.value = before;
                ApxAlertModal.show({
                    variant: 'error',
                    title: 'Could not assign mechanic',
                    message: (res.data && res.data.message) || 'Something went wrong. Please try again.'
                });
                return;
            }

            select.dataset.initial = staffId || '';
            const row = select.closest('tr');
            if (row) row.dataset.staff = staffId || '__unassigned';
            applyFilters();
        })
        .catch(() => {
            select.disabled = false;
            select.value = before;
            ApxAlertModal.show({
                variant: 'error',
                title: 'Could not assign mechanic',
                message: 'Could not reach the server. Check your connection and try again.'
            });
        });
    }
}

function markArrived(id, btn) {
    // Confirmation, error reporting and the double-click guard live in
    // assets/js/booking-actions.js; this only re-renders the row on success.
    ApxBookingActions.markArrived(id, btn, data => {
        const row   = btn.closest('tr');
        const badge = row.querySelector('.badge');
        row.dataset.status = data.status;
        badge.className    = 'badge badge-inprogress';
        badge.textContent  = data.status_label;
        btn.remove();
        applyFilters();
    });
}
['searchInput','filterStatus','filterService','filterStaff','filterDateFrom','filterDateTo']
    .forEach(id => document.getElementById(id).addEventListener('input', applyFilters));
</script>
@endpush