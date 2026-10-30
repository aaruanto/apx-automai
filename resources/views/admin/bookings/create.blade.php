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
<style>
/* Form beside the summary sidebar. The sidebar is a fixed 360px, so below
   roughly a tablet the pair cannot fit and the page scrolled sideways —
   279px of it at phone width. min-width:0 lets the form column shrink
   instead of being held open by its own content. */
.booking-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 20px;
    align-items: start;
}
.booking-form-grid > * { min-width: 0; }
@media (max-width: 900px) {
    .booking-form-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
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

    {{-- A schedule change the customer has not agreed to yet. The booking
         below still shows its ORIGINAL date and time, because that is what it
         still holds — the proposal is a separate record, not a status, so the
         slot is never quietly freed while the customer decides. --}}
    @if($isEdit && ($pendingReschedule ?? null))
    <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.22);border-radius:8px;padding:14px 18px;margin-bottom:24px;">
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <i class="fas fa-calendar-check" style="color:var(--info);font-size:1rem;flex-shrink:0;margin-top:3px;"></i>
            <div style="flex:1;font-size:.85rem;color:var(--text-muted);line-height:1.6;">
                <strong style="color:var(--text);">Waiting on the customer.</strong>
                You asked to move this booking from
                <strong style="color:var(--text);">{{ $pendingReschedule->originalLabel() }}</strong>
                to <strong style="color:var(--text);">{{ $pendingReschedule->proposedLabel() }}</strong>.
                <div style="margin-top:4px;">Reason given: {{ $pendingReschedule->reason }}</div>
                <div style="margin-top:6px;font-size:.78rem;">
                    The booking has <strong style="color:var(--text);">not moved</strong> and still holds its
                    original slot. Apply it once the customer has agreed — the new time is
                    re-checked then, because it was never reserved.
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0;">
                <form method="POST" action="{{ route('admin.bookings.reschedule.withdraw', $booking->id) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-ghost btn-sm" type="submit">Withdraw</button>
                </form>
                <form method="POST" action="{{ route('admin.bookings.reschedule.apply', $booking->id) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fas fa-check"></i> Customer agreed — move it
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($isRebook)
    <div style="background:rgba(255,180,0,.08);border:1px solid rgba(255,180,0,.2);border-radius:8px;padding:12px 18px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <i class="fas fa-rotate-right" style="color:var(--warning);font-size:1rem;flex-shrink:0;"></i>
        <span style="font-size:.85rem;color:var(--text-muted);">
            Rebooking from <strong style="color:var(--text);">{{ $prefill->reference_number }}</strong>. Review the details below and click <strong style="color:var(--text);">Create Booking</strong> to confirm.
        </span>
    </div>
    @endif

    {{-- The server validates this form and redirects back on failure, and
         nothing here ever rendered what it objected to. A rejected booking
         returned a blank form with no explanation, so the only way to find
         out what was wrong was to guess. The client-side checks above should
         now catch the common cases first; this is the backstop for everything
         they do not, such as a plate already registered to someone else. --}}
    <x-form-errors title="This booking could not be saved." />

    <form method="POST" id="bookingForm" action="{{ $isEdit ? route('admin.bookings.update', $booking->id) : route('admin.bookings.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="booking-form-grid">

            <!-- LEFT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- CUSTOMER INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-user"></i> Customer Information</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            {{-- Read-only on edit. These were editable here but
                                 update() never read them, so typing a new name
                                 and saving appeared to work and changed
                                 nothing. Customers own their own details. --}}
                            <div class="form-group">
                                <label class="form-label">
                                    Full Name @unless ($isEdit)<span style="color:var(--red)">*</span>@endunless
                                </label>
                                <input class="form-control" type="text"
                                       @if ($isEdit) disabled @else name="customer_name" id="adminCustomerName" required @endif
                                       value="{{ old('customer_name', $src->user->name ?? '') }}"
                                       placeholder="e.g. Juan dela Cruz"
                                       style="{{ $isEdit ? 'opacity:.65;cursor:not-allowed;' : '' }}" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    Phone Number @unless ($isEdit)<span style="color:var(--red)">*</span>@endunless
                                </label>
                                <input class="form-control" type="tel"
                                       @if ($isEdit) disabled @else name="customer_phone" id="adminCustomerPhone" required @endif
                                       value="{{ old('customer_phone', $src->user->phone ?? '') }}"
                                       placeholder="09XXXXXXXXX"
                                       style="{{ $isEdit ? 'opacity:.65;cursor:not-allowed;' : '' }}" />
                                @if ($isEdit)
                                <div class="form-hint">The customer changes their own details from their account.</div>
                                @endif
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Email Address</label>
                            <input class="form-control" type="email" name="customer_email"
                                   value="{{ old('customer_email', $src->user->email ?? '') }}"
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
                        {{-- This showed the plate and one field labelled "Car
                             Model" reading vehicles.model, which is blank for
                             every vehicle a customer added from their own
                             dashboard — that form records the whole thing in
                             make. So the mechanic saw a plate and nothing
                             else. Make, model, year and whether it is a car or
                             a motorcycle are each shown, from their own
                             column. --}}
                        <div class="form-row cols-2">
                            {{-- On edit the vehicle is shown, not edited. This
                                 form used to write the plate straight onto the
                                 Vehicle row with no validation, which changed
                                 it on every other booking for that car too. A
                                 plate belongs to the vehicle; the customer
                                 edits it from their own garage. --}}
                            <div class="form-group">
                                <label class="form-label">
                                    Plate Number
                                    @unless ($isEdit)<span style="color:var(--red)">*</span>@endunless
                                </label>
                                <input class="form-control" type="text"
                                       @if ($isEdit) disabled @else name="plate" id="adminPlateInput" required @endif
                                       value="{{ old('plate', $src->vehicle->plate_number ?? '') }}"
                                       @unless ($isEdit) placeholder="ABC 1234, 123 ABC or AB 12345" @endunless
                                       style="letter-spacing:.08em;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;{{ $isEdit ? 'opacity:.65;cursor:not-allowed;' : '' }}" />
                                @if ($isEdit)
                                <div class="form-hint">The customer changes this from their own vehicles.</div>
                                @endif
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vehicle Type</label>
                                @if ($isEdit)
                                    <input class="form-control" type="text" disabled
                                           value="{{ ($src->vehicle?->vehicle_type ?? 'car') === 'motorcycle' ? 'Motorcycle' : 'Car' }}"
                                           style="opacity:.65;cursor:not-allowed;" />
                                @else
                                    {{-- Recorded rather than assumed: the column
                                         defaults to "car", so every motorcycle
                                         a walk-in brought in was filed as one. --}}
                                    <select class="form-control" name="car_type">
                                        <option value="car" {{ old('car_type', 'car') === 'car' ? 'selected' : '' }}>Car</option>
                                        <option value="motorcycle" {{ old('car_type') === 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                    </select>
                                @endif
                            </div>
                        </div>
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Make</label>
                                <input class="form-control" type="text" list="adminVehicleMakes"
                                       @if ($isEdit) disabled @else name="car_make" @endif
                                       value="{{ old('car_make', $src->vehicle->make ?? '') }}"
                                       @unless ($isEdit) placeholder="e.g. Toyota" @endunless
                                       style="{{ $isEdit ? 'opacity:.65;cursor:not-allowed;' : '' }}" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Model</label>
                                <input class="form-control" type="text" list="adminVehicleModels"
                                       @if ($isEdit) disabled @else name="car_model" @endif
                                       value="{{ old('car_model', $src->vehicle->model ?? '') }}"
                                       @unless ($isEdit) placeholder="e.g. Vios" @endunless
                                       style="{{ $isEdit ? 'opacity:.65;cursor:not-allowed;' : '' }}" />
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;max-width:50%;">
                            <label class="form-label">Year</label>
                            <input class="form-control" type="number" min="1990" max="{{ date('Y') + 1 }}"
                                   @if ($isEdit) disabled @else name="car_year" @endif
                                   value="{{ old('car_year', $src->vehicle->year ?? '') }}"
                                   @unless ($isEdit) placeholder="e.g. 2021" @endunless
                                   style="{{ $isEdit ? 'opacity:.65;cursor:not-allowed;' : '' }}" />
                            @if ($isEdit)
                            <div class="form-hint">The customer changes these from their own vehicles.</div>
                            @endif
                        </div>

                        {{-- Suggestions, not a constraint: a walk-in may arrive
                             in anything, and admin is the path that has to
                             accept it. The list is config/vehicles.php, the one
                             the booking modal uses. --}}
                        @unless ($isEdit)
                        <x-vehicle-datalists makes="adminVehicleMakes" models="adminVehicleModels" />
                        @endunless
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

                                // A refused save redirects back with the input,
                                // so the services the admin had ticked are
                                // restored rather than cleared along with
                                // everything else on the form.
                                $picked = array_map('intval', (array) old('service_ids', $picked));
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
                            <label class="form-label">Assigned Mechanic</label>
                            <select class="form-control" name="mechanic_id">
                                <option value="">— Unassigned —</option>
                                @foreach($mechanics ?? [] as $emp)
                                <option value="{{ $emp->id }}"
                                    {{ (string) old('mechanic_id', (isset($src) ? $src->mechanic_id : '')) === (string) $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->specialty ?? 'General' }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="notes"
                                      placeholder="Special instructions, concerns, or requests...">{{ old('notes', $src->notes ?? '') }}</textarea>
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

                                // Same reason as the services above: the picker
                                // reads these two hidden fields on load, so
                                // restoring them puts the admin back on the
                                // slot they had chosen.
                                $prefillDate = old('booking_date', $prefillDate);
                                $prefillTime = old('booking_time', $prefillTime);
                            @endphp
                            <input type="hidden" name="booking_date" id="adminBookingDate" value="{{ $prefillDate }}" required />
                            <input type="hidden" name="booking_time" id="adminBookingTime" value="{{ $prefillTime }}" required />
                            {{-- Names the field to go to: it sits in SERVICE &
                                 NOTES, roughly 500px below this panel, so an
                                 admin reading an empty calendar has no reason
                                 to connect the two. --}}
                            {{-- A plain & here: the prop is a PHP string that
                                 {{ }} escapes on output, so an HTML entity
                                 would be escaped a second time and show up
                                 literally as "&amp;". --}}
                            <x-availability-picker id="adminAvp"
                                empty-hint="Choose a service in SERVICE & NOTES below — how long the work takes decides which slots are open." />
                            {{-- Same .fv-error pattern the rest of the form
                                 uses, so it reads as a field error rather than
                                 a new kind of message. --}}
                            <div class="fv-error" id="slotError">Pick a date and a time from the calendar above.</div>
                            <div class="form-hint">Mon–Fri 9:00 AM – 9:00 PM &middot; Sat–Sun 9:00 AM – 12:00 PM</div>

                            {{-- Only when editing: a new booking has no
                                 customer to ask. Picking a different slot here
                                 does not move the booking — it asks the
                                 customer to agree first — so the reason is
                                 required, because it is what they are sent. --}}
                            @if($isEdit)
                            <div class="form-group" style="margin-top:16px;">
                                <label class="form-label" for="rescheduleReason">
                                    Reason for changing the schedule
                                </label>
                                <textarea class="form-control @error('reschedule_reason') is-invalid @enderror"
                                          id="rescheduleReason" name="reschedule_reason" rows="2"
                                          placeholder="e.g. Mechanic called in sick — needed only if you change the date or time"
                                >{{ old('reschedule_reason') }}</textarea>
                                @error('reschedule_reason')
                                    <div class="fv-error show">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">
                                    Required only if you change the date or time. The booking keeps its
                                    current slot until the customer agrees, and this is what they are told.
                                </div>
                            </div>
                            @endif
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
                        {{-- The same action the bookings list uses, rather
                             than a second dialog of its own. The one that used
                             to live here posted no reason at all, which the
                             server requires, so pressing it silently bounced
                             back and cancelled nothing. --}}
                        <button type="button" class="btn btn-danger" id="cancelBookingBtn"
                                style="width:100%;justify-content:center;"
                                onclick="cancelThisBooking(this)">
                            <i class="fas fa-ban"></i> Cancel This Booking
                        </button>
                        @endif
                    </div>
                </div>

                {{-- CHANGE HISTORY
                     Only the changes that move money are recorded — status,
                     services, reschedules, cancellation, deletion — so this
                     list stays short enough to actually read. Informational,
                     so blue rather than red. --}}
                @if ($isEdit && $history->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-clock-rotate-left"></i> Change History</div>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <ul style="list-style:none;margin:0;padding:0;">
                            @foreach ($history as $entry)
                            <li style="padding:12px 16px;border-bottom:1px solid var(--border);font-size:.82rem;line-height:1.5;">
                                <div style="color:var(--text);">{{ $entry->describe() }}</div>
                                <div style="color:var(--text-muted);font-size:.76rem;margin-top:2px;">
                                    {{ $entry->actor_name ?? 'Unknown' }} ·
                                    {{ $entry->created_at->format('M j, Y g:i A') }}
                                </div>
                                @if ($entry->reason)
                                <div style="color:var(--text-muted);font-size:.78rem;margin-top:4px;font-style:italic;">
                                    “{{ $entry->reason }}”
                                </div>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </form>

@endsection

{{-- The @section('modals') block that used to sit here has gone with it. The
     dialog asked "are you sure?" and posted nothing but a CSRF token, while
     the server requires a reason — so it redirected away having cancelled
     nothing, and the admin had no way to tell. Cancelling now goes through
     ApxBookingActions, the same path the bookings list uses, which collects
     the reason and reports what actually happened. --}}

@push('scripts')
<script src="{{ asset('assets/js/availability-picker.js') }}"></script>
<script>
@if($isEdit)
/* Cancelling from this screen.

   Hands off to the shared action so the reason prompt, the validation and
   the error reporting are the ones the bookings list already uses — there is
   no second copy here to fall out of step with it.

   On success this page is showing a booking that no longer matches what is
   on file, so it reloads rather than trying to patch the form in place. */
function cancelThisBooking(btn) {
    ApxBookingActions.cancelBooking({{ $booking->id }}, btn, function () {
        window.location.reload();
    });
}
@endif

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
// The plate input only exists when creating; on edit the vehicle is shown
// read-only, so guard rather than attaching to null.
const adminPlate = document.getElementById('adminPlateInput');
if (adminPlate) {
    PlateInput.attach(adminPlate);
    FormValidate.register(adminPlate, { rules: [FormValidate.rules.required('Plate number is required.'), FormValidate.rules.plate()] });
}
// Customer and vehicle fields exist only when creating; on edit they are
// shown read-only, so each is guarded rather than registered against null.
const adminName = document.getElementById('adminCustomerName');
if (adminName) {
    FormValidate.register(adminName, { rules: [FormValidate.rules.required('Full name is required.')] });
}
const adminPhone = document.getElementById('adminCustomerPhone');
if (adminPhone) {
    FormValidate.register(adminPhone, { rules: [FormValidate.rules.required('Phone number is required.'), FormValidate.rules.phonePH()] });
}
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
        return;
    }

    // The date and time live in hidden inputs that the picker fills in, and a
    // `required` attribute on a hidden input is ignored by the browser. So a
    // form with no slot chosen submitted happily, failed validation on the
    // server, and bounced back to a blank page whose only visible message was
    // "Please choose at least one service" — left over from the wiped form,
    // and about the wrong field entirely. There was no way to work out what
    // was actually wrong.
    if (!document.getElementById('adminBookingDate').value || !document.getElementById('adminBookingTime').value) {
        e.preventDefault();
        e.stopImmediatePropagation();
        document.getElementById('slotError').classList.add('show');
        document.getElementById('adminAvp').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}, true);
</script>
@endpush