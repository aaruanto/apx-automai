/**
 * Shared admin booking status actions.
 *
 * Both admin/bookings/index.blade.php and admin/bookings/schedule.blade.php
 * had their own copy of this fetch, and both copies shared the same bug: a
 * bare `.then(r => r.json())` with no r.ok check. A refused transition comes
 * back as 422 (and a CSRF timeout as a 419 HTML page), so r.json() threw and
 * the rejection was only console.error'd — staff saw nothing whatsoever and
 * the button looked broken. Centralised here so there is one implementation
 * to get right.
 *
 * Requires <x-alert-modal /> and alert-modal.js on the page, plus a
 * <meta name="csrf-token"> in the layout head.
 *
 * Usage:
 *   ApxBookingActions.markArrived(id, btn, function (data) { ...update DOM... });
 */
(function (window, document) {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function alertModal(opts) {
        if (window.ApxAlertModal) {
            window.ApxAlertModal.show(opts);
            return;
        }
        // The modal is layout-provided; if a page somehow lacks it, the user
        // must still be told rather than the message vanishing into the console.
        window.alert(opts.title + '\n\n' + opts.message);
    }

    /**
     * Reads the body once as text, then tries JSON. Error responses from
     * Laravel may be HTML (419 session expiry, 500 pages), and those must
     * surface as a readable message instead of a parse exception.
     */
    function parse(response) {
        return response.text().then(function (body) {
            var data = null;
            try { data = JSON.parse(body); } catch (e) { /* not JSON */ }
            return { ok: response.ok, status: response.status, data: data };
        });
    }

    function failureMessage(result) {
        if (result.data && result.data.message) return result.data.message;
        if (result.status === 419) return 'Your session expired. Reload the page and sign in again.';
        if (result.status === 403) return 'You do not have permission to perform this action.';
        if (result.status === 404) return 'This booking no longer exists. Refresh the page.';
        return 'Something went wrong on the server (error ' + result.status + '). Please try again.';
    }

    function markArrived(id, btn, onSuccess) {
        alertModal({
            variant: 'confirm',
            title: 'Start service?',
            message: 'Mark this customer as arrived and start the service. This sets the booking to In Progress.',
            confirmText: 'Start service',
            cancelText: 'Cancel',
            onConfirm: function () { send(id, btn, onSuccess); }
        });
    }

    function send(id, btn, onSuccess) {
        // Guard the double-click: the server re-checks too, but there's no
        // reason to let a second request leave at all.
        if (btn) {
            if (btn.disabled) return;
            btn.disabled = true;
            btn.style.opacity = '.55';
        }

        function release() {
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '';
            }
        }

        fetch('/admin/bookings/' + id + '/arrive', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            }
        })
            .then(parse)
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    release();
                    alertModal({
                        variant: 'error',
                        title: 'Could not start service',
                        message: failureMessage(result)
                    });
                    return;
                }

                if (typeof onSuccess === 'function') onSuccess(result.data);

                alertModal({
                    variant: 'success',
                    title: 'Service started',
                    message: result.data.message || 'The booking is now In Progress.'
                });
            })
            .catch(function (err) {
                release();
                console.error('Mark arrived failed:', err);
                alertModal({
                    variant: 'error',
                    title: 'Could not start service',
                    message: 'Could not reach the server. Check your connection and try again.'
                });
            });
    }

    window.ApxBookingActions = { markArrived: markArrived };
})(window, document);
