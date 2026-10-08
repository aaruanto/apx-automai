/**
 * Keeps a dashboard current without the user pressing refresh.
 *
 * Polls a read-only JSON endpoint and hands the payload to an apply callback
 * that patches the page in place. Deliberately not a page reload: that loses
 * scroll position and anything half-typed.
 *
 * Usage:
 *   ApxLiveRefresh.start({
 *       url: '/admin/dashboard/live',
 *       apply: data => { ... },           // patch the DOM
 *       stamp: '#liveUpdated',            // optional "updated" label
 *       interval: 25000                   // optional, default 25s
 *   });
 *   ApxLiveRefresh.refreshNow();          // after an action changes data
 */
(function (window, document) {
    'use strict';

    var DEFAULT_INTERVAL = 25000;

    var config  = null;
    var timer   = null;
    var lastOk  = null;     // when the last successful poll landed
    var inFlight = false;

    function schedule() {
        clearTimeout(timer);
        if (!config) return;
        timer = setTimeout(poll, config.interval);
    }

    function stampEl() {
        return config && config.stamp ? document.querySelector(config.stamp) : null;
    }

    function relative(date) {
        var secs = Math.round((Date.now() - date.getTime()) / 1000);
        if (secs < 10)  return 'just now';
        if (secs < 60)  return secs + 's ago';
        var mins = Math.round(secs / 60);
        if (mins < 60)  return mins + ' min ago';
        return Math.round(mins / 60) + 'h ago';
    }

    function paintStamp() {
        var el = stampEl();
        if (!el) return;
        el.textContent = lastOk ? 'Updated ' + relative(lastOk) : '';
    }

    function poll() {
        if (!config || inFlight) return;

        // Nothing to update if nobody is looking; resumes on visibilitychange.
        if (document.visibilityState === 'hidden') {
            schedule();
            return;
        }

        inFlight = true;
        var btn = config.button ? document.querySelector(config.button) : null;
        if (btn) btn.classList.add('is-refreshing');

        fetch(config.url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                try {
                    config.apply(data);
                } catch (e) {
                    // A broken apply must not stop future polls.
                    console.error('live-refresh apply failed:', e);
                }
                lastOk = new Date();
                paintStamp();
            })
            .catch(function (err) {
                // Fail quietly: the last good data stays on screen and the
                // next tick tries again. A dashboard that blanks itself or
                // shows a stack trace is worse than one that is 25s stale.
                console.warn('live-refresh poll failed:', err.message);
            })
            .then(function () {
                inFlight = false;
                if (btn) btn.classList.remove('is-refreshing');
                schedule();
            });
    }

    function start(options) {
        config = {
            url:      options.url,
            apply:    options.apply,
            stamp:    options.stamp || null,
            button:   options.button || null,
            interval: options.interval || DEFAULT_INTERVAL
        };

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                // Catch up immediately rather than waiting out the tick that
                // elapsed while the tab was in the background.
                refreshNow();
            }
        });

        // Keep the relative timestamp honest between polls.
        setInterval(paintStamp, 15000);

        poll();
    }

    /** Poll right away, e.g. straight after confirming or cancelling. */
    function refreshNow() {
        if (!config) return;
        clearTimeout(timer);
        poll();
    }

    window.ApxLiveRefresh = { start: start, refreshNow: refreshNow };
})(window, document);
