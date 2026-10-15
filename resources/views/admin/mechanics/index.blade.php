@extends('layouts.admin')

@section('title', 'Mechanics')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><span>Mechanics</span></h1>
            <ol class="breadcrumb">
                <li>System</li>
                <li class="active">Mechanics</li>
            </ol>
        </div>
    </div>

    {{-- Mechanics are records, not accounts: a name and a number so work can
         be assigned and someone can be rung about it. Saying so here stops the
         obvious question about why there is no password field. --}}
    <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.22);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.84rem;color:var(--text-muted);line-height:1.6;">
        <i class="fas fa-circle-info" style="color:var(--info,#3B82F6);margin-right:6px;"></i>
        Mechanics do not sign in. These are records for assigning work and keeping contact details —
        the only account on this system is yours.
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-screwdriver-wrench"></i> Mechanic List</div>
            <button class="btn btn-primary btn-sm" onclick="openAddMechanic()">
                <i class="fas fa-plus"></i> Add Mechanic
            </button>
        </div>

        <div class="card-body" style="padding:0;">
            {{-- The table is the one element allowed to scroll sideways; the
                 page itself must not, at any width. --}}
            <div style="overflow-x:auto;">
                <table class="apx-table" style="min-width:620px;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Specialty</th>
                            <th>Bookings</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mechanics as $m)
                        <tr style="{{ $m->isActive() ? '' : 'opacity:.55;' }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="avatar-sm">{{ strtoupper(substr($m->name, 0, 2)) }}</div>
                                    <div class="primary-col">{{ $m->name }}</div>
                                </div>
                            </td>
                            <td style="font-variant-numeric:tabular-nums;">{{ $m->contact_number ?: '—' }}</td>
                            <td>{{ $m->specialty ?: 'General' }}</td>
                            <td style="font-variant-numeric:tabular-nums;">{{ $m->bookings_count }}</td>
                            <td>
                                <span class="badge {{ $m->isActive() ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $m->isActive() ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <button class="btn btn-ghost btn-sm"
                                        onclick="openEditMechanic(this)"
                                        data-action="{{ route('admin.mechanics.update', $m->id) }}"
                                        data-name="{{ $m->name }}"
                                        data-contact="{{ $m->contact_number }}"
                                        data-specialty="{{ $m->specialty }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-ghost btn-sm"
                                        onclick="confirmToggle(this)"
                                        data-action="{{ route('admin.mechanics.toggle', $m->id) }}"
                                        data-name="{{ $m->name }}"
                                        data-active="{{ $m->isActive() ? '1' : '0' }}"
                                        data-bookings="{{ $m->bookings_count }}">
                                    <i class="fas {{ $m->isActive() ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:32px 16px;color:var(--text-muted);">
                                No mechanics yet. Add one so bookings can be assigned.
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

<div class="modal-overlay" id="mechanicModal">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-screwdriver-wrench" style="color:var(--red);margin-right:8px;"></i>
                <span id="mechanicModalTitle">Add Mechanic</span>
            </div>
            <button class="modal-close" onclick="closeModal('mechanicModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="mechanicForm" method="POST" action="{{ route('admin.mechanics.store') }}">
            @csrf
            <input type="hidden" name="_method" id="mechanicMethod" value="POST" />
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Name <span style="color:var(--red);">*</span></label>
                    <input class="form-control" type="text" name="name" id="mechanicName"
                           placeholder="e.g. Jose Santos" required />
                    <div class="fv-error" data-for="name"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number <span style="color:var(--red);">*</span></label>
                    <input class="form-control" type="tel" name="contact_number" id="mechanicContact"
                           placeholder="09171234567" required />
                    <div class="form-hint">Philippine mobile number. 09XX, +639XX and 639XX are all accepted.</div>
                    <div class="fv-error" data-for="contact_number"></div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Specialty <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                    <input class="form-control" type="text" name="specialty" id="mechanicSpecialty"
                           placeholder="e.g. Engine, Brakes, Electrical" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('mechanicModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<form id="toggleForm" method="POST" action="" style="display:none;">
    @csrf
    @method('PATCH')
</form>

@endsection

@push('scripts')
<script>
function openAddMechanic() {
    document.getElementById('mechanicModalTitle').textContent = 'Add Mechanic';
    document.getElementById('mechanicForm').action  = '{{ route('admin.mechanics.store') }}';
    document.getElementById('mechanicMethod').value = 'POST';
    document.getElementById('mechanicName').value      = '';
    document.getElementById('mechanicContact').value   = '';
    document.getElementById('mechanicSpecialty').value = '';
    openModal('mechanicModal');
}

function openEditMechanic(btn) {
    document.getElementById('mechanicModalTitle').textContent = 'Edit Mechanic';
    document.getElementById('mechanicForm').action  = btn.dataset.action;
    document.getElementById('mechanicMethod').value = 'PUT';
    document.getElementById('mechanicName').value      = btn.dataset.name;
    document.getElementById('mechanicContact').value   = btn.dataset.contact;
    document.getElementById('mechanicSpecialty').value = btn.dataset.specialty;
    openModal('mechanicModal');
}

/* Deactivating is reversible and keeps the booking history, so the wording
   says that plainly instead of warning about deletion — nothing is deleted.
   Blue, because it is information rather than an error. */
function confirmToggle(btn) {
    const active   = btn.dataset.active === '1';
    const bookings = parseInt(btn.dataset.bookings, 10) || 0;

    ApxAlertModal.show({
        variant: 'confirm',
        title:   active ? 'Deactivate ' + btn.dataset.name + '?' : 'Reactivate ' + btn.dataset.name + '?',
        message: active
            ? btn.dataset.name + ' will stop appearing when assigning bookings.'
              + (bookings > 0
                  ? ' Their ' + bookings + ' existing booking' + (bookings === 1 ? '' : 's')
                    + ' keep' + (bookings === 1 ? 's' : '') + ' the assignment, and nothing is deleted.'
                  : ' Nothing is deleted and this can be undone at any time.')
            : btn.dataset.name + ' will be assignable to bookings again.',
        confirmText: active ? 'Deactivate' : 'Reactivate',
        onConfirm: function () {
            const form = document.getElementById('toggleForm');
            form.action = btn.dataset.action;
            form.submit();
        },
    });
}

@if ($errors->any())
// Server-side validation failed, so reopen the form with the messages against
// the fields rather than leaving the admin on a page with no visible reason.
document.addEventListener('DOMContentLoaded', function () {
    openModal('mechanicModal');
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
