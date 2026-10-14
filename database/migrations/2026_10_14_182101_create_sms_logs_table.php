<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per SMS the app attempted, successful or not.
 *
 * The failures matter more than the successes here: "the customer says they
 * never got a reminder" is answered by a row saying the number was not a valid
 * mobile, or that credits ran out, far faster than by reading the gateway's
 * dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();

            // Nullable: a test send has no booking, and a send can fail before
            // a recipient is even resolved.
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient')->nullable();

            $table->text('content');
            $table->unsignedSmallInteger('segments')->default(1);

            // 'pending'/'sent'/'delivered'/'failed' from the gateway, or one of
            // our own reasons a send never left: not_configured, invalid_number,
            // too_long, transport_error, http_401 and so on.
            $table->string('status')->index();
            $table->string('reference_id')->nullable()->index();
            $table->text('fail_reason')->nullable();

            // What prompted it — 'test', 'remind_1d', 'booking_confirmed' —
            // so a noisy trigger can be found without parsing message bodies.
            $table->string('trigger')->nullable()->index();

            $table->timestamps();

            // The common query is "recent activity", newest first.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
