@extends('layouts.admin')

@php
    $isEdit    = isset($booking);
    $pageTitle = $isEdit ? 'Edit Booking' : 'New Booking';
@endphp

@section('title', $pageTitle)

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">{!! $isEdit ? '<span>Edit</span> Booking' : 'New <span>Booking</span>' !!}</h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active">{{ $pageTitle }}</li>
            </ol>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to All Bookings
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.bookings.update', $booking->id) : route('admin.bookings.store') }}">
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
                                <input class="form-control" type="text" name="customer_name"
                                       value="{{ $booking->user->name ?? '' }}"
                                       placeholder="e.g. Juan dela Cruz" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="tel" name="customer_phone"
                                       value="{{ $booking->user->phone ?? '' }}"
                                       placeholder="09XXXXXXXXX" required />
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Email Address</label>
                            <input class="form-control" type="email" name="customer_email"
                                   value="{{ $booking->user->email ?? '' }}"
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
                                <input class="form-control" type="text" name="plate"
                                       value="{{ $booking->vehicle->plate_number ?? '' }}"
                                       placeholder="e.g. ABC 1234" required
                                       style="text-transform:uppercase;letter-spacing:.08em;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Car Model</label>
                                <input class="form-control" type="text" name="car_model"
                                       value="{{ trim(($booking->vehicle->make ?? '').' '.($booking->vehicle->model ?? '').' '.($booking->vehicle->year ?? '')) }}"
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
                            <label class="form-label">Service Type <span style="color:var(--red)">*</span></label>
                            <select class="form-control" name="service_id" required>
                                <option value="">— Select a service —</option>
                                @foreach($services as $svc)
                                <option value="{{ $svc->id }}"
                                    {{ isset($booking) && $booking->service_id == $svc->id ? 'selected' : '' }}>
                                    {{ $svc->name }} — ₱{{ number_format($svc->price, 2) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assigned Staff</label>
                            <select class="form-control" name="staff_id">
                                <option value="">— Unassigned —</option>
                                @foreach($employees ?? [] as $emp)
                                <option value="{{ $emp->id }}"
                                    {{ isset($booking) && $booking->staff_id == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->specialty ?? 'General' }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="notes"
                                      placeholder="Special instructions, concerns, or requests...">{{ $booking->notes ?? '' }}</textarea>
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
                        <div class="form-group">
                            <label class="form-label">Preferred Date <span style="color:var(--red)">*</span></label>
                            <input class="form-control" type="date" name="booking_date"
                                   value="{{ $booking->booking_date ?? '' }}" required />
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Preferred Time <span style="color:var(--red)">*</span></label>
                            <select class="form-control" name="booking_time" required>
                                <option value="">— Select time slot —</option>
                                @php
                                    $times = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
                                              '13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'];
                                @endphp
                                @foreach($times as $t)
                                <option value="{{ $t }}"
                                    {{ isset($booking) && substr($booking->booking_time, 0, 5) === $t ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromFormat('H:i', $t)->format('g:i A') }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Operating hours: 8:00 AM – 5:00 PM</div>
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
                        <button type="submit" class="btn btn-primary"
                                style="width:100%;justify-content:center;padding:12px;">
                            <i class="fas fa-{{ $isEdit ? 'floppy-disk' : 'plus' }}"></i>
                            {{ $isEdit ? 'Save Changes' : 'Create Booking' }}
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
            <form method="POST" action="{{ route('admin.bookings.update', $booking->id) }}">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="cancelled" />
                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Yes, Cancel</button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
// Live service summary update
const serviceSelect  = document.querySelector('[name="service_id"]');
const summaryPrice   = document.getElementById('summaryPrice');
const summaryDuration = document.getElementById('summaryDuration');

const serviceData = {
    @foreach($services as $svc)
    {{ $svc->id }}: { price: '₱{{ number_format($svc->price, 2) }}', duration: '{{ $svc->duration ?? "—" }} min' },
    @endforeach
};

serviceSelect.addEventListener('change', function() {
    const data = serviceData[this.value];
    if (data) {
        summaryPrice.textContent    = data.price;
        summaryDuration.textContent = data.duration;
    } else {
        summaryPrice.textContent    = '—';
        summaryDuration.textContent = '—';
    }
});

// Trigger on load if editing
if (serviceSelect.value) serviceSelect.dispatchEvent(new Event('change'));
</script>
@endpush