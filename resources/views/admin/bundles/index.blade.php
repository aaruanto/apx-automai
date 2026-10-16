@extends('layouts.admin')

@section('title', 'Bundles')

@push('styles')
<style>
.bundle-services { display: flex; flex-wrap: wrap; gap: 6px; }
.bundle-chip {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 999px; padding: 2px 10px; font-size: .74rem; color: var(--text-muted);
}
.bundle-save {
    color: #16a34a; font-weight: 700; font-variant-numeric: tabular-nums;
}
.svc-picker {
    max-height: 220px; overflow-y: auto; border: 1px solid var(--border);
    border-radius: 8px; padding: 8px;
}
.svc-row { display: flex; align-items: center; gap: 8px; padding: 5px 4px; font-size: .84rem; }
.svc-row span.price { margin-left: auto; color: var(--text-muted); font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title"><span>Bundles</span></h1>
            <ol class="breadcrumb">
                <li>System</li>
                <li class="active">Bundles</li>
            </ol>
        </div>
    </div>

    <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.22);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.84rem;color:var(--text-muted);line-height:1.6;">
        <i class="fas fa-circle-info" style="color:var(--info,#3B82F6);margin-right:6px;"></i>
        A bundle sells several services together for less than their separate prices. Changing a
        bundle's price never affects bookings already made — each keeps the price it was sold at.
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-box-open"></i> Service Bundles</div>
            <button class="btn btn-primary btn-sm" onclick="openAddBundle()">
                <i class="fas fa-plus"></i> Add Bundle
            </button>
        </div>

        <div class="card-body" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="apx-table" style="min-width:760px;">
                    <thead>
                        <tr>
                            <th>Bundle</th>
                            <th>Includes</th>
                            <th>Separately</th>
                            <th>Bundle price</th>
                            <th>Saves</th>
                            <th>Booked</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bundles as $b)
                        <tr style="{{ $b->isBookable() ? '' : 'opacity:.55;' }}">
                            <td>
                                <div class="primary-col">{{ $b->name }}</div>
                                @if ($b->vehicle_type)
                                <div style="font-size:.74rem;color:var(--text-muted);">
                                    {{ ucfirst($b->vehicle_type) }} only
                                </div>
                                @endif
                                @if ($b->starts_at || $b->ends_at)
                                <div style="font-size:.74rem;color:var(--text-muted);">
                                    {{ $b->starts_at?->format('M j, Y') ?? 'Any time' }}
                                    &ndash;
                                    {{ $b->ends_at?->format('M j, Y') ?? 'ongoing' }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <div class="bundle-services">
                                    @foreach ($b->services as $s)
                                    <span class="bundle-chip">{{ $s->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td style="font-variant-numeric:tabular-nums;color:var(--text-muted);text-decoration:line-through;">
                                ₱{{ number_format($b->listPrice(), 2) }}
                            </td>
                            <td style="font-variant-numeric:tabular-nums;font-weight:700;">
                                ₱{{ number_format($b->price, 2) }}
                            </td>
                            <td class="bundle-save">
                                ₱{{ number_format($b->savings(), 2) }}
                                <span style="font-weight:400;color:var(--text-muted);">({{ $b->savingsPercent() }}%)</span>
                            </td>
                            <td style="font-variant-numeric:tabular-nums;">{{ $b->bookings_count }}</td>
                            <td>
                                <span class="badge {{ $b->isBookable() ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $b->is_active ? ($b->isBookable() ? 'Live' : 'Out of dates') : 'Off' }}
                                </span>
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <button class="btn btn-ghost btn-sm" onclick="openEditBundle(this)"
                                        data-action="{{ route('admin.bundles.update', $b->id) }}"
                                        data-name="{{ $b->name }}"
                                        data-description="{{ $b->description }}"
                                        data-price="{{ $b->price }}"
                                        data-starts="{{ $b->starts_at?->toDateString() }}"
                                        data-ends="{{ $b->ends_at?->toDateString() }}"
                                        data-vehicle="{{ $b->vehicle_type }}"
                                        data-active="{{ $b->is_active ? '1' : '0' }}"
                                        data-services="{{ $b->services->pluck('id')->implode(',') }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.bundles.toggle', $b->id) }}" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-ghost btn-sm" type="submit"
                                            title="{{ $b->is_active ? 'Switch off' : 'Switch on' }}">
                                        <i class="fas {{ $b->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px 16px;color:var(--text-muted);">
                                No bundles yet. Add one to offer several services together at a discount.
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

<div class="modal-overlay" id="bundleModal">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-box-open" style="color:var(--red);margin-right:8px;"></i>
                <span id="bundleModalTitle">Add Bundle</span>
            </div>
            <button class="modal-close" onclick="closeModal('bundleModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="bundleForm" method="POST" action="{{ route('admin.bundles.store') }}">
            @csrf
            <input type="hidden" name="_method" id="bundleMethod" value="POST" />
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Name <span style="color:var(--red);">*</span></label>
                    <input class="form-control" type="text" name="name" id="bundleName"
                           placeholder="e.g. Basic Maintenance Package" required />
                    <div class="fv-error" data-for="name"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Included Services <span style="color:var(--red);">*</span></label>
                    <div class="svc-picker" id="svcPicker">
                        @foreach ($services as $s)
                        <label class="svc-row">
                            <input type="checkbox" name="service_ids[]" value="{{ $s->id }}"
                                   data-price="{{ $s->price }}" onchange="recalcBundle()" />
                            <span>{{ $s->name }}</span>
                            <span class="price">₱{{ number_format($s->price, 2) }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div class="form-hint">At least two. One service at a discount belongs in the price list instead.</div>
                    <div class="fv-error" data-for="service_ids"></div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Bundle Price <span style="color:var(--red);">*</span></label>
                        <input class="form-control" type="number" step="0.01" min="0"
                               name="price" id="bundlePrice" oninput="recalcBundle()" required />
                        <div class="fv-error" data-for="price"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vehicle Type</label>
                        <select class="form-control" name="vehicle_type" id="bundleVehicle">
                            <option value="">Any vehicle</option>
                            <option value="car">Car only</option>
                            <option value="motorcycle">Motorcycle only</option>
                        </select>
                    </div>
                </div>

                {{-- Live arithmetic, so the admin sees the saving before saving
                     rather than finding out from the validation message. --}}
                <div id="bundleMath" style="background:var(--surface-2);border-radius:8px;padding:10px 14px;font-size:.82rem;color:var(--text-muted);margin-bottom:14px;">
                    Choose services to see the saving.
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Starts <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                        <input class="form-control" type="date" name="starts_at" id="bundleStarts" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ends <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                        <input class="form-control" type="date" name="ends_at" id="bundleEnds" />
                        <div class="fv-error" data-for="ends_at"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Description <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                    <textarea class="form-control" name="description" id="bundleDescription" rows="2"
                              placeholder="Shown to customers under the bundle name."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('bundleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Bundle</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function recalcBundle() {
    const checked = [...document.querySelectorAll('#svcPicker input:checked')];
    const list    = checked.reduce((sum, el) => sum + parseFloat(el.dataset.price || 0), 0);
    const price   = parseFloat(document.getElementById('bundlePrice').value || 0);
    const box     = document.getElementById('bundleMath');

    if (checked.length < 2) {
        box.textContent = 'Choose at least two services to see the saving.';
        return;
    }

    const peso = n => '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    if (!price) {
        box.textContent = 'Separately: ' + peso(list) + '. Enter a bundle price below that.';
        return;
    }

    const saving = list - price;
    box.innerHTML = saving > 0
        ? 'Separately ' + peso(list) + ' → bundle ' + peso(price)
          + ' · <strong style="color:#16a34a;">saves ' + peso(saving)
          + ' (' + Math.round(saving / list * 100) + '%)</strong>'
        : '<span style="color:var(--red);">That is not below the ' + peso(list)
          + ' these services cost separately, so it saves the customer nothing.</span>';
}

function openAddBundle() {
    document.getElementById('bundleModalTitle').textContent = 'Add Bundle';
    document.getElementById('bundleForm').action  = '{{ route('admin.bundles.store') }}';
    document.getElementById('bundleMethod').value = 'POST';
    ['bundleName','bundlePrice','bundleStarts','bundleEnds','bundleDescription'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('bundleVehicle').value = '';
    document.querySelectorAll('#svcPicker input').forEach(el => el.checked = false);
    recalcBundle();
    openModal('bundleModal');
}

function openEditBundle(btn) {
    document.getElementById('bundleModalTitle').textContent = 'Edit Bundle';
    document.getElementById('bundleForm').action  = btn.dataset.action;
    document.getElementById('bundleMethod').value = 'PUT';
    document.getElementById('bundleName').value        = btn.dataset.name;
    document.getElementById('bundlePrice').value       = btn.dataset.price;
    document.getElementById('bundleStarts').value      = btn.dataset.starts;
    document.getElementById('bundleEnds').value        = btn.dataset.ends;
    document.getElementById('bundleVehicle').value     = btn.dataset.vehicle || '';
    document.getElementById('bundleDescription').value = btn.dataset.description || '';

    const ids = (btn.dataset.services || '').split(',');
    document.querySelectorAll('#svcPicker input').forEach(el => {
        el.checked = ids.includes(el.value);
    });
    recalcBundle();
    openModal('bundleModal');
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
    openModal('bundleModal');
    @foreach ($errors->getMessages() as $field => $messages)
    (function () {
        const key = @json(str_replace('.*', '', $field));
        const el  = document.querySelector('.fv-error[data-for="' + key + '"]');
        if (el) { el.textContent = @json($messages[0]); el.classList.add('show'); }
    })();
    @endforeach
});
@endif
</script>
@endpush
