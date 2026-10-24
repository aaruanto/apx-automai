<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maintenance reminders become time-only.
 *
 * Two columns existed to answer "how far has this vehicle gone since its last
 * service": maintenance_rules.interval_km held the threshold, and
 * vehicles.mileage_at_last_service held the odometer frozen at the last
 * completed booking. MaintenanceDue subtracted one from the other.
 *
 * The system has no way to read a vehicle's mileage. The only figure it ever
 * had was one the customer typed into a form, whenever they last happened to —
 * one vehicle in seventeen had a reading at all — so a distance rule could
 * declare a car overdue on a number months out of date. The calendar is
 * something we actually know. See MaintenanceDue's docblock.
 *
 * vehicles.mileage and mileage_updated_at are deliberately KEPT. They are a
 * record the shop finds useful; they simply no longer decide anything.
 *
 * The create migrations are left as they were, as a record of what the schema
 * was. This one carries the change.
 *
 * REVERSIBILITY: down() restores both columns, but not their contents. The
 * seeded 5,000 / 10,000 / 40,000 km thresholds and every stored odometer
 * snapshot are gone for good. Structurally reversible, not data-reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded because the two columns live in different tables and a
        // part-applied run must be able to finish rather than fail twice.
        if (Schema::hasColumn('maintenance_rules', 'interval_km')) {
            Schema::table('maintenance_rules', function (Blueprint $table) {
                $table->dropColumn('interval_km');
            });
        }

        if (Schema::hasColumn('vehicles', 'mileage_at_last_service')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('mileage_at_last_service');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('maintenance_rules', 'interval_km')) {
            Schema::table('maintenance_rules', function (Blueprint $table) {
                $table->unsignedInteger('interval_km')->nullable()->after('interval_months');
            });
        }

        if (! Schema::hasColumn('vehicles', 'mileage_at_last_service')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->unsignedInteger('mileage_at_last_service')->nullable()->after('mileage_updated_at');
            });
        }
    }
};
