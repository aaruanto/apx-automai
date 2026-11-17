<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the vehicle was, at the moment it was booked.
 *
 * Yes, this duplicates vehicles.vehicle_type and vehicles.transmission. That
 * is the point, and it is the same decision as booking_service.price: a
 * booking is a record of what the shop agreed to do, and editing the vehicle
 * afterwards must not silently rewrite it. A customer who corrects their car
 * from "manual" to "automatic" in their garage next March should not change
 * what last week's booking says the mechanic was asked to work on — and
 * without a snapshot, every past booking for that car would quietly follow.
 *
 * It also makes the booking readable on its own. The queue board, the admin
 * bookings list, the confirmation email and the CSV exports all want to print
 * the transmission beside the plate; reading it through the vehicle relation
 * means a soft-deleted vehicle prints nothing, and an anonymised account
 * prints whatever the anonymiser left behind.
 *
 * Nullable, with a backfill from the vehicle for rows that already exist.
 * That backfill is the one case where copying from the vehicle is right: those
 * bookings were made before the snapshot existed, so the vehicle's current
 * value is the best — and only — evidence of what was booked. New bookings
 * take their snapshot at reserve() time and never read the vehicle again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'vehicle_type')) {
                $table->string('vehicle_type', 20)->nullable()->after('vehicle_id');
            }
            if (! Schema::hasColumn('bookings', 'transmission')) {
                $table->string('transmission', 20)->nullable()->after('vehicle_type');
            }
        });

        // Backfill from the vehicle, for bookings made before the snapshot
        // existed. A correlated subquery rather than a join-update: the
        // UPDATE ... FROM syntax differs between SQLite and PostgreSQL, and
        // this form is the same sentence on both.
        //
        // Only where the snapshot is still null, so re-running this migration
        // — or running it after the shop has corrected a booking — cannot
        // overwrite a recorded value with the vehicle's current one.
        DB::table('bookings')->whereNull('vehicle_type')->update([
            'vehicle_type' => DB::raw('(select vehicle_type from vehicles where vehicles.id = bookings.vehicle_id)'),
        ]);

        DB::table('bookings')->whereNull('transmission')->update([
            'transmission' => DB::raw('(select transmission from vehicles where vehicles.id = bookings.vehicle_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['vehicle_type', 'transmission'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
