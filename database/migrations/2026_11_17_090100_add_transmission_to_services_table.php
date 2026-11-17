<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which transmissions a service is for.
 *
 * Deliberately the same column, the same convention and the same reasoning as
 * services.vehicle_type, one step along: NULL means it applies to every
 * transmission, otherwise 'manual' or 'automatic', compared against
 * vehicles.transmission. App\Models\Concerns\AppliesToVehicle is the one
 * implementation of both comparisons, so they cannot drift apart.
 *
 * Nullable with no default, so every existing service keeps applying to
 * everything until the shop narrows it. Narrowing a service is their decision,
 * not something a migration should guess at — the one exception being the
 * three services whose own names already state the answer, which are set
 * below.
 *
 * That backfill is here and NOT in ServiceSeeder, which is where it more
 * obviously belongs. The seeder runs on every container start, and Render's
 * free tier restarts this one every time it wakes, so anything the seeder
 * writes is a value the shop cannot keep. "Only where it is still NULL" would
 * have looked like enough of a guard — except NULL is also exactly what the
 * shop writes when they choose "All / Both" in Admin, so from the seeder's
 * side a column nobody has answered and a column deliberately widened are the
 * same thing. A shop setting "Manual Transmission Oil Change" to Both would
 * have found it narrowed again within the hour. A migration runs once, which
 * makes it the only place this can be done without second-guessing a decision
 * made afterwards.
 *
 * NOT added to bundles or maintenance_rules, which both carry vehicle_type.
 * A bundle's restriction already follows from the services inside it, and a
 * maintenance rule points at a service or a bundle and inherits theirs — so a
 * third copy of the column would be a second place to state the same fact, and
 * the two would eventually disagree about a real row. Keeping the convention
 * identical across the three tables is worth more here than being thorough.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'transmission')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Null applies to both transmissions; otherwise 'manual' or
            // 'automatic', matching vehicles.transmission.
            $table->string('transmission', 20)->nullable()->after('vehicle_type');
        });

        // The services whose names say which transmission they are for. Only
        // these three: every other service in the catalogue genuinely does
        // apply to both, and anything less obvious is for the shop to decide
        // on the Services screen.
        //
        // Matched on the exact seeded name, and only where the column is
        // still NULL — which, a line after the column was added, is every
        // row. A service the shop has renamed is simply not matched, which is
        // the right outcome: a renamed row is one they have already been
        // through, and guessing from a partial match is how you narrow the
        // wrong service.
        $byName = [
            'Manual Transmission Oil Change' => 'manual',
            'Automatic Transmission Service' => 'automatic',
            // A CVT is not literally either, but in a world with two values
            // it is the automatic one — there is no clutch pedal, and nobody
            // asking for a manual gearbox service wants this.
            'CVT Fluid Change'               => 'automatic',
        ];

        foreach ($byName as $name => $transmission) {
            DB::table('services')
                ->where('name', $name)
                ->whereNull('transmission')
                ->update(['transmission' => $transmission]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('services', 'transmission')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('transmission');
        });
    }
};
