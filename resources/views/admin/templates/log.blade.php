@extends('layouts.admin')

@section('title', 'Delivery Log')

@section('content')

@php
    // Presentation only. trigger_key is a machine value shared with the
    // reminders table and the dedupe key, so it is mapped here rather than
    // renamed at the source.
    $triggerLabels = [
        'remind_7d'       => '7 days before',
        'remind_3d'       => '3 days before',
        'remind_1d'       => '1 day before',
        'remind_2h'       => '2 hours before',
        'maintenance_due' => 'Service due',
        'test'            => 'Admin test send',
    ];
@endphp

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><span>Delivery</span> Log</h1>
            <ol class="breadcrumb">
                <li>Message Templates</li>
                <li class="active">Delivery Log</li>
            </ol>
        </div>
        <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Templates
        </a>
    </div>

    {{-- The active driver, stated before any figure below it. A wall of
         "simulated" rows is correct and expected on the log driver, and
         without this banner it reads as a system-wide failure. --}}
    @if($driver === 'log')
    <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:8px;padding:12px 18px;margin-bottom:24px;display:flex;align-items:flex-start;gap:12px;">
        <i class="fas fa-circle-info" style="color:var(--info);font-size:1rem;flex-shrink:0;margin-top:2px;"></i>
        <span style="font-size:.85rem;color:var(--text-muted);">
            <strong style="color:var(--text);">SMS is in simulation mode.</strong>
            Messages are composed, measured and recorded here, but nothing is
            sent and no credits are used &mdash; so rows below marked
            <em>simulated</em> did not reach a handset. Set
            <code>SMS_DRIVER</code> to a provider to send for real.
        </span>
    </div>
    @else
    <div style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:12px 18px;margin-bottom:24px;display:flex;align-items:flex-start;gap:12px;">
        <i class="fas fa-tower-broadcast" style="color:var(--success);font-size:1rem;flex-shrink:0;margin-top:2px;"></i>
        <span style="font-size:.85rem;color:var(--text-muted);">
            Sending live through <strong style="color:var(--text);">{{ $driver }}</strong>.
            Every text below was a real send and was billed.
        </span>
    </div>
    @endif

    <!-- SUMMARY -->
    <div class="cards-grid">
        <div class="stat-card success">
            <div class="stat-card-header">
                <div class="stat-label">Reminders Sent</div>
                <div class="stat-icon"><i class="fas fa-paper-plane"></i></div>
            </div>
            <div class="stat-value">{{ number_format($summary['reminders_sent']) }}</div>
            <div class="stat-footer" style="font-size:.74rem;color:var(--text-muted);">
                Email and SMS, counted once each
            </div>
        </div>

        <div class="stat-card primary">
            <div class="stat-card-header">
                <div class="stat-label">Texts Attempted</div>
                <div class="stat-icon"><i class="fas fa-comment-sms"></i></div>
            </div>
            <div class="stat-value">{{ number_format($summary['texts_total']) }}</div>
            <div class="stat-footer" style="font-size:.74rem;color:var(--text-muted);">
                {{ number_format($summary['texts_simulated']) }} simulated
            </div>
        </div>

        <div class="stat-card {{ $summary['texts_failed'] > 0 ? 'warning' : 'success' }}">
            <div class="stat-card-header">
                <div class="stat-label">Texts Failed</div>
                <div class="stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
            <div class="stat-value">{{ number_format($summary['texts_failed']) }}</div>
            <div class="stat-footer" style="font-size:.74rem;color:var(--text-muted);">
                Each one has a reason below
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-card-header">
                <div class="stat-label">Segments Billed</div>
                <div class="stat-icon"><i class="fas fa-coins"></i></div>
            </div>
            <div class="stat-value">{{ number_format($summary['segments_billed']) }}</div>
            {{-- Segments, not messages: one em-dash or peso sign drops a
                 segment from 160 characters to 70, so a template that quietly
                 grew shows up here and nowhere else in the panel. --}}
            <div class="stat-footer" style="font-size:.74rem;color:var(--text-muted);">
                Credits used, not messages
            </div>
        </div>
    </div>

    <!-- REMINDERS -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-bell"></i> Reminders</div>
            <span style="font-size:.76rem;color:var(--text-muted);">
                @if($reminders->isNotEmpty())Newest {{ $reminders->count() }} &middot; @endif both channels
            </span>
        </div>
        <div class="table-wrap">
            <table class="apx-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Customer</th>
                        <th>About</th>
                        <th>Reminder</th>
                        <th>Channel</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reminders as $r)
                <tr>
                    <td style="white-space:nowrap;">
                        {{ ($r->sent_at ?? $r->created_at)->format('M j, Y') }}
                        <div style="font-size:.74rem;color:var(--text-muted);">
                            {{ ($r->sent_at ?? $r->created_at)->format('g:i A') }}
                        </div>
                    </td>
                    <td>
                        <div class="primary-col">{{ $r->user->name ?? 'Deleted customer' }}</div>
                        @if($r->user?->email)
                        <div style="font-size:.74rem;color:var(--text-muted);">{{ $r->user->email }}</div>
                        @endif
                    </td>
                    <td>
                        @if($r->type === \App\Models\Reminder::TYPE_APPOINTMENT)
                            <div>{{ $r->booking->reference_number ?? 'Booking removed' }}</div>
                            <div style="font-size:.74rem;color:var(--text-muted);">
                                {{ $r->vehicle?->display_plate ?? 'No vehicle' }}
                            </div>
                        @else
                            <div>{{ $r->vehicle?->display_plate ?? 'Vehicle removed' }}</div>
                            <div style="font-size:.74rem;color:var(--text-muted);">
                                {{ $r->rule->name ?? 'Rule removed' }}
                            </div>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        {{ $triggerLabels[$r->trigger_key] ?? $r->trigger_key }}
                    </td>
                    <td>
                        <span class="badge {{ $r->channel === 'sms' ? 'badge-inprogress' : 'badge-pending' }}">
                            {{ strtoupper($r->channel) }}
                        </span>
                    </td>
                    <td>
                        @if($r->status === 'sent')
                            <span class="badge badge-confirmed">Sent</span>
                        @else
                            {{-- A claim that has not been marked sent. Usually a
                                 tick in progress; one that persists is a send
                                 that died between claiming and reporting. --}}
                            <span class="badge badge-pending">In flight</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">
                        No reminders have been sent yet.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer-bar">
            <span>
                {{ $reminders->count() }} shown
                @if($summary['reminders_stuck'] > 0)
                    &middot; <strong style="color:var(--warning);">{{ $summary['reminders_stuck'] }} in flight</strong>
                @endif
            </span>
        </div>
    </div>

    <!-- SMS ACTIVITY -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-comment-sms"></i> SMS Activity</div>
            <span style="font-size:.76rem;color:var(--text-muted);">
                @if($texts->isNotEmpty())Newest {{ $texts->count() }} &middot; @endif every attempt, including failures
            </span>
        </div>
        <div class="table-wrap">
            <table class="apx-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>To</th>
                        <th>About</th>
                        <th>Trigger</th>
                        <th>Message</th>
                        <th style="text-align:center;">Seg</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($texts as $t)
                @php $vehicle = $t->subjectVehicle(); @endphp
                <tr>
                    <td style="white-space:nowrap;">
                        {{ $t->created_at->format('M j, Y') }}
                        <div style="font-size:.74rem;color:var(--text-muted);">
                            {{ $t->created_at->format('g:i A') }}
                        </div>
                    </td>
                    <td style="white-space:nowrap;">
                        {{ $t->recipient ?? '—' }}
                        @if($t->booking?->customer)
                        <div style="font-size:.74rem;color:var(--text-muted);">{{ $t->booking->customer->name }}</div>
                        @endif
                    </td>
                    <td>
                        @if($t->booking)
                            <div>{{ $t->booking->reference_number }}</div>
                        @elseif($vehicle)
                            <div>{{ $vehicle->display_plate }}</div>
                        @else
                            <span style="color:var(--text-muted);font-style:italic;">—</span>
                        @endif
                        @if($t->booking && $vehicle)
                        <div style="font-size:.74rem;color:var(--text-muted);">{{ $vehicle->display_plate }}</div>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        {{ $triggerLabels[$t->trigger] ?? ($t->trigger ?? '—') }}
                    </td>
                    <td style="min-width:240px;max-width:380px;">
                        <div style="font-size:.8rem;color:var(--text);">{{ Str::limit($t->content, 120) }}</div>
                        @if($t->fail_reason)
                        {{-- The whole point of the table. "The customer says
                             they never got a reminder" is answered here. --}}
                        <div style="font-size:.74rem;color:var(--red);margin-top:4px;">
                            <i class="fas fa-circle-exclamation"></i> {{ $t->fail_reason }}
                        </div>
                        @elseif($t->reference_id)
                        <div style="font-size:.74rem;color:var(--text-muted);margin-top:3px;">
                            ref {{ $t->reference_id }}
                        </div>
                        @endif
                    </td>
                    <td style="text-align:center;font-variant-numeric:tabular-nums;">{{ $t->segments }}</td>
                    <td>
                        @if($t->simulated())
                            <span class="badge badge-inprogress">Simulated</span>
                        @elseif($t->succeeded())
                            <span class="badge badge-confirmed">{{ ucfirst($t->status) }}</span>
                        @else
                            <span class="badge badge-cancelled">{{ str_replace('_', ' ', $t->status) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">
                        No SMS has been attempted yet.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer-bar">
            <span>{{ $texts->count() }} shown</span>
        </div>
    </div>

    {{-- Stated rather than left to be discovered. A failed reminder claim is
         deleted so a later run can retry it — a row left behind holding the
         unique key would silently cancel that reminder for good — which means
         the Reminders table above shows successes, not attempts. For SMS the
         attempt survives in SMS Activity; for email it survives only in the
         application log. --}}
    <div style="margin-top:20px;padding:12px 18px;background:var(--surface);border:1px solid var(--border);border-radius:8px;display:flex;align-items:flex-start;gap:10px;">
        <i class="fas fa-circle-question" style="color:var(--text-muted);font-size:.9rem;flex-shrink:0;margin-top:2px;"></i>
        <span style="font-size:.78rem;color:var(--text-muted);line-height:1.6;">
            <strong style="color:var(--text);">Why a failed email is not listed.</strong>
            A reminder that fails gives up its claim so a later run can retry it,
            which removes the row &mdash; otherwise one failure would cancel that
            reminder permanently. Failed <em>texts</em> are still listed above,
            because every attempt writes its own record. Failed <em>emails</em>
            appear only in the application log.
        </span>
    </div>

@endsection
