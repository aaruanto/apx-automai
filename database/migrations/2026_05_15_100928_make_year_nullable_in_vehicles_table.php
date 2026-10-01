<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * vehicles.year was created as a string and is widened to a nullable
     * smallint here.
     *
     * SQLite is dynamically typed so a plain ->change() is enough, but Postgres
     * refuses to cast varchar to smallint implicitly:
     *   "column year cannot be cast automatically to type smallint"
     * and wants an explicit USING clause, which the schema builder doesn't emit.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            // NULLIF guards rows holding an empty string, which would not cast.
            DB::statement("ALTER TABLE vehicles ALTER COLUMN year TYPE SMALLINT USING NULLIF(year, '')::smallint");
            DB::statement('ALTER TABLE vehicles ALTER COLUMN year DROP NOT NULL');

            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->smallInteger('year')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE vehicles ALTER COLUMN year TYPE VARCHAR(255) USING year::varchar');
            DB::statement('ALTER TABLE vehicles ALTER COLUMN year SET NOT NULL');

            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('year')->nullable(false)->change();
        });
    }
};
