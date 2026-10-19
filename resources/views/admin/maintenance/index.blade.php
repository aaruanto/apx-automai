@extends('layouts.admin')

@section('title', 'Service Intervals')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><span>Service Intervals</span></h1>
            <ol class="breadcrumb">
                <li>System</li>
                <li class="active">Service Intervals</li>
            </ol>
        </div>
    </div>

    {{-- Blue, not red: this explains how the screen works, and red is reserved
         for errors and destructive actions. --}}
    <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.22);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.84rem;color:var(--text-muted);line-height:1.6;">
        <i class="fas fa-circle-info" style="color:var(--info,#3B82F6);margin-right:6px;"></i>
        These decide when a customer is told their vehicle is due for servicing. A vehicle is counted
        from its last completed booking. Time and distance can both be set — whichever comes first wins,
        and distance is only ever used for customers who have given us a mileage reading.
        Edit the wording of the message itself under
        <a href="{{ route('admin.templates.index') }}" style="color:var(--info,#3B82F6);">Message Templates</a>.
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-car-side"></i> Intervals</div>
            <button class="btn btn-primary btn-sm" onclick="openAddRule()">
                <i class="fas fa-plus"></i> Add Interval
            </button>
        </div>

        <div class="card-body" style="padding:0;">
            {{-- The table is the one element allowed to scroll sideways; the
                 page itself must not, at any width. --}}
            <div style="overflow-x:auto;">
                <table class="apx-table" style="min-width:780px;">
                    <thead>
                        <tr>
                            <th>Interval</th>
                            <th>Every</th>
                            <th>Warn ahead</th>
                            <th>Applies to</th>
                            <th>We recommend</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $r)
                        <tr style="{{ $r->is_active ? '' : 'opacity:.55;' }}">
                            <td>
                                <div class="primary-col">{{ $r->name }}</div>
                                @if ($r->description)
                                    <div style="font-size:.74rem;color:var(--text-muted);margin-top:2px;">{{ $r->description }}</div>
                                @endif
                            </td>
                            <td style="font-variant-numeric:tabular-nums;">
                                @if ($r->interval_months && $r->interval_km)
                                    {{ $r->interval_months }} months<br>
                                    <span style="font-size:.74rem;color:var(--text-muted);">or {{ number_format($r->interval_km) }} km</span>
                                @elseif ($r->interval_months)
                                    {{ $r->interval_months }} months
                                @else
                                    {{ number_format($r->interval_km) }} km
                                @endif
                            </td>
                            <td style="font-variant-numeric:tabular-nums;">
                                {{ $r->lead_days }} {{ \Illuminate\Support\Str::plural('day', $r->lead_days) }}
                            </td>
                            <td>
                                @if ($r->vehicle_type === 'car')
                                    <i class="fas fa-car" style="color:var(--text-muted);margin-right:4px;"></i> Cars
                                @elseif ($r->vehicle_type === 'motorcycle')
                                    <i class="fas fa-motorcycle" style="color:var(--text-muted);margin-right:4px;"></i> Motorcycles
                                @else
                                    All vehicles
                                @endif
                            </td>
                            <td>{{ $r->recommendation() ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $r->is_active ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $r->is_active ? 'Active' : 'Off' }}
                                </span>
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <button class="btn btn-ghost btn-sm"
                                        onclick="openEditRule(this)"
                                        data-action="{{ route('admin.maintenance.update', $r->id) }}"
                                        data-name="{{ $r->name }}"
                                        data-description="{{ $r->description }}"
                                        data-months="{{ $r->interval_months }}"
                                        data-km="{{ $r->interval_km }}"
                                        data-lead="{{ $r->lead_days }}"
                                        data-vtype="{{ $r->vehicle_type }}"
                                        data-bundle="{{ $r->bundle_id }}"
                                        data-service="{{ $r->service_id }}"
                                        data-sort="{{ $r->sort_order }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-ghost btn-sm"
                                        onclick="confirmRuleToggle(this)"
                                        data-action="{{ route('admin.maintenance.toggle', $r->id) }}"
                                        data-name="{{ $r->name }}"
                                        data-active="{{ $r->is_active ? '1' : '0' }}">
                                    <i class="fas {{ $r->is_active ? 'fa-bell-slash' : 'fa-bell' }}"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:32px 16px;color:var(--text-muted);">
                                No service intervals yet. Without one, no customer is ever told their vehicle is due.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('modals')

<div class="modal-overlay" id="ruleModal">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-car-side" style="color:var(--red);margin-right:8px;"></i>
                <span id="ruleModalTitle">Add Interval</span>
            </div>
            <button class="modal-close" onclick="closeModal('ruleModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="ruleForm" method="POST" action="{{ route('admin.maintenance.store') }}">
            @csrf
            <input type="hidden" name="_method" id="ruleMethod" value="POST" />
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Name <span style="color:var(--red);">*</span></label>
                    <input class="form-control" type="text" name="name" id="ruleName"
                           placeholder="e.g. Basic PMS due" required />
                    <div class="form-hint">The customer reads this, in the email and on their dashboard.</div>
                    <div class="fv-error" data-for="name"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                    <input class="form-control" type="text" name="description" id="ruleDescription"
                           placeholder="e.g. Engine oil, oil filter and a general inspection." />
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Every (months)</label>
                        <input class="form-control" type="number" name="interval_months" id="ruleMonths"
                               min="1" max="120" placeholder="6" />
                        <div class="fv-error" data-for="interval_months"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Or every (km)</label>
                        <input class="form-control" type="number" name="interval_km" id="ruleKm"
                               min="100" max="200000" placeholder="5000" />
                        <div class="form-hint">Only used when the customer has given a reading.</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Warn ahead (days) <span style="color:var(--red);">*</span></label>
                        <input class="form-control" type="number" name="lead_days" id="ruleLead"
                               min="0" max="180" value="14" required />
                        <div class="form-hint">Enough notice to book.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Applies to</label>
                        <select class="form-control" name="vehicle_type" id="ruleVtype">
                            <option value="">All vehicles</option>
                            <option value="car">Cars only</option>
                            <option value="motorcycle">Motorcycles only</option>
                        </select>
                    </div>
                </div>

                {{-- A reminder that names a package the customer can actually
                     book is worth far more than one telling them to come in
                     at some point. --}}
                <div class="form-group">
                    <label class="form-label">Recommend this package</label>
                    <select class="form-control" name="bundle_id" id="ruleBundle">
                        <option value="">Nothing in particular</option>
                        @foreach ($bundles as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">…or this single service</label>
                    <select class="form-control" name="service_id" id="ruleService">
                        <option value="">Nothing in particular</option>
                        @foreach ($services as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Sort order</label>
                    <input class="form-control" type="number" name="sort_order" id="ruleSort"
                           min="0" max="999" value="0" />
                    <div class="form-hint">Lowest first, when more than one interval is due at once.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('ruleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<form id="ruleToggleForm" method="POST" action="" style="display:none;">
    @csrf
    @method('PATCH')
</form>

@endsection

@push('scripts')
<script>
function openAddRule() {
    document.getElementById('ruleModalTitle').textContent = 'Add Interval';
    document.getElementById('ruleForm').action  = '{{ route('admin.maintenance.store') }}';
    document.getElementById('ruleMethod').value = 'POST';
    document.getElementById('ruleName').value        = '';
    document.getElementById('ruleDescription').value = '';
    document.getElementById('ruleMonths').value      = '';
    document.getElementById('ruleKm').value          = '';
    document.getElementById('ruleLead').value        = 14;
    document.getElementById('ruleVtype').value       = '';
    document.getElementById('ruleBundle').value      = '';
    document.getElementById('ruleService').value     = '';
    document.getElementById('ruleSort').value        = 0;
    openModal('ruleModal');
}

function openEditRule(btn) {
    document.getElementById('ruleModalTitle').textContent = 'Edit Interval';
    document.getElementById('ruleForm').action  = btn.dataset.action;
    document.getElementById('ruleMethod').value = 'PUT';
    document.getElementById('ruleName').value        = btn.dataset.name;
    document.getElementById('ruleDescription').value = btn.dataset.description;
    document.getElementById('ruleMonths').value      = btn.dataset.months;
    document.getElementById('ruleKm').value          = btn.dataset.km;
    document.getElementById('ruleLead').value        = btn.dataset.lead;
    document.getElementById('ruleVtype').value       = btn.dataset.vtype;
    document.getElementById('ruleBundle').value      = btn.dataset.bundle;
    document.getElementById('ruleService').value     = btn.dataset.service;
    document.getElementById('ruleSort').value        = btn.dataset.sort;
    openModal('ruleModal');
}

/* Switching off is reversible and deletes nothing — reminders already sent
   reference the interval that raised them. The wording says that rather than
   warning about deletion. */
function confirmRuleToggle(btn) {
    const active = btn.dataset.active === '1';

    ApxAlertModal.show({
        variant: 'confirm',
        title:   active ? 'Switch off "' + btn.dataset.name + '"?' : 'Switch on "' + btn.dataset.name + '"?',
        message: active
            ? 'No further reminders will be raised for this interval. Reminders already sent are kept, and this can be undone at any time.'
            : 'Customers will be told again when their vehicle falls due under this interval.',
        confirmText: active ? 'Switch off' : 'Switch on',
        onConfirm: function () {
            const form = document.getElementById('ruleToggleForm');
            form.action = btn.dataset.action;
            form.submit();
        },
    });
}

@if ($errors->any())
// Server-side validation failed, so reopen the form with the messages against
// the fields rather than leaving the admin on a page with no visible reason.
document.addEventListener('DOMContentLoaded', function () {
    openModal('ruleModal');
    @foreach ($errors->getMessages() as $field => $messages)
    (function () {
        const el = document.querySelector('.fv-error[data-for="{{ $field }}"]');
        if (el) { el.textContent = @json($messages[0]); el.classList.add('show'); }
        const input = document.querySelector('[name="{{ $field }}"]');
        if (input) input.classList.add('fv-invalid');
    })();
    @endforeach
});
@endif
</script>
@endpush
