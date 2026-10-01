<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Passive audit trail for self-service account deletions.
     *
     * Deliberately stores NO personal data — recording the name or email of an
     * account we just anonymized would defeat the erasure. The numeric user_id
     * is enough to correlate with retained bookings, and the counts explain
     * movements in the admin's customer/booking totals.
     */
    public function up(): void
    {
        Schema::create('account_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedInteger('bookings_cancelled')->default(0);
            $table->unsignedInteger('bookings_retained')->default(0);
            $table->unsignedInteger('vehicles_anonymized')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletions');
    }
};
