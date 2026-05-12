@extends('layouts.admin')

@section('title', 'Loyalty Members')

@section('content')

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Loyalty <span>Members</span></h1>
            <ol class="breadcrumb">
                <li>Customers</li>
                <li class="active">Loyalty Members</li>
            </ol>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> All Customers
        </a>
    </div>

    <!-- TIER SUMMARY CARDS -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">
        @php
            $tierConfig = [
                'gold'   => ['label'=>'Gold',  'color'=>'#F59E0B','bg'=>'rgba(245,158,11,0.12)','border'=>'rgba(245,158,11,0.3)','icon'=>'fa-star',           'desc'=>'10+ bookings'],
                'silver' => ['label'=>'Silver','color'=>'#94A3B8','bg'=>'rgba(148,163,184,0.12)','border'=>'rgba(148,163,184,0.3)','icon'=>'fa-star-half-stroke','desc'=>'5–9 bookings'],
                'bronze' => ['label'=>'Bronze','color'=>'#CD7C4F','bg'=>'rgba(205,124,79,0.12)', 'border'=>'rgba(205,124,79,0.3)', 'icon'=>'fa-circle',          'desc'=>'1–4 bookings'],
            ];
        @endphp
        @foreach($tierConfig as $key => $tc)
        @php $count = isset($grouped[$key]) ? count($grouped[$key]) : 0; @endphp
        <div style="background:var(--surface);border:1px solid {{ $tc['border'] }};border-radius:10px;padding:20px;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-10px;right:-10px;font-size:4rem;opacity:.05;color:{{ $tc['color'] }};"><i class="fas {{ $tc['icon'] }}"></i></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:{{ $tc['bg'] }};display:flex;align-items:center;justify-content:center;color:{{ $tc['color'] }};">
                    <i class="fas {{ $tc['icon'] }}"></i>
                </div>
                <div>
                    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:800;color:{{ $tc['color'] }};">{{ $tc['label'] }}</div>
                    <div style="font-size:.73rem;color:var(--text-muted);">{{ $tc['desc'] }}</div>
                </div>
            </div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.2rem;font-weight:800;line-height:1;">{{ $count }}</div>
            <div style="font-size:.73rem;color:var(--text-muted);">members</div>
        </div>
        @endforeach
    </div>

    <!-- TIER SECTIONS -->
    @foreach($tierConfig as $key => $tc)
    @if(!empty($grouped[$key]))
    @php
        $members = $grouped[$key];
        $maxBookings = $members->max('bookings_count') ?: 1;
        $nextThreshold = ['bronze'=>5,'silver'=>10,'gold'=>null];
    @endphp
    <div style="margin-bottom:32px;">

        <!-- Tier header -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <div style="width:38px;height:38px;border-radius:10px;background:{{ $tc['bg'] }};display:flex;align-items:center;justify-content:center;color:{{ $tc['color'] }};font-size:1rem;">
                <i class="fas {{ $tc['icon'] }}"></i>
            </div>
            <div>
                <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.3rem;font-weight:800;color:{{ $tc['color'] }};">{{ $tc['label'] }} Members</div>
                <div style="font-size:.78rem;color:var(--text-muted);">{{ $tc['desc'] }}</div>
            </div>
            <div style="margin-left:auto;font-family:'Barlow Condensed',sans-serif;font-size:1.6rem;font-weight:800;opacity:.4;">{{ count($members) }}</div>
        </div>

        <!-- Members grid -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">
            @foreach($members as $m)
            @php
                $bookings = $m->bookings_count ?? 0;
                $pct      = $maxBookings > 0 ? round($bookings / $maxBookings * 100) : 0;
                $needed   = $nextThreshold[$key] ? max(0, $nextThreshold[$key] - $bookings) : 0;
                $initials = strtoupper(substr($m->name,0,1) . substr(strrchr($m->name,' '),1,1));
            @endphp
            <div style="background:var(--surface);border:1px solid {{ $tc['border'] }};border-radius:10px;padding:16px;
                        display:flex;align-items:center;gap:14px;
                        transition:transform .15s,box-shadow .15s;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">

                <!-- Avatar -->
                <div style="width:42px;height:42px;border-radius:50%;background:{{ $tc['bg'] }};color:{{ $tc['color'] }};
                            display:flex;align-items:center;justify-content:center;
                            font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:.9rem;flex-shrink:0;">
                    {{ $initials }}
                </div>

                <!-- Info -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $m->name }}</div>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1rem;flex-shrink:0;color:{{ $tc['color'] }};">{{ $bookings }}x</span>
                    </div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:1px;">
                        {{ $m->phone }} &middot; {{ $m->vehicle->plate_number ?? '—' }}
                    </div>
                    <!-- Progress bar -->
                    <div style="height:4px;background:var(--surface-3);border-radius:2px;margin-top:6px;">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $tc['color'] }};border-radius:2px;transition:width .3s;"></div>
                    </div>
                    @if($needed > 0)
                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:3px;">{{ $needed }} more to next tier</div>
                    @else
                    <div style="font-size:.7rem;color:{{ $tc['color'] }};margin-top:3px;"><i class="fas fa-trophy" style="font-size:.65rem;"></i> Top tier</div>
                    @endif
                </div>

                <!-- Actions -->
                <div style="flex-shrink:0;">
                    <a href="{{ route('admin.customers.edit', $m->id) }}" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                        <i class="fas fa-pen"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach

@endsection