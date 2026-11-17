@extends('layouts.admin')

@section('title', "Today's Queue")

@push('styles')
<style>
/* ── The board ────────────────────────────────────────────────────────────
   Four columns on the shop's tablet and desktop, stacked on anything
   narrower. No new framework and no new card component: the columns are
   .card like every other panel in the admin, and the cards inside them are
   built from the same borders, radii and tokens.

   grid rather than flex, and with minmax(0, 1fr) rather than 1fr: a plate
   or a long service list would otherwise hold its column open at its
   intrinsic width and push the board off the right edge — the same fault
   .main-content and .card carry min-width:0 for. */
.queue-board {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    align-items: start;
}

.queue-board > * { min-width: 0; }

/* Stacks at the same breakpoint the layout stacks .wide-pair-grid at. Four
   columns inside a tablet's width is already two narrow ones too many, and
   below this they are unreadable rather than merely tight. */
@media (max-width: 1100px) {
    .queue-board { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .queue-board { grid-template-columns: minmax(0, 1fr); }
}

.queue-col-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

/* The count beside each column heading. Tabular so the four do not jiggle
   as the board updates in place. */
.queue-col-count {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800;
    font-size: 1.05rem;
    font-variant-numeric: tabular-nums;
    color: var(--text);
}

.queue-card {
    border: 1px solid var(--border);
    border-left-width: 3px;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 10px;
    background: var(--surface-2, rgba(0, 0, 0, .02));
}

.queue-card:last-child { margin-bottom: 0; }

/* The left edge carries the status colour, so a card is placeable without
   reading its column heading — which matters on a stacked phone layout,
   where the headings are a scroll away. One rule per status, from the same
   tokens the badges use. */
.queue-card.s-arrived             { border-left-color: var(--warning-dark); }
.queue-card.s-vehicle_inspection  { border-left-color: var(--info-dark); }
.queue-card.s-service_in_progress { border-left-color: var(--info); }
.queue-card.s-service_completed   { border-left-color: var(--success-dark); }

.queue-card-top {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-bottom: 7px;
}

/* The queue number. Per day and assigned at arrival, so it stays with the
   car across all four columns — staff call out "car four". */
.queue-num {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: var(--red-glow, rgba(232, 25, 44, .10));
    color: var(--red);
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    font-variant-numeric: tabular-nums;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* A booking taken straight to completed on the edit form has no arrival
   time, so it has no place in a queue ordered by one. Shown as a dash
   rather than given a number it did not earn. */
.queue-num.unnumbered {
    background: var(--surface-2, rgba(0, 0, 0, .04));
    color: var(--text-muted);
}

.queue-customer {
    font-weight: 600;
    font-size: .88rem;
    line-height: 1.25;
    /* The column is narrow and a name can be long. Wrap rather than
       overflow: truncating a customer's name on the screen staff use to
       call them is the wrong trade. */
    overflow-wrap: anywhere;
}

.queue-ref {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .74rem;
    letter-spacing: .04em;
    color: var(--text-muted);
}

.queue-line {
    display: flex;
    align-items: baseline;
    gap: 6px;
    font-size: .78rem;
    color: var(--text-muted);
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.queue-line i {
    width: 12px;
    flex-shrink: 0;
    font-size: .7rem;
    opacity: .8;
    text-align: center;
}

.queue-plate {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 700;
    letter-spacing: .05em;
    color: var(--text);
}

.queue-advance {
    width: 100%;
    justify-content: center;
    margin-top: 9px;
}

.queue-empty {
    text-align: center;
    padding: 18px 10px;
    font-size: .8rem;
    color: var(--text-muted);
}
</style>
@endpush

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Today's <span>Queue</span></h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active">Today's Queue</li>
            </ol>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:700;color:var(--text);">{{ now()->format('l, F j, Y') }}</span>
            <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add</a>
        </div>
    </div>

    {{-- WHAT IS NOT ON THE BOARD
         This screen used to list everything booked today. It lists what is
         physically here, so a confirmed booking that has not arrived is no
         longer on it — and the first thing staff would ask is where it went.
         So it is counted, named, and linked to the list it is on.

         The two are reported separately rather than summed: a booking the
         shop has not reviewed yet is not "confirmed for today", and saying so
         on a screen staff act on is how someone gets turned away at the
         counter. --}}
    <div class="stat-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
        <div class="stat-mini">
            <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;color:var(--info);font-size:.9rem;">
                <i class="fas fa-warehouse"></i>
            </div>
            <div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.5rem;font-weight:800;line-height:1;">{{ $inShop }}</div>
                <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">In the shop</div>
            </div>
        </div>

        <a href="{{ route('admin.bookings.index', ['status' => \App\Enums\BookingStatus::BookingConfirmed->value]) }}"
           class="stat-mini" title="Confirmed for today, not yet arrived">
            <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;color:var(--success);font-size:.9rem;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.5rem;font-weight:800;line-height:1;">{{ $awaitingArrival }}</div>
                <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Confirmed, not arrived</div>
            </div>
        </a>

        <a href="{{ route('admin.bookings.index', ['status' => \App\Enums\BookingStatus::PendingReview->value]) }}"
           class="stat-mini" title="Booked for today and still waiting on review">
            <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;color:var(--warning);font-size:.9rem;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.5rem;font-weight:800;line-height:1;">{{ $awaitingReview }}</div>
                <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Awaiting review</div>
            </div>
        </a>
    </div>

    @if ($inShop === 0)
    <div class="card">
        <div class="card-body" style="text-align:center;padding:46px 20px;color:var(--text-muted);">
            <i class="fas fa-warehouse" style="font-size:2rem;margin-bottom:12px;display:block;opacity:.3;"></i>
            <div style="font-size:.9rem;margin-bottom:4px;">Nothing in the shop yet.</div>
            <div style="font-size:.82rem;">
                A booking appears here once it is marked <strong>Arrived</strong> on the
                <a href="{{ route('admin.bookings.index') }}" style="color:var(--red);">bookings list</a>.
            </div>
        </div>
    </div>
    @else

    <!-- THE BOARD -->
    <div class="queue-board">
        @foreach ($onBoard as $status)
        @php $cards = $columns[$status->value]; @endphp
        <div class="card">
            <div class="card-header">
                <div class="queue-col-head" style="width:100%;">
                    <div class="card-header-title" style="font-size:.84rem;">
                        <span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
                    </div>
                    <span class="queue-col-count">{{ $cards->count() }}</span>
                </div>
            </div>
            <div class="card-body" style="padding:12px;" data-queue-column="{{ $status->value }}">
                @forelse ($cards as $booking)
                {{-- data-* carries what the in-place update needs, so advancing
                     a card does not reload the page and lose the scroll
                     position — the mistake the old Arrived button made. --}}
                <div class="queue-card s-{{ $status->value }}"
                     id="queue-card-{{ $booking->id }}"
                     data-booking="{{ $booking->id }}"
                     data-status="{{ $status->value }}">
                    <div class="queue-card-top">
                        @php $number = $queueNumbers[$booking->id] ?? null; @endphp
                        <div class="queue-num{{ $number === null ? ' unnumbered' : '' }}"
                             title="{{ $number === null ? 'No arrival time recorded' : 'Queue number for today' }}">
                            {{ $number ?? '—' }}
                        </div>
                        <div style="min-width:0;">
                            <div class="queue-customer">{{ $booking->customer->name ?? 'N/A' }}</div>
                            <div class="queue-ref">{{ $booking->reference_number }}</div>
                        </div>
                    </div>

                    <div class="queue-line">
                        <i class="fas fa-car"></i>
                        <span>
                            <span class="queue-plate">{{ $booking->vehicle?->display_plate ?? 'Not provided' }}</span>
                            {{-- Type and transmission from the booking's own
                                 snapshot, not the vehicle: what the mechanic
                                 was asked to work on, not what the record says
                                 today. --}}
                            @if ($booking->vehicle_profile)
                            <span style="opacity:.85;">&middot; {{ $booking->vehicle_profile }}</span>
                            @endif
                        </span>
                    </div>

                    <div class="queue-line">
                        <i class="fas fa-wrench"></i>
                        <span>{{ $booking->service_list }}</span>
                    </div>

                    <div class="queue-line">
                        <i class="fas fa-clock"></i>
                        <span>
                            @if ($booking->arrived_at)
                                Arrived {{ $booking->arrived_at->format('g:i A') }}
                            @else
                                {{-- Honest rather than blank: this is why the
                                     card has no queue number. --}}
                                No arrival time recorded
                            @endif
                        </span>
                    </div>

                    <div class="queue-line">
                        <i class="fas fa-user-gear"></i>
                        <span>{{ $booking->mechanic->name ?? 'Unassigned' }}</span>
                    </div>

                    {{-- No control on a finished card: Service Completed is the
                         end of the ladder, and the enum is what says so — this
                         view does not hold its own opinion about the order. --}}
                    @if ($status->next())
                    <button class="btn btn-primary btn-sm queue-advance"
                            onclick="advanceBooking({{ $booking->id }}, this)">
                        <i class="fas fa-arrow-right"></i> {{ $status->next()->label() }}
                    </button>
                    @endif
                </div>
                @empty
                <div class="queue-empty">Nothing at this stage.</div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    @endif

@endsection

@push('scripts')
<script>
// Serialised from App\Enums\BookingStatus, so the board's idea of what comes
// next is the server's idea of it.
const APX_STATUSES = @json(\App\Enums\BookingStatus::forJs());
const APX_COLUMNS  = @json(array_map(fn ($s) => $s->value, $onBoard));

/**
 * Advance one card, and move it to the next column in place.
 *
 * In place rather than reloading: the shop runs this on a tablet that is
 * propped up all day, and a reload throws away the scroll position and hides
 * the confirmation — which is the fault the old Arrived button had.
 */
function advanceBooking(id, btn) {
    if (btn.disabled) return;

    const card = document.getElementById('queue-card-' + id);
    const from = card ? card.dataset.status : null;
    const next = from ? (APX_STATUSES[from] || {}).next : null;

    if (!next) return;

    ApxAlertModal.show({
        variant: 'confirm',
        // Advancing is an affirmative step, not a destructive one, so green
        // rather than the red used for cancels and deletes.
        accent: 'success',
        confirmStyle: 'success',
        icon: 'fa-arrow-right',
        title: 'Move to ' + APX_STATUSES[next].label + '?',
        message: next === 'service_completed'
            // Said out loud, because the last step is not just a column
            // change: it earns the points, stamps the vehicle's history and
            // texts the customer. Nobody should press it to tidy the board.
            ? 'This finishes the job. The customer is told their vehicle is ready and any loyalty points are awarded.'
            : 'This moves the booking to ' + APX_STATUSES[next].label + '.',
        confirmText: 'Yes, move it',
        cancelText: 'Cancel',
        onConfirm: function () { send(id, btn); }
    });
}

function send(id, btn) {
    btn.disabled = true;
    btn.style.opacity = '.55';

    fetch('/admin/bookings/' + id + '/advance', {
        method: 'PATCH',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    })
    // Read as text first: an error from Laravel may be HTML (419 on an expired
    // session, a 500 page), and that has to surface as a readable message
    // instead of a parse exception nobody sees.
    .then(r => r.text().then(body => {
        let data = null;
        try { data = JSON.parse(body); } catch (e) { /* not JSON */ }
        return { ok: r.ok, status: r.status, data: data };
    }))
    .then(({ ok, status, data }) => {
        if (!ok || !data || !data.success) {
            btn.disabled = false;
            btn.style.opacity = '';

            ApxAlertModal.show({
                variant: status === 409 ? 'info' : 'error',
                title: status === 409 ? 'Already moved' : 'Could not move it',
                message: (data && data.message)
                    || 'The server returned an unexpected response (HTTP ' + status + ').',
            });
            return;
        }

        moveCard(id, data);
    })
    .catch(err => {
        btn.disabled = false;
        btn.style.opacity = '';
        ApxAlertModal.show({
            variant: 'error',
            title: 'Could not move it',
            message: err.message || 'Please try again.',
        });
    });
}

/** Re-home the card in its new column and redraw its control. */
function moveCard(id, data) {
    const card = document.getElementById('queue-card-' + id);
    if (!card) { window.location.reload(); return; }

    const target = document.querySelector('[data-queue-column="' + data.status + '"]');
    if (!target) { window.location.reload(); return; }

    // The placeholder a column shows when it is empty has to go before a card
    // lands in it, and come back in the column the card left if that empties.
    const origin = card.parentElement;

    target.querySelectorAll('.queue-empty').forEach(el => el.remove());

    card.dataset.status = data.status;
    card.className = 'queue-card s-' + data.status;

    const button = card.querySelector('.queue-advance');

    if (data.next) {
        button.disabled = false;
        button.style.opacity = '';
        button.innerHTML = '<i class="fas fa-arrow-right"></i> ' + APX_STATUSES[data.next].label;
    } else if (button) {
        // End of the ladder. The control goes rather than being disabled: a
        // greyed-out button invites a click that can never work.
        button.remove();
    }

    target.appendChild(card);

    if (origin && !origin.querySelector('.queue-card')) {
        const empty = document.createElement('div');
        empty.className = 'queue-empty';
        empty.textContent = 'Nothing at this stage.';
        origin.appendChild(empty);
    }

    recount();

    ApxAlertModal.show({
        variant: 'success',
        title: 'Moved',
        message: data.message,
    });
}

/** The per-column counts, read off the board rather than tracked separately. */
function recount() {
    APX_COLUMNS.forEach(status => {
        const column = document.querySelector('[data-queue-column="' + status + '"]');
        if (!column) return;

        const head = column.closest('.card').querySelector('.queue-col-count');
        if (head) head.textContent = column.querySelectorAll('.queue-card').length;
    });
}
</script>
@endpush
