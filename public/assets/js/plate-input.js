/**
 * Upper-cases a plate number as it is typed. Nothing else.
 *
 * This replaces plate-mask.js, which tried to format as well. That mask split
 * the input into letters and digits and always re-emitted letters first, so a
 * motorcycle plate was silently rewritten into a car one: typing "123 ABC"
 * produced "ABC 123", and "AB 12345" was truncated to "AB 1234" because digits
 * were capped at four. Three of the nine formats the shop sees came out as a
 * different plate from the one on the vehicle — and the customer could watch it
 * happen and had no way to stop it.
 *
 * Capitalising is safe because it cannot reorder or drop a character, and it
 * matches what PlateNumber::normalise() does on the server, so what you see is
 * what gets stored. Everything else — spacing, hyphens, length — is the
 * server's business.
 *
 * Usage: PlateInput.attach(document.getElementById('plateInput'));
 */
(function (window, document) {
    'use strict';

    function attach(inputEl) {
        if (!inputEl || inputEl.dataset.plateInputReady) return;
        inputEl.dataset.plateInputReady = '1';

        inputEl.addEventListener('input', function () {
            var upper = inputEl.value.toUpperCase();

            // Only touch the field if the value really changed. Assigning to
            // .value unconditionally fights an in-progress IME composition and
            // moves the caret for no reason.
            if (upper === inputEl.value) return;

            // Upper-casing never changes the length, so the caret belongs
            // exactly where it was — but writing .value moves it to the end,
            // so it has to be put back. This is the one thing the old mask got
            // right, and it is kept.
            var start = inputEl.selectionStart;
            var end   = inputEl.selectionEnd;

            inputEl.value = upper;

            if (start !== null && end !== null) {
                try {
                    inputEl.setSelectionRange(start, end);
                } catch (e) {
                    // Some input types refuse setSelectionRange. Losing the
                    // caret is survivable; throwing in an input handler is not.
                }
            }
        });
    }

    window.PlateInput = { attach: attach };
})(window, document);
