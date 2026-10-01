@props(['id' => 'avp'])
{{--
    Two-panel availability picker: a month calendar (green = has open
    capacity, red = fully booked) on the left, a time-slot list ("Available:
    N" / "Fully Booked") on the right. Purely presentational — all the logic
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
        <div class="avp-legend">
            <span class="avp-legend-item"><i class="avp-dot avp-dot-open"></i> Available</span>
            <span class="avp-legend-item"><i class="avp-dot avp-dot-full"></i> Fully booked</span>
        </div>
    </div>
    <div class="avp-slots-panel">
        <div class="avp-slots-header">Select a date</div>
        <div class="avp-slots-list">
            <p class="avp-slots-empty">Pick a green day on the calendar to see open times.</p>
        </div>
    </div>
</div>
