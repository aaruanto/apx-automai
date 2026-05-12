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

    <form method="POST" action="{{ $isEdit ? route('admin.customers.update', $customer->id) : route('admin.customers.store') }}">
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
                                <input class="form-control" type="text" name="name"
                                       value="{{ $customer->name ?? '' }}"
                                       placeholder="e.g. Juan dela Cruz" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="tel" name="phone"
                                       value="{{ $customer->phone ?? '' }}"
                                       placeholder="09XXXXXXXXX" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input class="form-control" type="email" name="email"
                                   value="{{ $customer->email ?? '' }}"
                                   placeholder="optional" />
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Street, City">{{ $customer->address ?? '' }}</textarea>
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
                                <input class="form-control" type="text" name="plate"
                                       value="{{ $customer->vehicle->plate_number ?? '' }}"
                                       placeholder="e.g. ABC 1234" required
                                       style="text-transform:uppercase;letter-spacing:.08em;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Car Color</label>
                                <input class="form-control" type="text" name="color"
                                       value="{{ $customer->vehicle->color ?? '' }}"
                                       placeholder="e.g. White" />
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Car Model</label>
                            <input class="form-control" type="text" name="car_model"
                                   value="{{ $customer->vehicle->model ?? '' }}"
                                   placeholder="e.g. Toyota Vios 2021" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- Loyalty Tier -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-trophy"></i> Loyalty Tier</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Tier Assignment</label>
                            <select class="form-control" name="loyalty">
                                @foreach(['bronze'=>'Bronze (1–4 bookings)','silver'=>'Silver (5–9 bookings)','gold'=>'Gold (10+ bookings)'] as $key => $label)
                                <option value="{{ $key }}" {{ ($customer->loyalty ?? 'bronze') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Tier is normally auto-assigned based on booking count.</div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-note-sticky"></i> Notes</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <textarea class="form-control" name="notes" rows="4"
                                      placeholder="Internal notes about this customer...">{{ $customer->notes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="card" style="background:var(--surface-2);">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
                            <i class="fas fa-{{ $isEdit ? 'floppy-disk' : 'plus' }}"></i>
                            {{ $isEdit ? 'Save Changes' : 'Add Customer' }}
                        </button>
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost"
                           style="width:100%;justify-content:center;margin-top:8px;">
                            Cancel
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