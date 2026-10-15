<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mechanics become records rather than login accounts.
 *
 * An `employees` table already existed — name, specialty, status — created
 * months ago and then abandoned: bookings.staff_id was wired to `users`
 * instead, and nothing ever read or wrote `employees`. This finishes that
 * abandoned switch rather than adding a third table alongside it.
 *
 * Renamed rather than dropped and recreated, because this database is not the
 * only one that has run that original migration and the table may hold rows
 * elsewhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && ! Schema::hasTable('mechanics')) {
            Schema::rename('employees', 'mechanics');
        }

        Schema::table('mechanics', function (Blueprint $table) {
            if (! Schema::hasColumn('mechanics', 'contact_number')) {
                // Nullable at the database level even though the form requires
                // it: any row already present predates the field and must not
                // block the migration.
                $table->string('contact_number')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mechanics', function (Blueprint $table) {
            if (Schema::hasColumn('mechanics', 'contact_number')) {
                $table->dropColumn('contact_number');
            }
        });

        if (Schema::hasTable('mechanics') && ! Schema::hasTable('employees')) {
            Schema::rename('mechanics', 'employees');
        }
    }
};
