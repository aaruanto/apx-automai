/**
 * Accessible show/hide toggle for every password field in the app.
 *
 * Replaces the two copy-pasted togglePw() handlers that used to live inline in
 * the login and register views. Those walked the DOM with previousElementSibling,
 * which broke as soon as anything else was inserted next to the input. This
 * finds the input by relationship to its own wrapper instead.
 *
 * Auto-discovers password inputs on load — pages don't need to opt in or add
 * markup. Add data-no-pw-toggle to an input to skip it.
 */
(function (window, document) {
    'use strict';

    var EYE = 'fa-eye';
    var EYE_OFF = 'fa-eye-slash';

    // Every field this script has enhanced, so a click on one eye can reach
    // the others it is grouped with.
    var enhanced = [];

    /**
     * Which fields move together.
     *
     * Opt-in, through data-pw-group on the input. A new password and its
     * confirmation are the same secret typed twice, so checking one should
     * show both — otherwise the user clicks two eyes to read back one value.
     *
     * Nothing is grouped unless it says so, which is what keeps the current
     * password on a change-password form independent. Revealing a new password
     * must never also reveal the existing one: they are different secrets, and
     * someone shoulder-surfing a "did I type that right" glance would get the
     * live credential for free.
     *
     * Scoped to the form as well as the name, so two forms on one page using
     * the same group name do not reach into each other.
     */
    function groupKeyFor(input) {
        var name = input.dataset.pwGroup;
        if (!name) return null;
        return { form: input.form || input.closest('form') || document, name: name };
    }

    function peersOf(input) {
        var key = groupKeyFor(input);
        if (!key) return [input];

        return enhanced.filter(function (entry) {
            var other = groupKeyFor(entry.input);
            return other && other.name === key.name && other.form === key.form;
        }).map(function (entry) { return entry.input; });
    }

    function buttonFor(input) {
        for (var i = 0; i < enhanced.length; i++) {
            if (enhanced[i].input === input) return enhanced[i].btn;
        }
        return null;
    }

    /**
     * The positioned box the eye is hung off.
     *
     * Reuses a wrapper the page already provides — .input-wrap on the auth
     * pages, .m-input-wrap in the dashboard modals. Injecting a .pw-field
     * inside one of those would work visually but would also become the
     * nearest match for form-validate's errorAnchor(), so a validation
     * message would land back inside .m-input-wrap and push that wrapper's
     * lock icon down the field: exactly the bug that anchoring outside the
     * wrapper fixed. One wrapper per field, and both scripts agree which.
     */
    function wrapperFor(input) {
        var existing = input.closest('.input-wrap, .m-input-wrap, .pw-field');
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

        // Plain <i>. Font Awesome used to be loaded here as JS, which rewrote
        // every <i> into an <svg> at load and meant this had to accept either.
        // Every view that loads this script now uses the CSS build, so the
        // element it created is still the element that is here.
        var icon = btn.querySelector('i');
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

        enhanced.push({ input: input, btn: btn });
        apply(btn, input, false);

        btn.addEventListener('click', function (e) {
            e.preventDefault();

            // One decision, applied to the whole group, so the fields can never
            // drift out of step — and so both eyes end up showing the same icon
            // and the same label.
            var visible = input.type === 'password';

            peersOf(input).forEach(function (field) {
                var fieldBtn = buttonFor(field);
                if (fieldBtn) apply(fieldBtn, field, visible);
            });
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
