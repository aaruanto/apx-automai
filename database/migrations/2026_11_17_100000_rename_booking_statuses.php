<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The booking lifecycle, in the shop's own words.
 *
 *   pending     -> pending_review
 *   confirmed   -> booking_confirmed
 *   in_progress -> service_in_progress
 *   completed   -> service_completed
 *   cancelled   -> cancelled          (unchanged, and deliberately so)
 *
 * arrived and vehicle_inspection are new steps between confirmed and in
 * progress. No existing row maps to either: arrival used to be a timestamp
 * and nothing else, and inspection was not recorded at all.
 *
 * ── The CHECK constraint ────────────────────────────────────────────────
 *
 * bookings.status was created with $table->enum(...), which is not a native
 * enum on either driver this project runs — it is a varchar with a CHECK
 * constraint listing the five original values. Writing 'pending_review' into
 * that fails the constraint, so it has to go before the UPDATE, on both
 * drivers.
 *
 * They need different treatment, and one of them needs none:
 *
 * PostgreSQL names the constraint bookings_status_check and keeps it through
 * every ALTER TABLE since, so on Render it is still there and is dropped here
 * explicitly.
 *
 * SQLite bakes the CHECK into the CREATE TABLE text, and there is no
 * ALTER TABLE ... DROP CONSTRAINT to remove it. It does not need one: this
 * project's own migration history already removed it by accident.
 * 2026_10_15_212644_convert_staff_users_to_mechanics drops bookings.staff_id,
 * SQLite implements a column drop by rebuilding the table, and the rebuild
 * does not carry the CHECK across. Verified rather than assumed — migrating a
 * fresh SQLite file and reading sqlite_master back shows the column as a plain
 * `"status" varchar not null default ('pending')` with no check in sight.
 *
 * So the SQLite branch below is a guard, not a fix: it looks for a CHECK and
 * rebuilds the column as a plain string only if it finds one. That covers a
 * database created before that migration existed, and costs nothing when
 * there is nothing to find. ->change() is available without doctrine/dbal,
 * which is not in composer.json — Laravel 11 dropped that requirement and
 * does native column modification on both drivers.
 *
 * Either way the column ends up a plain string and App\Enums\BookingStatus is
 * what enforces the set, which is where the set belongs: a CHECK constraint
 * listing six values is a seventh copy of the ladder, in the one place nobody
 * thinks to look.
 */
return new class extends Migration
{
    /** old => new. cancelled is absent because it does not move. */
    private const RENAMES = [
        'pending'     => 'pending_review',
        'confirmed'   => 'booking_confirmed',
        'in_progress' => 'service_in_progress',
        'completed'   => 'service_completed',
    ];

    public function up(): void
    {
        $this->freeTheColumn();

        foreach (self::RENAMES as $old => $new) {
            DB::table('bookings')->where('status', $old)->update(['status' => $new]);
        }
    }

    /**
     * Reverse the rename.
     *
     * arrived and vehicle_inspection have no backward image — they did not
     * exist before this migration, so there is no old value to return them
     * to. They fold into 'confirmed': the booking has been agreed and work has
     * not started, which is what both of them mean in the old four-step
     * vocabulary, and it is the reading that keeps them cancellable and keeps
     * them out of the revenue figures. The alternative, 'in_progress', would
     * claim work had begun on a car that has only just been checked in.
     *
     * Lossy, and unavoidably so. Said out loud here rather than discovered by
     * whoever rolls back.
     */
    public function down(): void
    {
        $this->freeTheColumn();

        DB::table('bookings')->whereIn('status', ['arrived', 'vehicle_inspection'])
            ->update(['status' => 'confirmed']);

        foreach (self::RENAMES as $old => $new) {
            DB::table('bookings')->where('status', $new)->update(['status' => $old]);
        }
    }

    /**
     * Make bookings.status accept any string, on whichever driver this is.
     *
     * Idempotent on both: Postgres gets IF EXISTS, and SQLite is only touched
     * when a CHECK is actually found in its schema text.
     */
    private function freeTheColumn(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // Looked up, not assumed.
            //
            // Laravel's enum() compiles to `varchar(255) check ("status" in
            // (...))` with no constraint name, so PostgreSQL invents one —
            // normally bookings_status_check, but it appends a digit if that
            // name is taken, and a database restored from a dump can carry a
            // different one again. Dropping a guessed name with IF EXISTS
            // would then succeed while removing nothing, and the UPDATE below
            // would fail against a constraint still in place. On the live site.
            //
            // So this asks the catalogue which CHECK constraints are on this
            // table and mention the status column, and drops those. Normally
            // exactly one; none on a database that has already been here.
            $constraints = DB::select(<<<'SQL'
                select con.conname as name
                  from pg_constraint con
                  join pg_class rel on rel.oid = con.conrelid
                  join pg_namespace nsp on nsp.oid = rel.relnamespace
                 where rel.relname = 'bookings'
                   and con.contype = 'c'
                   and nsp.nspname = current_schema()
                   and pg_get_constraintdef(con.oid) like '%status%'
            SQL);

            foreach ($constraints as $constraint) {
                DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS "'.$constraint->name.'"');
            }

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        $sql = (string) DB::selectOne(
            "select sql from sqlite_master where type = 'table' and name = 'bookings'"
        )?->sql;

        // Only the status column's own constraint matters, and only if one is
        // there at all — on this project's migration history it is not.
        if (! preg_match('/"status"[^,]*check/i', $sql) && ! str_contains(strtolower($sql), 'check ("status"')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
