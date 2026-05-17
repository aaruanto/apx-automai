<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add dob and address to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->date('dob')->nullable()->after('phone');
            $table->string('address')->nullable()->after('dob');
        });

        // Add is_primary flag to vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['dob', 'address']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};