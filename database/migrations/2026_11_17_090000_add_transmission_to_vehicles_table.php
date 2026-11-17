<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a vehicle is manual or automatic.
 *
 * The shop asked for it because some work is one or the other — a manual
 * transmission oil change is not an automatic transmission service, and a CVT
 * fluid change is neither of the things a manual car needs. Until now the
 * only thing recorded about a vehicle was car-or-motorcycle, so a customer
 * could book gearbox work meant for a transmission their car does not have,
 * and the shop found out when it arrived.
 *
 * Nullable with no default, deliberately — the same shape as
 * vehicles.vehicle_type was NOT given, and the difference matters. vehicle_type
 * defaults to 'car' because every vehicle is one or the other and 'car' is the
 * overwhelming majority, so a default is a reasonable guess. Transmission has
 * no such majority and no way to guess: every vehicle already in the system
 * was recorded before anyone was asked, and writing 'manual' or 'automatic'
 * onto those rows would be inventing data the shop would then have to correct
 * one car at a time. NULL means "not recorded", reads as "applies to both"
 * everywhere it is compared, and is what the customer fills in next time they
 * book.
 *
 * 20 characters for the same reason vehicle_type uses it: the values are two
 * short words and nothing should be able to put an essay here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('vehicles', 'transmission')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            // 'manual' or 'automatic'. NULL means not recorded.
            $table->string('transmission', 20)->nullable()->after('vehicle_type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vehicles', 'transmission')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('transmission');
        });
    }
};
