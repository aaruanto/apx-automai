/**
 * "Complimentary work accompanies paid work" — told to the customer while they
 * are still choosing, instead of after they have picked a date and a time.
 *
 * This is the browser half of App\Support\BookingPolicy::isFreeOnly(), and it
 * is only ever feedback. The server runs the same rule on every booking and
 * does not trust anything decided here; if the two ever disagree, the server
 * wins and the customer sees its refusal. Nothing in this file may be relied
 * on for enforcement.
 *
 * Both booking flows use it: the landing page's three-step modal and the
 * signed-in dashboard's Book a Service panel. They share the decision and the
 * wording — the message comes from the PHP constant, so there is no second
 * copy to drift — and nothing else. Their markup is unrelated and each draws
 * its own notice.
 *
 * Kept deliberately parallel to the PHP, including the order of the checks,
 * so the two can be read side by side:
 *
 *     a bundle is paid work whatever it contains   -> OK
 *     an empty selection is a different problem    -> EMPTY
 *     every price at or below zero                 -> FREE_ONLY
 */
(function (global) {
    'use strict';

    var FreeOnlyGuard = {
        /** Nothing chosen yet. Not this rule's business — both flows already
         *  say "choose a service", and answering FREE_ONLY here would replace
         *  that clear prompt with a confusing one. */
        EMPTY: 'empty',

        /** Chosen, and all of it complimentary. The one state that blocks. */
        FREE_ONLY: 'free-only',

        /** Bookable as far as this rule is concerned. */
        OK: 'ok',

        /** Set once from BookingPolicy::FREE_ONLY_MESSAGE. */
        message: '',

        configure: function (message) {
            if (typeof message === 'string' && message) {
                this.message = message;
            }
        },

        /**
         * @param {Array<number>} prices  of the currently selected services
         * @param {boolean}       hasBundle
         * @returns {string} one of EMPTY, FREE_ONLY, OK
         */
        evaluate: function (prices, hasBundle) {
            if (hasBundle) {
                return this.OK;
            }

            if (!prices || prices.length === 0) {
                return this.EMPTY;
            }

            var allFree = prices.every(function (price) {
                return Number(price) <= 0;
            });

            return allFree ? this.FREE_ONLY : this.OK;
        },

        /** Whether this state should stop the customer going forward. */
        blocks: function (state) {
            return state === this.FREE_ONLY;
        }
    };

    global.FreeOnlyGuard = FreeOnlyGuard;
})(window);
