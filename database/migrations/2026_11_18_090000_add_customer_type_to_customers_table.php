<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How the shop came to have each customer on file: Guest, Walk-in, Verified.
 *
 * App\Enums\CustomerType carries what the three mean and why Verified is not
 * email-verified. This is the column, the rows it needs to exist on, and the
 * backfill.
 *
 * ── A string, not an enum column ────────────────────────────────────────
 *
 * customers once had a `tier` enum and 2026_08_20_000000_drop_loyalty_feature
 * removed it. A $table->enum() here would compile to a CHECK constraint
 * listing the three values, which is a fourth copy of the set in the one place
 * nobody looks — and the booking status rename a day earlier is the whole
 * story of what that costs. The enum class enforces the set.
 *
 * NOT NULL with default 'guest'. The default is the cautious direction: a row
 * nobody has classified is read as the least-trusted thing it could be, so an
 * unclassified record cannot accidentally present as a verified account.
 *
 * ── Why this creates rows ───────────────────────────────────────────────
 *
 * The admin Customer tab lists `customers` rows, and two of the three paths
 * that create a customer never created one. A guest booking writes a users
 * row and stops (GuestBookingController); so does the walk-in path in
 * Admin\BookingController. A customers row only appeared when the shop added
 * someone through the Customers screen, or lazily the first time that person
 * signed into their own dashboard.
 *
 * So "Guest" would have been a value almost nothing could ever display, on
 * the one screen it exists for. The rows are created here, and every path
 * that creates a customer account now creates one too, which makes the
 * Customer tab the complete list an admin already assumes it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'customer_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('customer_type', 20)->default('guest')->after('user_id');
            });
        }

        $this->createMissingCustomerRows();
        $this->classifyEveryone();
    }

    public function down(): void
    {
        // The rows stay.
        //
        // Dropping the column is reversible; deleting customers rows is not,
        // because by the time anyone rolls this back those rows may carry a
        // phone number or an address somebody typed in, and there is no way
        // to tell which were created here from which were created since.
        // Leaving them is harmless: they are rows the Customer tab should
        // always have had.
        if (! Schema::hasColumn('customers', 'customer_type')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }

    /**
     * Give every customer account a customers row.
     *
     * Only role = customer: an admin is not a customer, and
     * 2026_10_15_212644 demoted the old 'staff' logins to customer and
     * deactivated them, so those come through here too and get a row. That is
     * correct — they are former mechanics who may well have booked a service.
     *
     * Soft-deleted users are skipped. A deleted account's row would appear on
     * the Customer tab as a customer with no name, which is the exact fault
     * 2026_09_03's account-deletion work was done to avoid.
     */
    private function createMissingCustomerRows(): void
    {
        $missing = DB::table('users')
            ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
            ->where('users.role', 'customer')
            ->whereNull('users.deleted_at')
            ->whereNull('customers.id')
            ->select('users.id', 'users.phone')
            ->get();

        if ($missing->isEmpty()) {
            return;
        }

        $now = now();

        // Chunked: this is a few dozen rows on the live site today, but an
        // insert of one row per customer account is not a statement to write
        // without a bound on its size.
        foreach ($missing->chunk(200) as $chunk) {
            DB::table('customers')->insert(
                $chunk->map(fn ($user) => [
                    // Classified a moment later, by the same rule as every
                    // other row, rather than guessed at twice.
                    'customer_type' => 'guest',
                    'user_id'       => $user->id,
                    'phone'         => $user->phone,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ])->all()
            );
        }
    }

    /**
     * Classify every row.
     *
     * The rules, and how wrong each can be:
     *
     *   users.is_guest = true  ->  Guest
     *     Exact. The column exists for precisely this and nothing else sets
     *     it; a guest booking writes it and the setup link clears it.
     *
     *   a placeholder email     ->  Walk-in
     *     Exact, in the other direction. Admin\BookingController synthesises
     *     name@apxautomai.local for a walk-in who gave no address, and it is
     *     the only thing in the application that writes that domain. A row
     *     carrying one was typed in at the counter.
     *
     *   everything else         ->  Verified
     *     This is the inexact one, and it is worth being plain about: a
     *     customer the shop added through the Customers screen WITH a real
     *     email address is indistinguishable from one who registered
     *     themselves. Nothing records who created a users row, so there is no
     *     signal left to read — those backfill as Verified and are wrong.
     *
     *     Not guessed at, either: a heuristic on "has never signed in" would
     *     need a last_login column that does not exist, and one on
     *     remember_token being null would classify every customer who has
     *     never ticked "remember me". Both would be confidently wrong about
     *     more rows than this is.
     *
     *     Going forward it does not arise — CustomerController::store writes
     *     Walk-in at creation — so this is a one-time imprecision in historic
     *     data, and the shop can correct any row from the Customers screen.
     */
    private function classifyEveryone(): void
    {
        $placeholder = \App\Notifications\BookingNotification::PLACEHOLDER_DOMAIN;

        // Verified first, as the baseline, then narrowed. Written in this
        // order so a row can only end up more cautious than it started, never
        // less — the reverse order would briefly mark a guest as verified.
        DB::table('customers')->update(['customer_type' => 'verified']);

        DB::table('customers')
            ->whereIn('user_id', DB::table('users')
                ->where('email', 'like', '%'.$placeholder)
                ->select('id'))
            ->update(['customer_type' => 'walk_in']);

        DB::table('customers')
            ->whereIn('user_id', DB::table('users')
                ->where('is_guest', true)
                ->select('id'))
            ->update(['customer_type' => 'guest']);
    }
};
