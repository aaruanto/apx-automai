<?php

namespace App\Support;

/**
 * The one place a Philippine mobile number is cleaned up and judged.
 *
 * Written for the same reason as PlateNumber: the rule was in four places
 * and only three of them were the same. The guest booking form asked for a
 * phone number, validated it client side with FormValidate.rules.phonePH,
 * and then read it on the server as `$request->guest_phone ?? null` — no
 * rule at all. So a guest booking could be saved with no number, or with
 * "dunno", and the shop has no way to ring a guest back about a booking
 * made under a name and an email it cannot verify.
 *
 * ── Stored as typed ───────────────────────────────────────────────────
 *
 * normalise() strips the separators people type — spaces, hyphens,
 * brackets, dots — and nothing else. It does NOT convert 09 to +63, which
 * is deliberate and matches Mechanic::normalisedContact(): a number is kept
 * in the form its owner gave it, so what the shop reads back on screen is
 * what the customer wrote, and the verification-call notice can show them
 * their own number to check for a typo. Converting on the way IN would show
 * a customer a number they did not type and have them wondering whether we
 * got it wrong.
 *
 * The dialling form is a separate question with a separate answer:
 * SmsService::normalise() turns any of these into +639XXXXXXXXX on the way
 * out to a gateway. That is the only place that conversion belongs, because
 * it is the only place it is needed.
 *
 * ── Agreement with the browser ────────────────────────────────────────
 *
 * PATTERN below is the same shape as FormValidate.rules.phonePH, and the
 * direction of any disagreement matters: the browser must never approve
 * what the server will refuse. It cannot here, because the server cleans
 * separators first and so accepts strictly more than the browser does —
 * "0917 123 4567" is refused in the field and accepted on submission,
 * which is the harmless direction.
 */
class PhoneNumber
{
    /**
     * 09XXXXXXXXX or +639XXXXXXXXX, and nothing else.
     *
     * Mobile only, on purpose: this number exists to be called and texted
     * about a booking, and a landline cannot receive the confirmation SMS.
     * Both spellings are accepted because both are what people write.
     */
    public const PATTERN = '/^(?:09\d{9}|\+639\d{9})$/';

    /** Shown to the customer, so it shows the shape rather than naming the rule. */
    public const MESSAGE = 'Enter a valid Philippine mobile number, e.g. 09171234567 or +639171234567.';

    /**
     * Separators out, digits and a leading + kept.
     *
     * Anything else the customer typed is left alone rather than stripped,
     * so a number with letters in it fails the rule below and is reported,
     * instead of being quietly filed down into something that passes.
     */
    public static function normalise(?string $raw): string
    {
        return preg_replace('/[\s()\.\-]/', '', trim((string) $raw)) ?? '';
    }

    /**
     * Validation rules, for a value that has already been normalised.
     *
     * max:20 matches the column, which every other phone field in the
     * project already validates against.
     */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            'regex:'.self::PATTERN,
        ];
    }

    /** Validation messages for whichever field name a form uses. */
    public static function messages(string $field): array
    {
        return [
            $field.'.required' => 'We need a phone number so we can confirm your booking by call or text.',
            $field.'.regex'    => self::MESSAGE,
            $field.'.max'      => self::MESSAGE,
        ];
    }
}
