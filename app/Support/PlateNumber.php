<?php

namespace App\Support;

/**
 * The one place a plate number is cleaned up and judged.
 *
 * It used to be judged in four places by the same copied regex,
 * /^[A-Z]{3} \d{3,4}$/i, which encodes one shape: three letters, a space,
 * three or four digits. That is a car plate. It rejects every motorcycle
 * format the shop actually sees — 123 ABC, AB 12345 — along with protocol,
 * government, diplomatic, trailer and vintage plates and conduction
 * stickers. A wrong rejection stops a real booking at the door; a typo is
 * caught when the vehicle physically arrives. So the rule checks the SHAPE
 * of the input, not its format: letters, digits, spaces and hyphens, within
 * a sensible length. The usual shapes live in the placeholder, where they
 * inform without refusing.
 *
 * Normalising matters more than validating. plate_number is globally unique
 * and is looked up by exact string match, so "ABC  1234" and "ABC 1234" are
 * two different vehicles for the same car unless every write and every
 * lookup agrees on one form. They did not: two admin paths called
 * strtoupper() with no trim at all, so a leading space from a paste created
 * a second row. Everything goes through normalise() now.
 */
class PlateNumber
{
    /** Short enough to be a plate, long enough for a diplomatic or trailer one. */
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 15;

    /**
     * Prefixes the system uses for itself.
     *
     * Vehicle::displayPlate() renders anything starting NO-PLATE- or
     * REMOVED- as "Not provided" — the first from guest bookings that left
     * the plate blank, the second from account deletions. The old regex made
     * them impossible to type; this looser one does not, and a customer who
     * entered NO-PLATE-99 would find their plate showing as "Not provided"
     * everywhere with nothing to explain it.
     */
    public const RESERVED_PREFIXES = '/^(NO-PLATE-|REMOVED-)/';

    /** Shown to the customer, so it shows the shapes rather than naming the rule. */
    public const MESSAGE = 'Enter the plate as it appears on the vehicle, e.g. ABC 1234, 123 ABC or AB 12345.';

    /**
     * One canonical form: upper case, single spaces, no surrounding space.
     *
     * Runs of whitespace collapse to one. Anything else the customer typed
     * is left alone — a hyphen is part of some plates, and silently removing
     * characters is what the old input mask did.
     */
    public static function normalise(?string $raw): string
    {
        return trim(preg_replace('/\s+/', ' ', strtoupper((string) $raw)));
    }

    /**
     * Validation rules, for a value that has already been normalised.
     *
     * Letters, digits, and single spaces or hyphens between them. The
     * pattern cannot match a leading, trailing or doubled separator, so it
     * also proves normalise() was applied.
     */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:'.self::MIN_LENGTH,
            'max:'.self::MAX_LENGTH,
            'regex:/^[A-Z0-9]+(?:[ -][A-Z0-9]+)*$/',
            'not_regex:'.self::RESERVED_PREFIXES,
        ];
    }

    /** Validation messages for whichever field name a form uses. */
    public static function messages(string $field): array
    {
        return [
            $field.'.regex' => self::MESSAGE,
            $field.'.min'   => self::MESSAGE,
            $field.'.max'   => self::MESSAGE,
            $field.'.not_regex' => 'That plate is reserved by the system. Please enter the plate as it appears on the vehicle.',
        ];
    }
}
