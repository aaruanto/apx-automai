<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Guarded so this is safe to re-run even if the failed attempt
            // already added one of the columns.
            if (! Schema::hasColumn('bookings', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('bookings', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancel_reason')
                      ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }
        });

        // NOTE: reference_number is already unique in your base bookings migration,
        // so there is nothing to add for it here — and no duplicates were ever
        // possible, which is why the old renumber step is gone.
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['cancel_reason', 'cancelled_by', 'cancelled_at']);
        });
    }
};