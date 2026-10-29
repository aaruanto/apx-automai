@props([
    'id' => 'avp',
    // Shown inside the calendar while no service is chosen. A prop because
    // the three callers put their service control in different places: the
    // admin create screen has it in a section far below the calendar, while
    // the guest modal and the dashboard choose a service before the calendar
    // is even on screen. The default says nothing about where to look.
    'emptyHint' => 'Choose a service first to see availability.',
])
{{--
    Two-panel availability picker: a month calendar (green = has open
    capacity, red = unavailable) on the left, a time-slot list ("Available:
    N" / "Unavailable") on the right. Purely presentational — all the logic
    lives in /assets/js/availability-picker.js. A page wires it up with:

        const picker = new AvailabilityPicker({
            root: '#{{ $id }}',
            dateInput: document.getElementById('...'),
            timeInput: document.getElementById('...'),
            getServiceIds: () => [...],
        });
        picker.refresh();

    and calls picker.refresh() again whenever the chosen service(s) change.
--}}
<div class="avp" id="{{ $id }}">
    <div class="avp-cal-panel">
        <div class="avp-cal-header">
            <button type="button" class="avp-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>
            <span class="avp-cal-title">&nbsp;</span>
            <button type="button" class="avp-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>
        </div>
        <div class="avp-weekdays">
            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
        </div>
        <div class="avp-days"></div>
        {{-- The grid is emptied while no service is chosen, and an empty grid
             under a month title and weekday headings reads as a broken
             calendar. The explanation used to sit only in the slots panel
             beside it, which is not where anyone looks when the calendar
             itself is blank. --}}
        <div class="avp-cal-empty" hidden>{{ $emptyHint }}</div>
        <div class="avp-legend">
            <span class="avp-legend-item"><i class="avp-dot avp-dot-open"></i> Available</span>
            <span class="avp-legend-item"><i class="avp-dot avp-dot-full"></i> Unavailable</span>
        </div>
    </div>
    <div class="avp-slots-panel">
        <div class="avp-slots-header">Select a date</div>
        <div class="avp-slots-list">
            <p class="avp-slots-empty">Pick a green day on the calendar to see open times.</p>
        </div>
    </div>
</div>
