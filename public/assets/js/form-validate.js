/**
 * Shared client-side form validation. Plain script, no dependencies —
 * load via <script src="{{ asset('assets/js/form-validate.js') }}"></script>
 * before a page's own inline <script> block.
 *
 * Usage:
 *   FormValidate.register(fieldEl, {
 *     rules: [FormValidate.rules.required('Name is required'), ...],
 *     events: ['blur'],       // 'input' is added automatically after first failed validation
 *   });
 *   FormValidate.bindSubmit(formEl, submitBtnEl, (formEl) => { ...on valid submit... });
 *   FormValidate.watchButton(formEl, submitBtnEl); // live-disables the button until the form is valid
 */
(function (window) {
    'use strict';

    var registry = new WeakMap(); // fieldEl -> { rules, errorEl, revalidateOnInput }

    /**
     * What the error message should be placed after.
     *
     * A field is often wrapped — .input-wrap on the auth pages, .m-input-wrap
     * in the dashboard modals, .pw-field around a password and its eye — and
     * those wrappers position their icons at top:50% of themselves. Putting
     * the message after the *input* puts it inside that wrapper, which makes
     * the wrapper taller, which slides the lock and the eye down the field.
     * Measured on /register: the wrapper went 41px -> 73px and both icons
     * dropped 16px the moment a field failed.
     *
     * After the wrapper instead, so the message sits below the whole field and
     * the wrapper's own height never changes.
     */
    function errorAnchor(fieldEl) {
        return (fieldEl.closest && fieldEl.closest('.input-wrap, .m-input-wrap, .pw-field')) || fieldEl;
    }

    function getErrorEl(fieldEl, explicitErrorEl) {
        if (explicitErrorEl) return explicitErrorEl;

        var anchor = errorAnchor(fieldEl);

        // Look for the existing message where this function now puts it. The
        // lookup and the insertion have to agree: if one moved and the other
        // did not, every register() call would add another message element.
        var next = anchor.nextElementSibling;
        if (next && next.classList && next.classList.contains('fv-error')) return next;

        // An older message still sitting inside the wrapper, from markup or
        // from a page that ran against the previous behaviour. Reuse it rather
        // than adding a second one, and move it out so it stops inflating the
        // wrapper.
        if (anchor !== fieldEl) {
            var stale = anchor.querySelector(':scope > .fv-error');
            if (stale) {
                anchor.insertAdjacentElement('afterend', stale);
                return stale;
            }
        }

        var el = document.createElement('div');
        el.className = 'fv-error';
        anchor.insertAdjacentElement('afterend', el);
        return el;
    }

    function fieldValue(fieldEl) {
        if (fieldEl.type === 'checkbox' || fieldEl.type === 'radio') {
            return fieldEl.checked ? 'checked' : '';
        }
        return (fieldEl.value || '');
    }

    function setInvalid(fieldEl, message) {
        var entry = registry.get(fieldEl);
        fieldEl.classList.add('fv-invalid');
        if (entry && entry.errorEl) {
            entry.errorEl.textContent = message;
            entry.errorEl.classList.add('show');
        }
    }

    function setValid(fieldEl) {
        var entry = registry.get(fieldEl);
        fieldEl.classList.remove('fv-invalid');
        if (entry && entry.errorEl) {
            entry.errorEl.textContent = '';
            entry.errorEl.classList.remove('show');
        }
    }

    function validateField(fieldEl) {
        var entry = registry.get(fieldEl);
        if (!entry) return true;
        var value = fieldValue(fieldEl);
        for (var i = 0; i < entry.rules.length; i++) {
            var rule = entry.rules[i];
            if (!rule.test(value, fieldEl)) {
                setInvalid(fieldEl, rule.message);
                return false;
            }
        }
        setValid(fieldEl);
        return true;
    }

    function register(fieldEl, options) {
        options = options || {};
        var errorEl = getErrorEl(fieldEl, options.errorEl);
        var events = options.events || ['blur'];
        var entry = { rules: options.rules || [], errorEl: errorEl, revalidateOnInput: false };
        registry.set(fieldEl, entry);

        events.forEach(function (evt) {
            fieldEl.addEventListener(evt, function () { validateField(fieldEl); });
        });

        // Once a field has been shown invalid, re-validate live as the user types
        // so the error clears immediately when corrected (real-time re-validation).
        fieldEl.addEventListener('input', function () {
            if (fieldEl.classList.contains('fv-invalid')) validateField(fieldEl);
        });
    }

    function unregister(fieldEl) {
        registry.delete(fieldEl);
    }

    function validateForm(fieldsOrContainer) {
        var fields = resolveFields(fieldsOrContainer);
        var allValid = true;
        var firstInvalid = null;
        fields.forEach(function (fieldEl) {
            var ok = validateField(fieldEl);
            if (!ok) {
                allValid = false;
                if (!firstInvalid) firstInvalid = fieldEl;
            }
        });
        if (firstInvalid) firstInvalid.focus();
        return allValid;
    }

    function resolveFields(fieldsOrContainer) {
        if (Array.isArray(fieldsOrContainer)) return fieldsOrContainer;
        if (fieldsOrContainer instanceof HTMLElement) {
            var all = fieldsOrContainer.querySelectorAll('input, select, textarea');
            var registered = [];
            all.forEach(function (el) { if (registry.has(el)) registered.push(el); });
            return registered;
        }
        return [];
    }

    function bindSubmit(formEl, submitBtnEl, onValidSubmit) {
        formEl.addEventListener('submit', function (e) {
            var valid = validateForm(formEl);
            if (!valid) {
                e.preventDefault();
                return;
            }
            if (typeof onValidSubmit === 'function') {
                e.preventDefault();
                onValidSubmit(formEl);
            }
            // else: let the native submit proceed
        });
        if (submitBtnEl) watchButton(formEl, submitBtnEl);
    }

    function watchButton(formEl, submitBtnEl) {
        // Checks rules directly (not just the .fv-invalid class) so an untouched
        // required field keeps the button disabled instead of appearing valid
        // just because it's never been blurred yet.
        function refresh() {
            var fields = resolveFields(formEl);
            var allValid = fields.every(function (fieldEl) {
                var entry = registry.get(fieldEl);
                if (!entry) return true;
                var value = fieldValue(fieldEl);
                return entry.rules.every(function (rule) { return rule.test(value, fieldEl); });
            });
            submitBtnEl.disabled = !allValid;
        }
        formEl.addEventListener('input', refresh);
        formEl.addEventListener('change', refresh);
        refresh();
    }

    var rules = {
        required: function (message) {
            return { test: function (v) { return v.trim().length > 0; }, message: message || 'This field is required.' };
        },
        email: function (message) {
            return { test: function (v) { return v.trim() === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); }, message: message || 'Enter a valid email address.' };
        },
        phonePH: function (message) {
            return { test: function (v) { return /^09\d{9}$|^\+639\d{9}$/.test(v.trim()); }, message: message || 'Enter a valid PH mobile number (e.g. 09171234567).' };
        },
        // Must agree with App\Support\PlateNumber exactly — same shape, same
        // bounds, same message. The old rule here and its four server-side
        // twins all demanded three letters, a space and three or four digits,
        // which is a car plate: it refused 123 ABC and AB 12345 outright, and
        // every protocol, government, diplomatic, trailer and vintage plate
        // with them. Shape, not format.
        plate: function (message) {
            return {
                test: function (v) {
                    var p = v.toUpperCase().replace(/\s+/g, ' ').trim();
                    return p.length >= 2 && p.length <= 15 && /^[A-Z0-9]+(?:[ -][A-Z0-9]+)*$/.test(p);
                },
                message: message || 'Enter the plate as it appears on the vehicle, e.g. ABC 1234, 123 ABC or AB 12345.',
            };
        },
        minLen: function (n, message) {
            return { test: function (v) { return v.trim().length >= n; }, message: message || ('Must be at least ' + n + ' characters.') };
        },
        maxLen: function (n, message) {
            return { test: function (v) { return v.length <= n; }, message: message || ('Must be at most ' + n + ' characters.') };
        },
        checked: function (message) {
            return { test: function (v) { return v === 'checked'; }, message: message || 'This must be checked to continue.' };
        }
    };

    window.FormValidate = {
        register: register,
        unregister: unregister,
        validateField: validateField,
        validateForm: validateForm,
        bindSubmit: bindSubmit,
        watchButton: watchButton,
        rules: rules
    };
})(window);
