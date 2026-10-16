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
                            <input class="form-control @error('business_name') fv-invalid @enderror" type="text" name="business_name" value="{{ old('business_name', $settings['business_name'] ?? 'APX Motors Service Center') }}" />
                            @error('business_name')<div class="fv-error show">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch Name</label>
                            <input class="form-control @error('branch_name') fv-invalid @enderror" type="text" name="branch_name" value="{{ old('branch_name', $settings['branch_name'] ?? 'Tandang Sora Branch') }}" />
                            @error('branch_name')<div class="fv-error show">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea class="form-control @error('address') fv-invalid @enderror" name="address" rows="2">{{ old('address', $settings['address'] ?? 'Tandang Sora Ave., Quezon City') }}</textarea>
                            @error('address')<div class="fv-error show">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Contact Number</label>
                                <input class="form-control @error('contact_number') fv-invalid @enderror" type="tel" name="contact_number" value="{{ old('contact_number', $settings['contact_number'] ?? '') }}" placeholder="(02) XXXX-XXXX" />
                            @error('contact_number')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input class="form-control @error('contact_email') fv-invalid @enderror" type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="apxmotors@email.com" />
                            @error('contact_email')<div class="fv-error show">{{ $message }}</div>@enderror
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
                                <input class="form-control @error('open_time') fv-invalid @enderror" type="time" name="open_time" value="{{ old('open_time', $settings['open_time'] ?? '08:00') }}" />
                            @error('open_time')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Closing Time</label>
                                <input class="form-control @error('close_time') fv-invalid @enderror" type="time" name="close_time" value="{{ old('close_time', $settings['close_time'] ?? '17:00') }}" />
                            @error('close_time')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Weekend Opening Time</label>
                                <input class="form-control @error('weekend_open_time') fv-invalid @enderror" type="time" name="weekend_open_time" value="{{ old('weekend_open_time', $settings['weekend_open_time'] ?? '09:00') }}" />
                            @error('weekend_open_time')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Weekend Closing Time</label>
                                <input class="form-control @error('weekend_close_time') fv-invalid @enderror" type="time" name="weekend_close_time" value="{{ old('weekend_close_time', $settings['weekend_close_time'] ?? '12:00') }}" />
                            @error('weekend_close_time')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max Bookings Per Day</label>
                            <input class="form-control @error('max_bookings') fv-invalid @enderror" type="number" name="max_bookings" value="{{ old('max_bookings', $settings['max_bookings'] ?? 20) }}" min="1" max="100" />
                            @error('max_bookings')<div class="fv-error show">{{ $message }}</div>@enderror
                            <div class="form-hint">How many bookings can be accepted in a single day. Applies to the customer booking calendar.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Booking Slot Duration (minutes)</label>
                            <select class="form-control @error('slot_duration') fv-invalid @enderror" name="slot_duration">
                                @foreach([30,45,60,90,120] as $min)
                                <option value="{{ $min }}" {{ old('slot_duration', $settings['slot_duration'] ?? 60) == $min ? 'selected' : '' }}>{{ $min }} min</option>
                                @endforeach
                            </select>
                            @error('slot_duration')<div class="fv-error show">{{ $message }}</div>@enderror
                        </div>
                        <div class="setting-row" style="padding:8px 0 0;border:none;">
                            <div class="setting-info">
                                <div class="label">Allow Walk-in Bookings</div>
                                <div class="hint">Admins can create bookings without customer login</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="allow_walkin" {{ (old('open_time') !== null ? old('allow_walkin') : ($settings['allow_walkin'] ?? true)) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="setting-row" style="padding:8px 0;">
                            <div class="setting-info">
                                <div class="label">Email Reminders</div>
                                <div class="hint">Send automated reminders before appointments</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_reminders" {{ (old('open_time') !== null ? old('email_reminders') : ($settings['email_reminders'] ?? false)) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        {{-- ACCOUNT DISCOUNT
                             Configurable rather than a hardcoded percentage:
                             the figure is a commercial decision the shop will
                             want to change without a deploy. Off by default,
                             so enabling it is deliberate. --}}
                        <div class="setting-row" style="padding:8px 0;">
                            <div class="setting-info">
                                <div class="label">Account Discount</div>
                                <div class="hint">Customers who book signed in get a discount; guests pay full price</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="account_discount_enabled" {{ (old('open_time') !== null ? old('account_discount_enabled') : ($settings['account_discount_enabled'] ?? false)) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="form-group" style="max-width:220px;">
                            <label class="form-label">Discount Percentage</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input class="form-control @error('account_discount_percent') fv-invalid @enderror"
                                       type="number" name="account_discount_percent" min="0" max="50"
                                       value="{{ old('account_discount_percent', $settings['account_discount_percent'] ?? 5) }}" />
                                <span style="color:var(--text-muted);font-weight:700;">%</span>
                            </div>
                            @error('account_discount_percent')<div class="fv-error show">{{ $message }}</div>@enderror
                            <div class="form-hint">Applies to services and bundles, calculated on the server at booking time.</div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top:8px;"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            {{-- LOYALTY POINTS
                 Its own form rather than part of the booking settings: these
                 decide what points are worth, and a stray save on an
                 unrelated screen should not be able to move them. --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-star"></i> Loyalty Points</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.loyalty') }}">
                        @csrf @method('PUT')

                        <div class="setting-row" style="padding:0 0 8px;">
                            <div class="setting-info">
                                <div class="label">Loyalty Programme</div>
                                <div class="hint">Customers earn points on completed work and spend them on future bookings</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="loyalty_enabled" {{ ($settings['loyalty_enabled'] ?? false) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Pesos Per Point</label>
                                <input class="form-control @error('loyalty_pesos_per_point') fv-invalid @enderror"
                                       type="number" name="loyalty_pesos_per_point" min="1" max="10000"
                                       value="{{ old('loyalty_pesos_per_point', $settings['loyalty_pesos_per_point'] ?? 50) }}"
                                       oninput="recalcLoyalty()" id="loyPesos" />
                                <div class="form-hint">Spend this much to earn one point.</div>
                                @error('loyalty_pesos_per_point')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Point Value (₱)</label>
                                <input class="form-control @error('loyalty_point_value') fv-invalid @enderror"
                                       type="number" step="0.01" name="loyalty_point_value" min="0" max="100"
                                       value="{{ old('loyalty_point_value', $settings['loyalty_point_value'] ?? 0.50) }}"
                                       oninput="recalcLoyalty()" id="loyValue" />
                                <div class="form-hint">What one point is worth when redeemed.</div>
                                @error('loyalty_point_value')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- The return rate is the number that actually matters
                             and the one nobody works out by hand. --}}
                        <div id="loyMath" style="background:var(--surface-2);border-radius:8px;padding:10px 14px;font-size:.82rem;color:var(--text-muted);margin-bottom:14px;"></div>

                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Minimum To Redeem</label>
                                <input class="form-control @error('loyalty_min_redeem') fv-invalid @enderror"
                                       type="number" name="loyalty_min_redeem" min="0" max="100000"
                                       value="{{ old('loyalty_min_redeem', $settings['loyalty_min_redeem'] ?? 100) }}" />
                                <div class="form-hint">Points needed before any can be spent.</div>
                                @error('loyalty_min_redeem')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Most Of A Booking Points May Cover</label>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input class="form-control @error('loyalty_max_redeem_percent') fv-invalid @enderror"
                                           type="number" name="loyalty_max_redeem_percent" min="0" max="100"
                                           value="{{ old('loyalty_max_redeem_percent', $settings['loyalty_max_redeem_percent'] ?? 50) }}" />
                                    <span style="color:var(--text-muted);font-weight:700;">%</span>
                                </div>
                                @error('loyalty_max_redeem_percent')<div class="fv-error show">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group" style="max-width:260px;">
                            <label class="form-label">Points Expire After</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input class="form-control @error('loyalty_expiry_months') fv-invalid @enderror"
                                       type="number" name="loyalty_expiry_months" min="0" max="120"
                                       value="{{ old('loyalty_expiry_months', $settings['loyalty_expiry_months'] ?? 12) }}" />
                                <span style="color:var(--text-muted);font-weight:700;">months</span>
                            </div>
                            <div class="form-hint">Use 0 for points that never expire.</div>
                            @error('loyalty_expiry_months')<div class="fv-error show">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top:8px;"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- System Preferences -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-display"></i> System Preferences</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.system') }}">
                        @csrf @method('PUT')
                        <div class="setting-row">
                            <div class="setting-info">
                                <div class="label">Default Theme</div>
                                <div class="hint">Light mode is the default for all admin sessions</div>
                            </div>
                            <select class="filter-select @error('default_theme') fv-invalid @enderror" style="width:auto;" name="default_theme" id="defaultThemeSetting">
                                <option value="light" {{ old('default_theme', $settings['default_theme'] ?? 'light') === 'light' ? 'selected' : '' }}>Light</option>
                                <option value="dark"  {{ old('default_theme', $settings['default_theme'] ?? 'light') === 'dark' ? 'selected' : '' }}>Dark</option>
                            </select>
                            @error('default_theme')<div class="fv-error show">{{ $message }}</div>@enderror
                        </div>
                        <div class="setting-row">
                            <div class="setting-info">
                                <div class="label">Show Booking ID Prefix</div>
                                <div class="hint">Display #BK- prefix on booking IDs (e.g. #BK-0001)</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="show_id_prefix" {{ (old('default_theme') !== null ? old('show_id_prefix') : ($settings['show_id_prefix'] ?? true)) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="setting-row">
                            <div class="setting-info">
                                <div class="label">Maintenance Mode</div>
                                <div class="hint">Hides the customer portal while you work</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="maintenance" {{ (old('default_theme') !== null ? old('maintenance') : ($settings['maintenance'] ?? false)) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:8px;"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Account Deletions (read-only log) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-user-slash"></i> Account Deletions</div>
                </div>
                <div class="card-body">
                    <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:8px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-circle-info" style="color:var(--info);flex-shrink:0;"></i>
                        <span style="font-size:.8rem;color:var(--text-muted);">
                            Customers delete their own accounts &mdash; nothing here needs your approval.
                            Accounts stay recoverable for 30 days, then personal details are erased automatically. Bookings are always kept so reports and revenue stay accurate.
                        </span>
                    </div>
                    @forelse($accountDeletions ?? [] as $d)
                        <div class="settings-row" style="display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
                            <div>
                                <div style="font-size:.85rem;color:var(--text);">
                                    Customer #{{ $d->user_id }}
                                    @if($d->restored_at)
                                        <span class="badge badge-confirmed" style="margin-left:6px;">Restored</span>
                                    @elseif($d->anonymized_at)
                                        <span class="badge badge-cancelled" style="margin-left:6px;">Erased</span>
                                    @else
                                        <span class="badge badge-pending" style="margin-left:6px;">Recoverable</span>
                                    @endif
                                </div>
                                <div style="font-size:.75rem;color:var(--text-muted);">
                                    {{ $d->bookings_retained }} booking{{ $d->bookings_retained === 1 ? '' : 's' }} kept
                                    @if($d->bookings_cancelled)
                                        &middot; {{ $d->bookings_cancelled }} upcoming cancelled
                                    @endif
                                    @if(! $d->anonymized_at && ! $d->restored_at && $d->purge_at)
                                        &middot; data erased {{ $d->purge_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                            <div style="font-size:.75rem;color:var(--text-muted);white-space:nowrap;">
                                {{ $d->created_at->format('M d, Y') }}
                            </div>
                        </div>
                    @empty
                        <div style="font-size:.82rem;color:var(--text-muted);font-style:italic;padding:6px 0;">
                            No account deletions yet.
                        </div>
                    @endforelse
                </div>
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
                        <td>{{ $service->duration ?? '—' }} min</td>
                        <td>
                            <span class="badge {{ $service->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $service->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;">
                                <button class="btn btn-ghost btn-sm btn-icon" title="Edit"
                                    onclick="openEditService(this)"
                                    data-action="{{ route('admin.settings.services.update', $service->id) }}"
                                    data-name="{{ $service->name }}"
                                    data-category="{{ $service->category }}"
                                    data-duration="{{ $service->duration }}"
                                    data-price="{{ $service->price }}"
                                    data-description="{{ $service->description }}"><i class="fas fa-pen"></i></button>
                                <button class="btn btn-danger btn-sm btn-icon" title="Delete"
                                    onclick="openDeleteService(this)"
                                    data-action="{{ route('admin.settings.services.destroy', $service->id) }}"
                                    data-name="{{ $service->name }}"><i class="fas fa-trash"></i></button>
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
                            @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (minutes) <span style="color:var(--red)">*</span></label>
                        <input class="form-control" type="number" name="duration" placeholder="60" min="15" step="15" required />
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


<!-- EDIT SERVICE MODAL -->
<div class="modal-overlay" id="editServiceModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-pen" style="color:var(--red);margin-right:8px;"></i> Edit Service</div>
            <button class="modal-close" onclick="closeModal('editServiceModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="editServiceForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Service Name <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="text" name="name" id="editServiceName" required />
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="category" id="editServiceCategory">
                            @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (minutes) <span style="color:var(--red)">*</span></label>
                        <input class="form-control" type="number" name="duration" id="editServiceDuration" min="15" step="15" required />
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (₱) <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="number" name="price" id="editServicePrice" min="0" step="0.01" required />
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="editServiceDescription" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editServiceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRM MODALS -->
<div class="modal-overlay" id="deleteServiceModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Service?</div>
            <button class="modal-close" onclick="closeModal('deleteServiceModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">Deleting <strong id="deleteServiceName">this service</strong> will remove it from the booking form. Existing bookings using this service will not be affected.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteServiceModal')">Cancel</button>
            <form id="deleteServiceForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Service</button>
            </form>
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
if (['general','services'].includes(hash)) {
    switchTab(hash, document.querySelector('[data-tab="'+hash+'"]'));
}

// ── Edit / Delete service ────────────────────────────────────────────────
function openEditService(btn) {
    document.getElementById('editServiceForm').action        = btn.dataset.action;
    document.getElementById('editServiceName').value         = btn.dataset.name;
    document.getElementById('editServiceCategory').value     = btn.dataset.category;
    document.getElementById('editServiceDuration').value     = btn.dataset.duration;
    document.getElementById('editServicePrice').value        = btn.dataset.price;
    document.getElementById('editServiceDescription').value  = btn.dataset.description;
    openModal('editServiceModal');
}

function openDeleteService(btn) {
    document.getElementById('deleteServiceForm').action  = btn.dataset.action;
    document.getElementById('deleteServiceName').textContent = btn.dataset.name;
    openModal('deleteServiceModal');
}
</script>
@endpush
@push('scripts')
<script>
// Settings save feedback — reuses the shared alert modal from the admin layout.
@if (session('success'))
    ApxAlertModal.show({
        variant: 'success',
        title: 'Settings saved',
        message: @json(session('success')),
    });
@endif
@if ($errors->any())
    ApxAlertModal.show({
        variant: 'error',
        title: 'Couldn\'t save settings',
        message: @json($errors->first() . ($errors->count() > 1 ? ' (and ' . ($errors->count() - 1) . ' more)' : '')),
    });
@endif
</script>
@endpush

@push('scripts')
<script>
/* The return rate: what a customer gets back as a percentage of what they
   spend. It is the number that decides whether the programme is affordable,
   and the one nobody works out by hand from "₱50 per point, ₱0.50 a point". */
function recalcLoyalty() {
    const pesos = parseFloat(document.getElementById('loyPesos')?.value || 0);
    const value = parseFloat(document.getElementById('loyValue')?.value || 0);
    const box   = document.getElementById('loyMath');
    if (!box) return;

    if (!pesos || value < 0) {
        box.textContent = 'Enter both figures to see the return rate.';
        return;
    }

    const rate = (value / pesos) * 100;
    const per1000 = Math.round((1000 / pesos)) + ' points, worth ₱'
        + ((1000 / pesos) * value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    box.innerHTML = '₱1,000 spent earns ' + per1000
        + ' · <strong style="color:var(--text);">a ' + rate.toFixed(2) + '% return</strong>'
        + (rate > 10 ? ' <span style="color:var(--red);">— that is unusually generous, check the figures.</span>' : '');
}
recalcLoyalty();
</script>
@endpush
