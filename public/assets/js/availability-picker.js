/**
 * Two-panel availability picker (month calendar + time-slot list).
 *
 * Usage:
 *   const picker = new AvailabilityPicker({
 *       root: '#avp-guest',              // the <div class="avp"> container
 *       dateInput: document.getElementById('apx-f-date'), // existing field to fill
 *       timeInput: document.getElementById('apx-f-time'), // existing field to fill
 *       endpoint: '/booking/availability', // optional, this is the default
 *       getServiceIds: () => [12],         // required — current service id(s)
 *       onSelect: (date, time) => {},      // optional
 *   });
 *   picker.refresh(); // call again whenever the selected service(s) change
 */
(function (global) {
    'use strict';

    function pad(n) { return String(n).padStart(2, '0'); }

    function AvailabilityPicker(opts) {
        this.root = typeof opts.root === 'string' ? document.querySelector(opts.root) : opts.root;
        this.dateInput = opts.dateInput || null;
        this.timeInput = opts.timeInput || null;
        this.endpoint = opts.endpoint || '/booking/availability';
        this.getServiceIds = opts.getServiceIds;
        this.onSelect = opts.onSelect || function () {};

        var today = new Date();
        this.year = today.getFullYear();
        this.month = today.getMonth() + 1; // 1-12
        this.todayStr = today.getFullYear() + '-' + pad(today.getMonth() + 1) + '-' + pad(today.getDate());
        this.selectedDate = null;
        this.selectedTime = null;
        this.requestSeq = 0;

        this.els = {
            title: this.root.querySelector('.avp-cal-title'),
            days: this.root.querySelector('.avp-days'),
            prevBtn: this.root.querySelector('[data-nav="prev"]'),
            nextBtn: this.root.querySelector('[data-nav="next"]'),
            slotsHeader: this.root.querySelector('.avp-slots-header'),
            slotsList: this.root.querySelector('.avp-slots-list'),
        };

        this.els.prevBtn.addEventListener('click', this._changeMonth.bind(this, -1));
        this.els.nextBtn.addEventListener('click', this._changeMonth.bind(this, 1));
    }

    AvailabilityPicker.prototype._changeMonth = function (delta) {
        this.month += delta;
        if (this.month < 1) { this.month = 12; this.year--; }
        if (this.month > 12) { this.month = 1; this.year++; }
        this._loadMonth();
    };

    AvailabilityPicker.prototype.refresh = function () {
        this._loadMonth();
        if (this.selectedDate) this._loadDay(this.selectedDate);
    };

    /** Clear any date/time selection and jump back to the current month — call this when reopening a booking modal. */
    AvailabilityPicker.prototype.reset = function () {
        var today = new Date();
        this.year = today.getFullYear();
        this.month = today.getMonth() + 1;
        this.selectedDate = null;
        this.selectedTime = null;
        if (this.dateInput) this.dateInput.value = '';
        if (this.timeInput) this.timeInput.value = '';
        this.els.slotsHeader.textContent = 'Select a date';
        this.els.slotsList.innerHTML = '<p class="avp-slots-empty">Pick a green day on the calendar to see open times.</p>';
        this.refresh();
    };

    AvailabilityPicker.prototype._serviceIds = function () {
        var ids = (this.getServiceIds && this.getServiceIds()) || [];
        return Array.isArray(ids) ? ids.filter(Boolean) : [ids].filter(Boolean);
    };

    AvailabilityPicker.prototype._fetch = function (params) {
        var ids = this._serviceIds();
        if (!ids.length) return Promise.resolve(null);

        var qs = new URLSearchParams();
        ids.forEach(function (id) { qs.append('service_ids[]', id); });
        Object.keys(params).forEach(function (k) { qs.append(k, params[k]); });

        var seq = ++this.requestSeq;
        var self = this;

        return fetch(this.endpoint + '?' + qs.toString(), { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                // Drop stale responses from a superseded request (fast month/service switching).
                if (seq !== self.requestSeq) return null;
                return data;
            })
            .catch(function () { return null; });
    };

    AvailabilityPicker.prototype._loadMonth = function () {
        var self = this;
        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        this.els.title.textContent = monthNames[this.month - 1] + ' ' + this.year;

        var isCurrentMonth = (this.year === new Date().getFullYear() && this.month === (new Date().getMonth() + 1));
        this.els.prevBtn.disabled = isCurrentMonth;

        if (!this._serviceIds().length) {
            this.els.days.innerHTML = '';
            this.els.slotsHeader.textContent = 'Select a date';
            this.els.slotsList.innerHTML = '<p class="avp-slots-empty">Choose a service first to see availability.</p>';
            return;
        }

        this._fetch({ year: this.year, month: this.month }).then(function (data) {
            if (!data) return;
            self._renderMonth(data.days || []);
        });
    };

    AvailabilityPicker.prototype._renderMonth = function (days) {
        var self = this;
        this.els.days.innerHTML = '';

        if (!days.length) return;

        var firstDow = new Date(days[0].date + 'T00:00:00').getDay(); // 0 = Sunday
        for (var i = 0; i < firstDow; i++) {
            var blank = document.createElement('div');
            blank.className = 'avp-day avp-day-blank';
            this.els.days.appendChild(blank);
        }

        days.forEach(function (day) {
            var cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'avp-day avp-day-' + day.status;
            cell.textContent = String(parseInt(day.date.split('-')[2], 10));
            cell.disabled = !day.bookable;

            if (day.date === self.selectedDate) cell.classList.add('avp-day-selected');

            if (day.bookable) {
                cell.addEventListener('click', function () {
                    self.selectedDate = day.date;
                    self.selectedTime = null;
                    self.root.querySelectorAll('.avp-day').forEach(function (d) { d.classList.remove('avp-day-selected'); });
                    cell.classList.add('avp-day-selected');
                    self._loadDay(day.date);
                });
            }

            self.els.days.appendChild(cell);
        });
    };

    AvailabilityPicker.prototype._loadDay = function (dateStr) {
        var self = this;
        var d = new Date(dateStr + 'T00:00:00');
        var label = d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
        this.els.slotsHeader.textContent = label;
        this.els.slotsList.innerHTML = '<p class="avp-slots-empty">Loading times…</p>';

        this._fetch({ date: dateStr }).then(function (data) {
            if (!data) {
                self.els.slotsList.innerHTML = '<p class="avp-slots-empty">Could not load times. Please try again.</p>';
                return;
            }
            self._renderSlots(data.slots || []);
        });
    };

    AvailabilityPicker.prototype._renderSlots = function (slots) {
        var self = this;
        this.els.slotsList.innerHTML = '';

        if (!slots.length) {
            this.els.slotsList.innerHTML = '<p class="avp-slots-empty">No time slots available this day.</p>';
            return;
        }

        slots.forEach(function (slot) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'avp-slot';
            btn.disabled = !slot.bookable;
            if (slot.start === self.selectedTime) btn.classList.add('avp-slot-selected');

            var label = document.createElement('span');
            label.textContent = slot.label;
            var count = document.createElement('span');
            count.className = 'avp-slot-count';
            count.textContent = slot.bookable ? ('Available: ' + slot.remaining) : 'Fully Booked';

            btn.appendChild(label);
            btn.appendChild(count);

            if (slot.bookable) {
                btn.addEventListener('click', function () {
                    self.selectedTime = slot.start;
                    self.root.querySelectorAll('.avp-slot').forEach(function (s) { s.classList.remove('avp-slot-selected'); });
                    btn.classList.add('avp-slot-selected');

                    if (self.dateInput) self.dateInput.value = self.selectedDate;
                    if (self.timeInput) self.timeInput.value = slot.start;

                    self.onSelect(self.selectedDate, slot.start);
                });
            }

            self.els.slotsList.appendChild(btn);
        });
    };

    global.AvailabilityPicker = AvailabilityPicker;
})(window);
