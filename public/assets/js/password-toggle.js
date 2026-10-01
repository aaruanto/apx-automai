/**
 * Accessible show/hide toggle for every password field in the app.
 *
 * Replaces the two copy-pasted togglePw() handlers that used to live inline in
 * the login and register views. Those walked the DOM with previousElementSibling,
 * which broke as soon as anything else was inserted next to the input, and they
 * looked for an <i> element that Font Awesome's JS had already swapped for an
 * <svg>. This finds the input by relationship to its own wrapper instead, and
 * accepts either icon element.
 *
 * Auto-discovers password inputs on load — pages don't need to opt in or add
 * markup. Add data-no-pw-toggle to an input to skip it.
 */
(function (window, document) {
    'use strict';

    var EYE = 'fa-eye';
    var EYE_OFF = 'fa-eye-slash';

    function wrapperFor(input) {
        var existing = input.closest('.input-wrap, .pw-field');
        if (existing) return existing;

        var span = document.createElement('span');
        span.className = 'pw-field';
        input.parentNode.insertBefore(span, input);
        span.appendChild(input);
        return span;
    }

    function apply(btn, input, visible) {
        input.type = visible ? 'text' : 'password';
        btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
        btn.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        btn.title = visible ? 'Hide password' : 'Show password';

        // Font Awesome rewrites <i> into <svg>, so accept whichever is present.
        var icon = btn.querySelector('i, svg');
        if (icon) {
            icon.classList.remove(visible ? EYE : EYE_OFF);
            icon.classList.add(visible ? EYE_OFF : EYE);
        }
    }

    function enhance(input) {
        if (input.dataset.pwToggleReady || input.hasAttribute('data-no-pw-toggle')) return;
        input.dataset.pwToggleReady = '1';

        var wrap = wrapperFor(input);
        var btn = wrap.querySelector('.pw-toggle');

        if (!btn) {
            btn = document.createElement('button');
            btn.className = 'pw-toggle';
            btn.innerHTML = '<i class="fas ' + EYE + '"></i>';
            wrap.appendChild(btn);
        }

        // type=button so it never submits the form it sits in; drop the old
        // inline onclick and the tabindex=-1 that kept it off the tab order.
        btn.type = 'button';
        btn.removeAttribute('onclick');
        btn.removeAttribute('tabindex');

        apply(btn, input, false);

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            apply(btn, input, input.type === 'password');
        });
    }

    function init() {
        document.querySelectorAll('input[type="password"]').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.PasswordToggle = { enhance: enhance, refresh: init };
})(window, document);
