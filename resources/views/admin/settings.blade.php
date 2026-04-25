@extends('layouts.admin')

@section('title', 'Settings')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><span>Settings</span></h1>
            <ol class="breadcrumb">
                <li>System</li>
                <li class="active">Settings</li>
            </ol>
        </div>
    </div>

    <!-- TABS NAV -->
    <div style="display:flex;gap:4px;border-bottom:2px solid var(--border);margin-bottom:24px;" id="settingsTabs">
        <button class="settings-tab active" data-tab="general" onclick="switchTab('general', this)">
            <i class="fas fa-sliders"></i> General
        </button>
        <button class="settings-tab" data-tab="staff" onclick="switchTab('staff', this)">
            <i class="fas fa-users-gear"></i> Staff Accounts
        </button>
        <button class="settings-tab" data-tab="services" onclick="switchTab('services', this)">
            <i class="fas fa-wrench"></i> Services &amp; Pricing
        </button>
    </div>

    <style>
    .settings-tab {
        background: none; border: none; cursor: pointer;
        padding: 9px 16px; font-size: .84rem; font-weight: 600;
        font-family: 'Barlow', sans-serif; color: var(--text-muted);
        border-bottom: 2px solid transparent; margin-bottom: -2px;
        border-radius: 6px 6px 0 0; display: flex; align-items: center; gap: 6px;
    }
    .settings-tab:hover { color: var(--text); background: var(--surface-2); }
    .settings-tab.active { color: var(--red); border-bottom-color: var(--red); }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .section-title {
        font-family: 'Barlow Condensed', sans-serif; font-size: 1rem; font-weight: 700;
        color: var(--text); margin-bottom: 14px; padding-bottom: 8px;
        border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px;
    }
    .section-title i { color: var(--red); }
    .toggle-switch {
        position: relative; width: 42px; height: 22px; flex-shrink: 0;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute; inset: 0; background: var(--surface-3);
        border-radius: 11px; cursor: pointer; transition: background .2s;
    }
    .toggle-slider::before {
        content: ''; position: absolute; height: 16px; width: 16px;
        left: 3px; top: 3px; background: #fff; border-radius: 50%;
        transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    .toggle-switch input:checked + .toggle-slider { background: var(--red); }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }
    .setting-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 0; border-bottom: 1px solid var(--border);
    }
    .setting-row:last-child { border-bottom: none; }
    .setting-info .label { font-size: .85rem; font-weight: 600; color: var(--text); }
    .setting-info .hint  { font-size: .76rem; color: var(--text-muted); margin-top: 2px; }
    </style>

    <!-- ════════════════════ TAB: GENERAL ════════════════════ -->
    <div class="tab-panel active" id="tab-general">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <!-- Business Info -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-building"></i> Business Information</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.general') }}">
                        @csrf @method('PUT')
                        <div class="form-group">
                            <label class="form-label">Business Name</label>
                            <input class="form-control" type="text" name="business_name" value="{{ $settings['business_name'] ?? 'APX Motors Service Center' }}" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch Name</label>
                            <input class="form-control" type="text" name="branch_name" value="{{ $settings['branch_name'] ?? 'Tandang Sora Branch' }}" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2">{{ $settings['address'] ?? 'Tandang Sora Ave., Quezon City' }}</textarea>
                        </div>
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Contact Number</label>
                                <input class="form-control" type="tel" name="contact_number" value="{{ $settings['contact_number'] ?? '' }}" placeholder="(02) XXXX-XXXX" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" placeholder="apxmotors@email.com" />
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Booking Settings -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-calendar-gear"></i> Booking Settings</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.booking') }}">
                        @csrf @method('PUT')
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Opening Time</label>
                                <input class="form-control" type="time" name="open_time" value="{{ $settings['open_time'] ?? '08:00' }}" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Closing Time</label>
                                <input class="form-control" type="time" name="close_time" value="{{ $settings['close_time'] ?? '17:00' }}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max Bookings Per Day</label>
                            <input class="form-control" type="number" name="max_bookings" value="{{ $settings['max_bookings'] ?? 20 }}" min="1" max="100" />
                            <div class="form-hint">How many bookings can be accepted in a single day.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Booking Slot Duration (minutes)</label>
                            <select class="form-control" name="slot_duration">
                                @foreach([30,45,60,90,120] as $min)
                                <option value="{{ $min }}" {{ ($settings['slot_duration'] ?? 60) == $min ? 'selected' : '' }}>{{ $min }} min</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="setting-row" style="padding:8px 0 0;border:none;">
                            <div class="setting-info">
                                <div class="label">Allow Walk-in Bookings</div>
                                <div class="hint">Admins can create bookings without customer login</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="allow_walkin" {{ ($settings['allow_walkin'] ?? true) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="setting-row" style="padding:8px 0;">
                            <div class="setting-info">
                                <div class="label">Email Reminders</div>
                                <div class="hint">Send automated reminders before appointments</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_reminders" {{ ($settings['email_reminders'] ?? false) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:8px;"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Loyalty Settings -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-trophy"></i> Loyalty Tier Thresholds</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.loyalty') }}">
                        @csrf @method('PUT')
                        @php
                            $tiers = [
                                ['key'=>'bronze','label'=>'Bronze','color'=>'#CD7C4F','default'=>1],
                                ['key'=>'silver','label'=>'Silver','color'=>'#94A3B8','default'=>5],
                                ['key'=>'gold',  'label'=>'Gold',  'color'=>'#F59E0B','default'=>10],
                            ];
                        @endphp
                        @foreach($tiers as $tier)
                        <div class="form-group">
                            <label class="form-label" style="color:{{ $tier['color'] }};">
                                <i class="fas fa-{{ $tier['key'] === 'gold' ? 'star' : ($tier['key'] === 'silver' ? 'star-half-stroke' : 'circle') }}"></i>
                                {{ $tier['label'] }} Tier — Minimum Bookings
                            </label>
                            <input class="form-control" type="number" name="loyalty_{{ $tier['key'] }}"
                                   value="{{ $settings['loyalty_'.$tier['key']] ?? $tier['default'] }}" min="0" />
                        </div>
                        @endforeach
                        <div class="form-hint" style="margin-bottom:12px;">Customers automatically move up when they reach these thresholds.</div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- System Preferences -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-display"></i> System Preferences</div>
                </div>
                <div class="card-body">
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="label">Default Theme</div>
                            <div class="hint">Light mode is the default for all admin sessions</div>
                        </div>
                        <select class="filter-select" style="width:auto;" id="defaultThemeSetting">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                        </select>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="label">Show Booking ID Prefix</div>
                            <div class="hint">Display #BK- prefix on booking IDs (e.g. #BK-0001)</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="label">Maintenance Mode</div>
                            <div class="hint">Hides the customer portal while you work</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" {{ ($settings['maintenance'] ?? false) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ════════════════════ TAB: STAFF ACCOUNTS ════════════════════ -->
    <div class="tab-panel" id="tab-staff">
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-users-gear"></i> Staff Accounts</div>
                <button class="btn btn-primary btn-sm" onclick="openModal('addStaffModal')">
                    <i class="fas fa-plus"></i> Add Staff
                </button>
            </div>
            <div class="table-wrap">
                <table class="apx-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($staffAccounts ?? [] as $staff)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:var(--red-glow);color:var(--red);
                                            display:flex;align-items:center;justify-content:center;
                                            font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:.8rem;flex-shrink:0;">
                                    {{ strtoupper(substr($staff->name, 0, 2)) }}
                                </div>
                                <div class="primary-col">{{ $staff->name }}</div>
                            </div>
                        </td>
                        <td>{{ $staff->email }}</td>
                        <td>
                            <span class="badge {{ $staff->role === 'admin' ? 'badge-admin' : 'badge-staff' }}">
                                {{ ucfirst($staff->role) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $staff->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $staff->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="font-size:.78rem;">{{ $staff->created_at->format('M d, Y') }}</td>
                        <td style="text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;">
                                <button class="btn btn-ghost btn-sm btn-icon" title="Edit" onclick="openModal('editStaffModal')"><i class="fas fa-pen"></i></button>
                                <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="openModal('deleteStaffModal')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No staff accounts yet.</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════ TAB: SERVICES & PRICING ════════════════════ -->
    <div class="tab-panel" id="tab-services">
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-wrench"></i> Services &amp; Pricing</div>
                <button class="btn btn-primary btn-sm" onclick="openModal('addServiceModal')">
                    <i class="fas fa-plus"></i> Add Service
                </button>
            </div>
            <div class="table-wrap">
                <table class="apx-table">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Category</th>
                            <th>Price (₱)</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($services ?? [] as $service)
                    <tr>
                        <td class="primary-col">{{ $service->name }}</td>
                        <td>{{ $service->category ?? 'General' }}</td>
                        <td style="font-family:'Barlow Condensed',sans-serif;font-weight:700;color:var(--text);">
                            ₱{{ number_format($service->price ?? 0, 2) }}
                        </td>
                        <td>{{ $service->duration_minutes ?? '—' }} min</td>
                        <td>
                            <span class="badge {{ $service->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $service->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;">
                                <button class="btn btn-ghost btn-sm btn-icon" title="Edit" onclick="openModal('editServiceModal')"><i class="fas fa-pen"></i></button>
                                <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="openModal('deleteServiceModal')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No services configured yet.</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('modals')

<!-- ADD STAFF MODAL -->
<div class="modal-overlay" id="addStaffModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user-plus" style="color:var(--red);margin-right:8px;"></i> Add Staff Account</div>
            <button class="modal-close" onclick="closeModal('addStaffModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('admin.settings.staff.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                        <input class="form-control" type="text" name="name" placeholder="e.g. Jose Santos" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span style="color:var(--red)">*</span></label>
                        <select class="form-control" name="role" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="email" name="email" placeholder="staff@apxmotors.com" required />
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Password <span style="color:var(--red)">*</span></label>
                        <input class="form-control" type="password" name="password" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password <span style="color:var(--red)">*</span></label>
                        <input class="form-control" type="password" name="password_confirmation" required />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addStaffModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD SERVICE MODAL -->
<div class="modal-overlay" id="addServiceModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-plus-circle" style="color:var(--red);margin-right:8px;"></i> Add Service</div>
            <button class="modal-close" onclick="closeModal('addServiceModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('admin.settings.services.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Service Name <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="text" name="name" placeholder="e.g. Full Car Wash" required />
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="category">
                            <option value="Wash">Wash</option>
                            <option value="Detailing">Detailing</option>
                            <option value="Mechanical">Mechanical</option>
                            <option value="Coating">Coating</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (minutes)</label>
                        <input class="form-control" type="number" name="duration_minutes" placeholder="60" min="15" step="15" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (₱) <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="number" name="price" placeholder="0.00" min="0" step="0.01" required />
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="Brief description of the service..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addServiceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Service</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRM MODALS -->
<div class="modal-overlay" id="deleteStaffModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Remove Staff?</div>
            <button class="modal-close" onclick="closeModal('deleteStaffModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">This will permanently remove this staff account. The account will lose all portal access immediately.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteStaffModal')">Cancel</button>
            <button class="btn btn-danger"><i class="fas fa-trash"></i> Remove Account</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="deleteServiceModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Service?</div>
            <button class="modal-close" onclick="closeModal('deleteServiceModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">Deleting this service will remove it from the booking form. Existing bookings using this service will not be affected.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteServiceModal')">Cancel</button>
            <button class="btn btn-danger"><i class="fas fa-trash"></i> Delete Service</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}

// Open tab from URL hash on load
const hash = window.location.hash.replace('#','');
if (['general','staff','services'].includes(hash)) {
    switchTab(hash, document.querySelector('[data-tab="'+hash+'"]'));
}
</script>
@endpush