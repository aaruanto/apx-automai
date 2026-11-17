<?php

namespace App\Enums;

use App\Models\User;

/**
 * How the shop came to have this customer on file.
 *
 * Three values, and the useful thing about them is what they say about how
 * much the shop can rely on the record:
 *
 *   Guest    — an account created on someone's behalf by a guest booking.
 *              They have never set a password, so nobody has proved they own
 *              the address, and the only reason the row exists is to hold a
 *              booking.
 *   Walk-in  — created by the shop, at the counter. The details are as good
 *              as whoever typed them, and the customer has never seen the
 *              account.
 *   Verified — a registered account with a password the customer set
 *              themselves.
 *
 * ── Verified does NOT mean email-verified ───────────────────────────────
 *
 * Nothing in this application ever writes users.email_verified_at. The
 * comment on 2026_10_12_090000_add_is_guest_to_users_table records that
 * assuming otherwise already caused a real bug: a guard tested that column,
 * it was null for every account, so the check passed for everyone and
 * registration could overwrite a real user's password.
 *
 * So Verified is defined as is_guest = false — someone who went through
 * /register and chose their own password, or a guest who followed the setup
 * link in their inbox, which proves they own the address and is what
 * NewPasswordController clears is_guest for.
 *
 * There are deliberately no loyalty tiers here. customers once had a `tier`
 * enum and 2026_08_20_000000_drop_loyalty_feature removed it; this is not
 * that column coming back under a new name.
 */
enum CustomerType: string
{
    case Guest    = 'guest';
    case WalkIn   = 'walk_in';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Guest    => 'Guest',
            self::WalkIn   => 'Walk-in',
            self::Verified => 'Verified',
        };
    }

    /**
     * The badge class, reusing the admin panel's existing ones.
     *
     * Verified is the green "this is a real account" badge, Walk-in the
     * neutral grey of a record rather than a person, and Guest the amber that
     * means unfinished — which it is: a guest account is one setup link away
     * from being Verified.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Guest    => 'badge-pending',
            self::WalkIn   => 'badge-inactive',
            self::Verified => 'badge-active',
        };
    }

    /** One line of why, for the admin screen. */
    public function description(): string
    {
        return match ($this) {
            self::Guest    => 'Booked without an account. Has never set a password.',
            self::WalkIn   => 'Added by the shop at the counter.',
            self::Verified => 'Registered, with a password they set themselves.',
        };
    }

    /**
     * What this user is, from the account itself.
     *
     * The one place the rule lives, so registration, the guest booking path,
     * the admin counter, the claim link and the migration's backfill cannot
     * each decide it differently — which is exactly how customers.tier used
     * to end up disagreeing with the ledger.
     *
     * $createdByShop is what the caller knows and the row cannot say: there
     * is no column recording that an admin typed this customer in. Only the
     * two admin paths pass true.
     */
    public static function forUser(User $user, bool $createdByShop = false): self
    {
        // A guest account is a guest account however it was reached: the shop
        // may have created the row, but the person still has no password and
        // the booking still came in through the guest form.
        if ($user->is_guest) {
            return self::Guest;
        }

        if ($createdByShop) {
            return self::WalkIn;
        }

        // A synthesised address is proof on its own. Admin\BookingController
        // mints these for a walk-in who gave no email — it is the only thing
        // in the application that writes the placeholder domain — so a row
        // carrying one was typed in at the counter whatever else is known
        // about it.
        if (str_ends_with((string) $user->email, \App\Notifications\BookingNotification::PLACEHOLDER_DOMAIN)) {
            return self::WalkIn;
        }

        return self::Verified;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<string, string> value => label, for a select or a filter. */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
