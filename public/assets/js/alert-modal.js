/**
 * Shared alert/confirm modal controller. Namespaced as window.ApxAlertModal
 * deliberately distinct from admin's openModal(id)/closeModal(id) globals
 * and the customer dashboard's own unrelated openModal(svcId) service-picker
 * modal — no collision with either, both stay untouched.
 *
 * Requires <x-alert-modal id="apx-alert-modal" /> to be present on the page
 * (defaults to that id). Usage:
 *   ApxAlertModal.show({ variant: 'error', title: 'Oops', message: '...' });
 *   ApxAlertModal.show({
 *     variant: 'confirm', title: 'Delete vehicle?', message: '...',
 *     confirmText: 'Delete', cancelText: 'Cancel',
 *     onConfirm: () => { ... }
 *   });
 */
(function (window, document) {
    'use strict';

    var VARIANTS = {
        success: { icon: 'fa-circle-check', color: 'var(--success)', bg: 'rgba(34,197,94,0.12)' },
        error:   { icon: 'fa-circle-exclamation', color: 'var(--red)', bg: 'var(--red-glow, rgba(232,25,44,0.15))' },
        info:    { icon: 'fa-circle-info', color: 'var(--info)', bg: 'var(--info-glow, rgba(59,130,246,0.15))' },
        confirm: { icon: 'fa-triangle-exclamation', color: 'var(--warning)', bg: 'rgba(245,158,11,0.12)' }
    };

    var DEFAULT_ID = 'apx-alert-modal';
    var activeOnConfirm = null;
    var activeOnCancel = null;

    function el(id) { return document.getElementById(id || DEFAULT_ID); }

    function isDismissable(overlay) {
        return overlay.getAttribute('data-dismissable') !== 'false';
    }

    function hide(id) {
        var overlay = el(id);
        if (!overlay) return;
        overlay.classList.remove('open');
        overlay.removeAttribute('data-dismissable');
        activeOnConfirm = null;
        activeOnCancel = null;
    }

    function buildButton(text, style, onClick) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'apx-alert-btn apx-alert-btn--' + style;
        btn.textContent = text;
        btn.addEventListener('click', onClick);
        return btn;
    }

    function show(opts) {
        opts = opts || {};
        var id = opts.id || DEFAULT_ID;
        var overlay = el(id);
        if (!overlay) return;

        var variant = VARIANTS[opts.variant] ? opts.variant : 'info';
        var cfg = VARIANTS[variant];

        overlay.querySelector('.apx-alert-title').textContent = opts.title || '';
        overlay.querySelector('.apx-alert-message').textContent = opts.message || '';

        var iconWrap = overlay.querySelector('.apx-alert-icon');
        iconWrap.style.color = cfg.color;
        iconWrap.style.background = cfg.bg;
        iconWrap.style.width = '36px';
        iconWrap.style.height = '36px';
        iconWrap.style.borderRadius = '50%';
        iconWrap.style.display = 'flex';
        iconWrap.style.alignItems = 'center';
        iconWrap.style.justifyContent = 'center';
        iconWrap.style.flexShrink = '0';
        iconWrap.querySelector('i').className = 'fas ' + cfg.icon;

        var footer = overlay.querySelector('.apx-alert-footer');
        footer.innerHTML = '';

        activeOnConfirm = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
        activeOnCancel = typeof opts.onCancel === 'function' ? opts.onCancel : null;

        if (variant === 'confirm') {
            overlay.setAttribute('data-dismissable', 'false');
            footer.appendChild(buildButton(opts.cancelText || 'Cancel', 'ghost', function () {
                if (activeOnCancel) activeOnCancel();
                hide(id);
            }));
            footer.appendChild(buildButton(opts.confirmText || 'Confirm', 'danger', function () {
                if (activeOnConfirm) activeOnConfirm();
                hide(id);
            }));
        } else {
            overlay.removeAttribute('data-dismissable');
            footer.appendChild(buildButton(opts.confirmText || 'OK', 'primary', function () {
                if (activeOnConfirm) activeOnConfirm();
                hide(id);
            }));
        }

        overlay.classList.add('open');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            if (!overlay.querySelector('.apx-alert-title')) return; // not an alert-modal instance

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay && isDismissable(overlay)) hide(overlay.id);
            });
            var closeBtn = overlay.querySelector('[data-apx-alert-close]');
            if (closeBtn) closeBtn.addEventListener('click', function () {
                if (isDismissable(overlay)) hide(overlay.id);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.modal-overlay.open').forEach(function (overlay) {
                if (overlay.querySelector('.apx-alert-title') && isDismissable(overlay)) hide(overlay.id);
            });
        });
    });

    window.ApxAlertModal = { show: show, hide: hide };
})(window, document);
