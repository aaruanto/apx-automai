@extends('layouts.admin')

@php
    $isEdit    = isset($customer);
    $pageTitle = $isEdit ? 'Edit Customer' : 'Add Customer';
@endphp

@section('title', $pageTitle)

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">{!! $isEdit ? '<span>Edit</span> Customer' : 'Add <span>Customer</span>' !!}</h1>
            <ol class="breadcrumb">
                <li>Customers</li>
                <li class="active">{{ $pageTitle }}</li>
            </ol>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
    </div>

    {{-- This page had no error display at all, so every server-side refusal —
         a duplicate email, a malformed plate, a plate already registered to
         another account — looked like the form reloading itself for no reason.
         Same banner the booking form uses. --}}
    <x-form-errors :title="$isEdit ? 'This customer could not be saved.' : 'This customer could not be added.'" />

    <form method="POST" id="customerForm" action="{{ $isEdit ? route('admin.customers.update', $customer->id) : route('admin.customers.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

            <!-- LEFT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- Personal Info -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-user"></i> Personal Information</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="name" id="custCreateName"
                                       value="{{ old('name', $customer->name ?? '') }}"
                                       placeholder="e.g. Juan dela Cruz" required @if($isEdit) readonly @endif />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="tel" name="phone" id="custCreatePhone"
                                       value="{{ old('phone', $customer->phone ?? '') }}"
                                       placeholder="09XXXXXXXXX" required @if($isEdit) readonly @endif />
                            </div>
                        </div>
                        {{-- On an existing customer the address is shown, not
                             offered for editing. It is the identity they sign
                             in with and the one every booking confirmation and
                             reminder is sent to, so changing it from here would
                             quietly lock someone out of their own account.
                             update() refuses the field regardless; this stops
                             the form inviting an edit the server will reject.

                             Rendered as plain text rather than a mailto link,
                             so a stray click cannot open a mail client with a
                             customer's address prefilled. Still selectable, so
                             it can be copied when someone needs to write to
                             them deliberately.

                             Creating a customer still needs the input: there is
                             no address yet to protect. --}}
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            @if ($isEdit)
                                <div class="form-control" aria-readonly="true"
                                     style="background:var(--surface-2);color:var(--text-muted);
                                            display:flex;align-items:center;gap:8px;cursor:default;
                                            user-select:text;">
                                    <i class="fas fa-lock" style="font-size:.72rem;opacity:.7;"></i>
                                    <span>{{ $customer->email ?: '—' }}</span>
                                </div>
                                <div class="form-hint">
                                    The customer changes this from their own account.
                                </div>
                            @else
                                <input class="form-control" type="email" name="email"
                                       value="{{ old('email') }}"
                                       placeholder="optional" />
                            @endif
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Street, City" @if($isEdit) readonly @endif>{{ old('address', $customer->address ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-car"></i> Vehicle Details</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Plate Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="plate" id="custCreatePlate"
                                       value="{{ old('plate', $customer->vehicle->plate_number ?? '') }}"
                                       placeholder="ABC 1234, 123 ABC or AB 12345" required
                                       style="letter-spacing:.08em;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;" @if($isEdit) readonly @endif />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Car Color</label>
                                <input class="form-control" type="text" name="color"
                                       value="{{ old('color', $customer->vehicle->color ?? '') }}"
                                       placeholder="e.g. White" @if($isEdit) readonly @endif />
                            </div>
                        </div>
                        {{-- One field called "Car Model" was written into both
                             vehicles.make and vehicles.model, so the vehicle
                             read back doubled wherever the two are shown
                             together. Each column has its own field. --}}
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Make</label>
                                <input class="form-control" type="text" name="car_make" list="custVehicleMakes"
                                       value="{{ old('car_make', $customer->vehicle->make ?? '') }}"
                                       placeholder="e.g. Toyota" @if($isEdit) readonly @endif />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Model</label>
                                <input class="form-control" type="text" name="car_model" list="custVehicleModels"
                                       value="{{ old('car_model', $customer->vehicle->model ?? '') }}"
                                       placeholder="e.g. Vios" @if($isEdit) readonly @endif />
                            </div>
                        </div>
                        <div class="form-row cols-2" style="margin-bottom:0;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Year</label>
                                <input class="form-control" type="number" name="car_year"
                                       min="1990" max="{{ date('Y') + 1 }}"
                                       value="{{ old('car_year', $customer->vehicle->year ?? '') }}"
                                       placeholder="e.g. 2021" @if($isEdit) readonly @endif />
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Vehicle Type</label>
                                @php $vtype = old('car_type', $customer->vehicle->vehicle_type ?? 'car'); @endphp
                                <select class="form-control" name="car_type" @if($isEdit) disabled @endif>
                                    <option value="car" {{ $vtype === 'car' ? 'selected' : '' }}>Car</option>
                                    <option value="motorcycle" {{ $vtype === 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                </select>
                            </div>
                        </div>

                        @unless($isEdit)
                        <x-vehicle-datalists makes="custVehicleMakes" models="custVehicleModels" />
                        @endunless

                        {{-- The list view shows a count rather than one plate,
                             because a customer can own several. This is where
                             the plates themselves are readable. --}}
                        @if(isset($customer) && ($customer->vehicles ?? collect())->count() > 1)
                        <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border);">
                            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
                                All registered vehicles ({{ $customer->vehicles->count() }})
                            </div>
                            @foreach($customer->vehicles as $veh)
                            <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border);font-size:.85rem;">
                                <i class="fas fa-{{ $veh->vehicle_type === 'motorcycle' ? 'motorcycle' : 'car' }}" style="color:var(--text-muted);width:16px;"></i>
                                <span style="font-family:'Barlow Condensed',sans-serif;font-weight:700;letter-spacing:.04em;">{{ $veh->display_plate }}</span>
                                <span style="color:var(--text-muted);">{{ $veh->display_name }}</span>
                                @if($veh->is_primary)
                                <span style="margin-left:auto;font-size:.68rem;font-weight:700;color:var(--red);text-transform:uppercase;letter-spacing:.06em;">Primary</span>
                                @endif
                            </div>
                            @endforeach
                            <div style="font-size:.74rem;color:var(--text-muted);margin-top:9px;">
                                The fields above edit the most recently added vehicle.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- Notes -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-note-sticky"></i> Notes</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <textarea class="form-control" name="notes" rows="4"
                                      placeholder="Internal notes about this customer..." @if($isEdit) readonly @endif>{{ $customer->notes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="card" style="background:var(--surface-2);">
                    <div class="card-body">
                        {{-- No Save on an existing customer. update() accepts
                             no fields at all, so the button submitted a form
                             that could only ever come back with "these details
                             are read-only" — an admin typing into seven fields
                             and losing the lot. Offering it was the bug. --}}
                        @if (! $isEdit)
                        <button type="submit" class="btn btn-primary" id="customerSubmitBtn" style="width:100%;justify-content:center;padding:12px;">
                            <i class="fas fa-plus"></i>
                            Add Customer
                        </button>
                        @endif
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost"
                           style="width:100%;justify-content:center;{{ $isEdit ? '' : 'margin-top:8px;' }}">
                            {{ $isEdit ? 'Back to Customers' : 'Cancel' }}
                        </a>
                        @if($isEdit)
                        <hr style="border-color:var(--border);margin:12px 0;" />
                        <button type="button" class="btn btn-danger"
                                style="width:100%;justify-content:center;"
                                onclick="openModal('deleteModal')">
                            <i class="fas fa-trash"></i> Delete Customer
                        </button>
                        @endif
                    </div>
                </div>

                @if($isEdit)
                <!-- Booking history summary -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-calendar-check"></i> Booking History</div>
                    </div>
                    <div class="card-body" style="padding:14px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <span style="font-size:.82rem;color:var(--text-muted);">Total Bookings</span>
                            <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.4rem;color:var(--text);">{{ $customer->bookings_count ?? 0 }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:.82rem;color:var(--text-muted);">Last Visit</span>
                            <span style="font-size:.82rem;font-weight:600;">{{ $customer->last_visit ? \Carbon\Carbon::parse($customer->last_visit)->format('M d, Y') : '—' }}</span>
                        </div>
                        <a href="{{ route('admin.bookings.index', ['customer' => $customer->id]) }}"
                           class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:10px;">
                            <i class="fas fa-list"></i> View All Bookings
                        </a>
                    </div>
                </div>
                @endif
            </div>

        </div>
    </form>

@endsection

@push('scripts')
<script>
PlateInput.attach(document.getElementById('custCreatePlate'));
FormValidate.register(document.getElementById('custCreateName'), { rules: [FormValidate.rules.required('Full name is required.')] });
FormValidate.register(document.getElementById('custCreatePhone'), { rules: [FormValidate.rules.required('Phone number is required.'), FormValidate.rules.phonePH()] });
FormValidate.register(document.getElementById('custCreatePlate'), { rules: [FormValidate.rules.required('Plate number is required.'), FormValidate.rules.plate()] });
FormValidate.bindSubmit(document.getElementById('customerForm'), document.getElementById('customerSubmitBtn'));
</script>
@endpush

@if($isEdit ?? false)
@section('modals')
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Customer?</div>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">
                This will permanently delete <strong style="color:var(--text);">{{ $customer->name }}</strong> and all associated records.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteModal')">Cancel</button>
            <form method="POST" action="{{ route('admin.customers.destroy', $customer->id) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Permanently</button>
            </form>
        </div>
    </div>
</div>
@endsection
@endif