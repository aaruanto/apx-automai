<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When this vehicle was last serviced, and how far it had gone.
 *
 * Maintenance reminders need a starting point to count from. The date is
 * maintained by the system — set whenever a booking for the vehicle is marked
 * completed — so it stays correct without anyone being asked to keep it up.
 *
 * Mileage is the opposite: it is the customer's to give, and plenty of them
 * will not know it. Everything here treats it as optional. A vehicle with no
 * reading still gets reminded, just on the time interval alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('last_service_date')->nullable()->after('color');
            $table->unsignedInteger('mileage')->nullable()->after('last_service_date');
            $table->date('mileage_updated_at')->nullable()->after('mileage');

            // The reading as it stood when that work was done. Distance since
            // the last service is then a subtraction, with no need to guess how
            // far the customer drives in a month.
            $table->unsignedInteger('mileage_at_last_service')->nullable()->after('mileage_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'last_service_date', 'mileage', 'mileage_updated_at', 'mileage_at_last_service',
            ]);
        });
    }
};
