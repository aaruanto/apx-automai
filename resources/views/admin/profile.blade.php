@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">My <span>Profile</span></h1>
            <ol class="breadcrumb">
                <li>System</li>
                <li class="active">My Profile</li>
            </ol>
        </div>
        <button class="btn btn-primary" onclick="openModal('editProfileModal')">
            <i class="fas fa-pen"></i> Edit Profile
        </button>
    </div>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start;">

        <!-- LEFT — Identity card -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card" style="text-align:center;padding:28px 20px;">
                <!-- Avatar -->
                <div style="width:80px;height:80px;border-radius:16px;background:var(--red-glow);border:2px solid rgba(232,25,44,.2);
                            display:flex;align-items:center;justify-content:center;margin:0 auto 14px;
                            font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:2rem;color:var(--red);">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:800;color:var(--text);">
                    {{ Auth::user()->name }}
                </div>
                <div style="margin-top:6px;">
                    <span class="badge badge-admin">Admin</span>
                </div>
                <div style="font-size:.8rem;color:var(--text-muted);margin-top:8px;">{{ Auth::user()->email }}</div>

                <hr style="border:none;border-top:1px solid var(--border);margin:16px 0;" />

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;text-align:left;">
                    <div style="background:var(--surface-2);border-radius:8px;padding:10px;">
                        <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Member Since</div>
                        <div style="font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.95rem;margin-top:2px;">
                            {{ Auth::user()->created_at->format('M Y') }}
                        </div>
                    </div>
                    <div style="background:var(--surface-2);border-radius:8px;padding:10px;">
                        <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Last Login</div>
                        <div style="font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.95rem;margin-top:2px;">
                            {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick links -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-bolt"></i> Quick Actions</div>
                </div>
                <div style="padding:8px;">
                    <a href="{{ route('admin.settings') }}" class="btn btn-ghost" style="width:100%;justify-content:flex-start;margin-bottom:4px;">
                        <i class="fas fa-gear" style="width:16px;"></i> System Settings
                    </a>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-ghost" style="width:100%;justify-content:flex-start;margin-bottom:4px;">
                        <i class="fas fa-calendar-check" style="width:16px;"></i> Manage Bookings
                    </a>
                    <button class="btn btn-ghost" style="width:100%;justify-content:flex-start;margin-bottom:4px;" onclick="openModal('changePasswordModal')">
                        <i class="fas fa-lock" style="width:16px;"></i> Change Password
                    </button>
                    <a href="{{ route('logout') }}" class="btn btn-danger" style="width:100%;justify-content:flex-start;margin-top:4px;"
                       onclick="event.preventDefault();document.getElementById('logout-form-profile').submit();">
                        <i class="fas fa-right-from-bracket" style="width:16px;"></i> Logout
                    </a>
                    <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
                </div>
            </div>
        </div>

        <!-- RIGHT — Details + Activity -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-id-card"></i> Account Information</div>
                    <span class="badge badge-active">Active</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:5px;">Full Name</div>
                            <div style="font-weight:600;color:var(--text);">{{ Auth::user()->name }}</div>
                        </div>
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:5px;">Email Address</div>
                            <div style="font-weight:600;color:var(--text);">{{ Auth::user()->email }}</div>
                        </div>
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:5px;">Role</div>
                            <span class="badge badge-admin">Administrator</span>
                        </div>
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:5px;">Account Status</div>
                            <span class="badge badge-active">Active</span>
                        </div>
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:5px;">Phone Number</div>
                            <div style="font-weight:600;color:var(--text);">{{ Auth::user()->phone ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:5px;">Branch</div>
                            <div style="font-weight:600;color:var(--text);">APX Motors — Tandang Sora</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Summary -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-chart-simple"></i> Activity Summary</div>
                    <span style="font-size:.75rem;color:var(--text-muted);">All time</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                        @php
                            $stats = [
                                ['label'=>'Bookings Created', 'value'=>$activityStats['bookings_created'] ?? 0, 'icon'=>'fa-calendar-plus', 'color'=>'var(--red)'],
                                ['label'=>'Customers Added', 'value'=>$activityStats['customers_added'] ?? 0, 'icon'=>'fa-user-plus', 'color'=>'#22c55e'],
                                ['label'=>'Bookings Updated', 'value'=>$activityStats['bookings_updated'] ?? 0, 'icon'=>'fa-pen-to-square', 'color'=>'#3b82f6'],
                                ['label'=>'Services Managed', 'value'=>$activityStats['services_managed'] ?? 0, 'icon'=>'fa-wrench', 'color'=>'#f59e0b'],
                            ];
                        @endphp
                        @foreach($stats as $s)
                        <div style="background:var(--surface-2);border-radius:8px;padding:14px;text-align:center;">
                            <div style="font-size:1.2rem;color:{{ $s['color'] }};margin-bottom:8px;"><i class="fas {{ $s['icon'] }}"></i></div>
                            <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:800;color:var(--text);line-height:1;">{{ $s['value'] }}</div>
                            <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px;">{{ $s['label'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Activity Log -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-clock-rotate-left"></i> Recent Activity</div>
                    <span style="font-size:.75rem;color:var(--text-muted);">Last 10 actions</span>
                </div>
                <div class="table-wrap">
                    <table class="apx-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($activityLog ?? [] as $log)
                        <tr>
                            <td>
                                <span class="badge badge-staff">{{ $log->action }}</span>
                            </td>
                            <td>{{ $log->description }}</td>
                            <td style="white-space:nowrap;font-size:.78rem;">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);">No activity recorded yet.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('modals')

<!-- EDIT PROFILE MODAL -->
<div class="modal-overlay" id="editProfileModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user-pen" style="color:var(--red);margin-right:8px;"></i> Edit Profile</div>
            <button class="modal-close" onclick="closeModal('editProfileModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('admin.profile.update') }}">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                        <input class="form-control" type="text" name="name" value="{{ Auth::user()->name }}" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input class="form-control" type="tel" name="phone" value="{{ Auth::user()->phone ?? '' }}" placeholder="09XXXXXXXXX" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="email" name="email" value="{{ Auth::user()->email }}" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editProfileModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal-overlay" id="changePasswordModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-lock" style="color:var(--red);margin-right:8px;"></i> Change Password</div>
            <button class="modal-close" onclick="closeModal('changePasswordModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('admin.profile.password') }}">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Current Password <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="password" name="current_password" required />
                </div>
                <div class="form-group">
                    <label class="form-label">New Password <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="password" name="password" required />
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Confirm New Password <span style="color:var(--red)">*</span></label>
                    <input class="form-control" type="password" name="password_confirmation" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('changePasswordModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Update Password</button>
            </div>
        </form>
    </div>
</div>

@endsection