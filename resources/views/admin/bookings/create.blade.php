@extends('layouts.admin')

@php
    $isEdit    = isset($booking);
    $isRebook  = !$isEdit && isset($prefill);
    $pageTitle = $isEdit ? 'Edit Booking' : ($isRebook ? 'Rebook' : 'New Booking');
    $src       = $isEdit ? $booking : ($isRebook ? $prefill : null);
@endphp

@section('title', $pageTitle)

@push('styles')
<link href="{{ asset('assets/css/availability-picker.css') }}" rel="stylesheet" />
@endpush

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">
                @if($isEdit) <span>Edit</span> Booking
                @elseif($isRebook) <span>Rebook</span>
                @else New <span>Booking</span>
                @endif
            </h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active">{{ $pageTitle }}</li>
            </ol>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to All Bookings
        </a>
    </div>

    @if($isRebook)
    <div style="background:rgba(255,180,0,.08);border:1px solid rgba(255,180,0,.2);border-radius:8px;padding:12px 18px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <i class="fas fa-rotate-right" style="color:var(--warning);font-size:1rem;flex-shrink:0;"></i>
        <span style="font-size:.85rem;color:var(--text-muted);">
            Rebooking from <strong style="color:var(--text);">{{ $prefill->reference_number }}</strong>. Review the details below and click <strong style="color:var(--text);">Create Booking</strong> to confirm.
        </span>
    </div>
    @endif

    <form method="POST" id="bookingForm" action="{{ $isEdit ? route('admin.bookings.update', $booking->id) : route('admin.bookings.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">

            <!-- LEFT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- CUSTOMER INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-user"></i> Customer Information</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="customer_name" id="adminCustomerName"
                                       value="{{ $src->user->name ?? '' }}"
                                       placeholder="e.g. Juan dela Cruz" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="tel" name="customer_phone" id="adminCustomerPhone"
                                       value="{{ $src->user->phone ?? '' }}"
                                       placeholder="09XXXXXXXXX" required />
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Email Address</label>
                            <input class="form-control" type="email" name="customer_email"
                                   value="{{ $src->user->email ?? '' }}"
                                   placeholder="optional" />
                        </div>
                    </div>
                </div>

                <!-- VEHICLE INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-car"></i> Vehicle Details</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Plate Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="plate" id="adminPlateInput"
                                       value="{{ $src->vehicle->plate_number ?? '' }}"
                                       placeholder="e.g. ABC 1234" required
                                       style="letter-spacing:.08em;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Car Model</label>
                                <input class="form-control" type="text" name="car_model"
                                       value="{{ $src->vehicle->model ?? '' }}"
                                       placeholder="e.g. Toyota Vios 2021" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SERVICE INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-wrench"></i> Service &amp; Notes</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Services <span style="color:var(--red)">*</span></label>
                            @php
                                // Services already on the booking, when editing or rebooking.
                                $picked = isset($src)
                                    ? $src->services->pluck('id')->all()
                                    : [];
                                if (! $picked && isset($src) && $src->service_id) {
                                    $picked = [$src->service_id];
                                }
                            @endphp
                            <div id="servicePicker" style="border:1px solid var(--border);border-radius:8px;max-height:260px;overflow-y:auto;padding:4px;">
                                @foreach($services->groupBy('category') as $category => $group)
                                <div style="padding:7px 10px 3px;font-size:.7rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);">
                                    {{ $category ?: 'Other' }}
                                </div>
                                    @foreach($group as $svc)
                                    <label style="display:flex;align-items:center;gap:9px;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:.85rem;"
                                           onmouseover="this.style.background='var(--surface-2,rgba(0,0,0,.03))'"
                                           onmouseout="this.style.background='none'">
                                        <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}"
                                               data-price="{{ $svc->price }}" data-duration="{{ $svc->duration }}"
                                               {{ in_array($svc->id, $picked) ? 'checked' : '' }}>
                                        <span style="flex:1;">{{ $svc->name }}</span>
                                        <span style="color:var(--text-muted);white-space:nowrap;">
                                            ₱{{ number_format($svc->price, 2) }} &middot; {{ $svc->duration }}m
                                        </span>
                                    </label>
                                    @endforeach
                                @endforeach
                            </div>
                            <div class="fv-error" id="serviceError">Please choose at least one service.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assigned Staff</label>
                            <select class="form-control" name="staff_id">
                                <option value="">— Unassigned —</option>
                                @foreach($employees ?? [] as $emp)
                                <option value="{{ $emp->id }}"
                                    {{ isset($src) && $src->staff_id == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->specialty ?? 'General' }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="notes"
                                      placeholder="Special instructions, concerns, or requests...">{{ $src->notes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- SCHEDULE -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-calendar"></i> Schedule</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Preferred Date &amp; Time <span style="color:var(--red)">*</span></label>
                            @php
                                $prefillDate = $isEdit ? ($booking->booking_date ?? '') : ($isRebook ? now()->addDay()->toDateString() : '');
                                $prefillTime = isset($src) ? substr($src->booking_time, 0, 5) : '';
                            @endphp
                            <input type="hidden" name="booking_date" id="adminBookingDate" value="{{ $prefillDate }}" required />
                            <input type="hidden" name="booking_time" id="adminBookingTime" value="{{ $prefillTime }}" required />
                            <x-availability-picker id="adminAvp" />
                            <div class="form-hint">Mon–Fri 9:00 AM – 9:00 PM &middot; Sat–Sun 9:00 AM – 12:00 PM</div>
                        </div>
                    </div>
                </div>

                <!-- STATUS (edit only) -->
                @if($isEdit)
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-tag"></i> Status</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Booking Status</label>
                            <select class="form-control" name="status">
                                @foreach(['pending'=>'Pending','confirmed'=>'Confirmed','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $val => $label)
                                <option value="{{ $val }}" {{ ($booking->status ?? '') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @endif

                <!-- BOOKING SUMMARY -->
                <div class="card" style="background:var(--surface-2);">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-receipt"></i> Summary</div>
                    </div>
                    <div class="card-body" style="padding:14px;">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:6px 0;border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted);">Service Fee</span>
                            <span id="summaryPrice" style="font-weight:600;">—</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:6px 0;">
                            <span style="color:var(--text-muted);">Duration</span>
                            <span id="summaryDuration" style="font-weight:600;">—</span>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary" id="bookingSubmitBtn"
                                style="width:100%;justify-content:center;padding:12px;">
                            <i class="fas fa-{{ $isEdit ? 'floppy-disk' : 'plus' }}"></i>
                            {{ $isEdit ? 'Save Changes' : ($isRebook ? 'Confirm Rebook' : 'Create Booking') }}
                        </button>
                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-ghost"
                           style="width:100%;justify-content:center;margin-top:8px;">
                            Cancel
                        </a>
                        @if($isEdit)
                        <hr style="border-color:var(--border);margin:12px 0;" />
                        <button type="button" class="btn btn-danger"
                                style="width:100%;justify-content:center;"
                                onclick="openModal('cancelModal')">
                            <i class="fas fa-ban"></i> Cancel This Booking
                        </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </form>

@endsection

@section('modals')
@if($isEdit)
<div class="modal-overlay" id="cancelModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);">
                <i class="fas fa-triangle-exclamation" style="margin-right:8px;"></i>Cancel Booking?
            </div>
            <button class="modal-close" onclick="closeModal('cancelModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;">
                This will mark the booking as <strong style="color:var(--red)">Cancelled</strong>. Are you sure?
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('cancelModal')">Go Back</button>
            <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Yes, Cancel</button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('assets/js/availability-picker.js') }}"></script>
<script>
const serviceBoxes    = () => Array.from(document.querySelectorAll('input[name="service_ids[]"]'));
const checkedServices = () => serviceBoxes().filter(b => b.checked);
const summaryPrice    = document.getElementById('summaryPrice');
const summaryDuration = document.getElementById('summaryDuration');

const serviceData = {
    @foreach($services as $svc)
    {{ $svc->id }}: { price: '₱{{ number_format($svc->price, 2) }}', duration: '{{ $svc->duration ?? "—" }} min' },
    @endforeach
};

// ── Plate mask + validation ─────────────────────────────────────────────
PlateMask.attach(document.getElementById('adminPlateInput'));
FormValidate.register(document.getElementById('adminCustomerName'), { rules: [FormValidate.rules.required('Full name is required.')] });
FormValidate.register(document.getElementById('adminCustomerPhone'), { rules: [FormValidate.rules.required('Phone number is required.'), FormValidate.rules.phonePH()] });
FormValidate.register(document.getElementById('adminPlateInput'), { rules: [FormValidate.rules.required('Plate number is required.'), FormValidate.rules.plate()] });
FormValidate.bindSubmit(document.getElementById('bookingForm'), document.getElementById('bookingSubmitBtn'));

// ── Availability picker ──────────────────────────────────────────────────
const adminPicker = new AvailabilityPicker({
    root: '#adminAvp',
    dateInput: document.getElementById('adminBookingDate'),
    timeInput: document.getElementById('adminBookingTime'),
    // Summed duration decides how long a slot is held, so the picker needs
    // every ticked service, not just one.
    getServiceIds: () => checkedServices().map(b => parseInt(b.value, 10)),
});

// Editing/rebooking an existing booking — adopt its current date/time so the
// calendar opens already showing (and highlighting) what's on file.
const prefillDate = document.getElementById('adminBookingDate').value;
const prefillTime = document.getElementById('adminBookingTime').value;
if (prefillDate && prefillTime) {
    const [py, pm] = prefillDate.split('-').map(Number);
    adminPicker.year = py;
    adminPicker.month = pm;
    adminPicker.selectedDate = prefillDate;
    adminPicker.selectedTime = prefillTime;
}

function refreshServiceSummary() {
    const picked = checkedServices();

    if (!picked.length) {
        summaryPrice.textContent    = '—';
        summaryDuration.textContent = '—';
    } else {
        const price    = picked.reduce((t, b) => t + parseFloat(b.dataset.price || 0), 0);
        const duration = picked.reduce((t, b) => t + parseInt(b.dataset.duration || 0, 10), 0);
        summaryPrice.textContent    = '₱' + price.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        summaryDuration.textContent = duration + ' min';
    }

    document.getElementById('serviceError').classList.toggle('show', picked.length === 0);
    adminPicker.refresh();
}

serviceBoxes().forEach(b => b.addEventListener('change', refreshServiceSummary));
refreshServiceSummary();

// The submit button is driven by FormValidate, which does not know about the
// checkbox group, so the service rule is enforced here as well.
document.getElementById('bookingForm').addEventListener('submit', function (e) {
    if (!checkedServices().length) {
        e.preventDefault();
        e.stopImmediatePropagation();
        document.getElementById('serviceError').classList.add('show');
        document.getElementById('servicePicker').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}, true);
</script>
@endpush