/**
 * Live input mask for Philippine vehicle plate numbers. Format-agnostic
 * between the old (3 letters + 3 digits) and current (3 letters + 4 digits)
 * LTO formats — it just uppercases, groups up to 3 leading letters, then up
 * to 4 trailing digits, and inserts a space once digits start. Whether
 * exactly 3 or 4 digits is required belongs to FormValidate.rules.plate,
 * not this mask (the mask stays permissive while typing).
 *
 * Usage: PlateMask.attach(document.getElementById('plateInput'));
 */
(function (window) {
    'use strict';

    function format(raw) {
        var clean = raw.toUpperCase().replace(/[^A-Z0-9]/g, '');
        var letters = clean.replace(/[0-9]/g, '').slice(0, 3);
        var digits = clean.replace(/[A-Z]/g, '').slice(0, 4);
        return digits.length > 0 ? (letters + ' ' + digits) : letters;
    }

    function attach(inputEl) {
        if (!inputEl) return;

        inputEl.addEventListener('input', function () {
            var oldValue = inputEl.value;
            var oldPos = inputEl.selectionStart || oldValue.length;
            var oldLen = oldValue.length;

            var newValue = format(oldValue);
            inputEl.value = newValue;

            var delta = newValue.length - oldLen;
            var newPos = Math.max(0, Math.min(newValue.length, oldPos + delta));
            inputEl.setSelectionRange(newPos, newPos);
        });

        // Normalize once more on blur in case of paste/autofill edge cases.
        inputEl.addEventListener('blur', function () {
            inputEl.value = format(inputEl.value);
        });
    }

    window.PlateMask = { attach: attach, format: format };
})(window);
