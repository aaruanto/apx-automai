<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attribute a maintenance text to the vehicle it was about.
 *
 * SendMaintenanceReminders has always passed `vehicle_id` in its send
 * context, intending it to be stored — but the column did not exist and the
 * attribute was not fillable, so Eloquent discarded it without complaint.
 * A maintenance text therefore landed in sms_logs with a null booking_id and
 * nothing tying it to a vehicle at all.
 *
 * That is the half of the table nobody could read: an appointment reminder
 * was traceable through booking_id, while "we told this customer their Vios
 * was due" could only be recovered by matching a phone number against a
 * message body. The delivery log in Admin reports on both, so the link has
 * to be real rather than inferred.
 *
 * Additive and nullable: existing rows keep a null, which is the truthful
 * answer for sends made before the column existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            // nullOnDelete rather than cascade, matching booking_id: deleting a
            // vehicle must not erase the record that a customer was texted.
            $table->foreignId('vehicle_id')->nullable()->after('booking_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
