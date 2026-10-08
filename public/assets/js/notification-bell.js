/**
 * Behaviour for <x-notification-bell />.
 *
 * The badge is refreshed from one of two places: the dashboards hand it their
 * poll's unread count via ApxBell.setCount(), so there is no second poller
 * competing with them, and pages without a poll fetch the feed themselves when
 * the dropdown is opened.
 */
(function (window, document) {
    'use strict';

    var FEED_URL = '/notifications/feed';
    var loaded   = false;

    function el(id) { return document.getElementById(id); }

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    /** Update just the badge. Called by the dashboard polls. */
    function setCount(n) {
        var badge = el('apxBellCount');
        if (!badge) return;
        n = Number(n) || 0;
        badge.textContent = n > 99 ? '99+' : n;
        badge.style.display = n > 0 ? '' : 'none';
    }

    function render(items) {
        var list = el('apxBellList');
        if (!list) return;

        if (!items.length) {
            list.innerHTML = '<div style="padding:22px;text-align:center;color:var(--text-muted,#888);font-size:.8rem;">'
                + 'Nothing yet.</div>';
            return;
        }

        list.innerHTML = items.map(function (n) {
            return '<button type="button" class="apx-bell-item" data-id="' + esc(n.id) + '"'
                + ' data-link="' + esc(n.link || '') + '"'
                + ' style="display:flex;gap:10px;width:100%;text-align:left;padding:11px 14px;border:none;'
                + 'border-bottom:1px solid var(--border,#eee);cursor:pointer;'
                + 'background:' + (n.unread ? 'var(--red-glow,rgba(232,25,44,.07))' : 'transparent') + ';">'
                + '<i class="fas ' + esc(n.icon) + '" style="margin-top:2px;color:var(--red);width:15px;"></i>'
                + '<span style="flex:1;min-width:0;">'
                + '<span style="display:block;font-size:.8rem;font-weight:' + (n.unread ? '700' : '600') + ';">'
                + esc(n.title) + '</span>'
                + '<span style="display:block;font-size:.76rem;color:var(--text-muted,#888);margin-top:1px;">'
                + esc(n.body) + '</span>'
                + '<span style="display:block;font-size:.68rem;color:var(--text-muted,#aaa);margin-top:3px;">'
                + esc(n.ago) + '</span>'
                + '</span></button>';
        }).join('');

        list.querySelectorAll('.apx-bell-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id   = btn.dataset.id;
                var link = btn.dataset.link;

                fetch('/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { setCount(d.unread); })
                    // Following the link matters more than recording the read,
                    // so navigate either way.
                    .catch(function () {})
                    .then(function () { if (link) window.location.href = link; });
            });
        });
    }

    function loadFeed() {
        return fetch(FEED_URL, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                setCount(d.unread);
                render(d.items || []);
                loaded = true;
            })
            .catch(function () {
                var list = el('apxBellList');
                if (list) {
                    list.innerHTML = '<div style="padding:18px;text-align:center;color:var(--text-muted,#888);'
                        + 'font-size:.8rem;">Could not load notifications.</div>';
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn   = el('apxBellBtn');
        var panel = el('apxBellPanel');
        if (!btn || !panel) return;

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = panel.style.display !== 'none';
            panel.style.display = open ? 'none' : 'block';
            // Fetched on first open rather than on page load: most visits
            // never open it.
            if (!open) loadFeed();
        });

        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target) && e.target !== btn) panel.style.display = 'none';
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') panel.style.display = 'none';
        });

        var readAll = el('apxBellReadAll');
        if (readAll) {
            readAll.addEventListener('click', function (e) {
                e.stopPropagation();
                fetch('/notifications/read-all', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json(); })
                    .then(function () { setCount(0); loadFeed(); })
                    .catch(function () {});
            });
        }

        // Pages with no dashboard poll still need a correct badge on arrival.
        if (!window.ApxLiveRefresh) loadFeed();
    });

    window.ApxBell = { setCount: setCount, reload: loadFeed };
})(window, document);
