<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `duration` snapshots the total minutes the booking occupies (summed
     * across all selected services at creation time), so availability math
     * stays correct even if a service's duration changes later.
     *
     * `arrived_at` is stamped when staff mark a booking "Arrived / Start
     * service" (status -> in_progress); its presence protects the booking
     * from the no-show auto-cancel job.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'duration')) {
                $table->integer('duration')->nullable()->after('service_id');
            }
            if (! Schema::hasColumn('bookings', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'duration')) {
                $table->dropColumn('duration');
            }
            if (Schema::hasColumn('bookings', 'arrived_at')) {
                $table->dropColumn('arrived_at');
            }
        });
    }
};
