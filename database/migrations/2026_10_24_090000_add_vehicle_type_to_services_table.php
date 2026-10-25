<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which vehicles a service is for.
 *
 * bundles and maintenance_rules have carried this since they were created;
 * services never did. The result is that a guest booking a motorcycle is
 * offered an Aircon Performance Check, and the shop finds out when the bike
 * arrives.
 *
 * Same column and same convention as the other two, deliberately — NULL means
 * it applies to every vehicle, and the comparison is against
 * vehicles.vehicle_type. App\Models\Concerns\AppliesToVehicleType is the one
 * implementation all three now share.
 *
 * Nullable with no default, so every existing service keeps applying to
 * everything until the shop says otherwise. Narrowing a service is a decision
 * for them, not something a migration should guess at.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'vehicle_type')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Null applies to all vehicles; otherwise 'car' or 'motorcycle',
            // matching vehicles.vehicle_type.
            $table->string('vehicle_type')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('services', 'vehicle_type')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('vehicle_type');
        });
    }
};
