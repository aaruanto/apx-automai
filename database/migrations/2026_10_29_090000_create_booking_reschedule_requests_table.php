<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A proposed change to a booking's schedule, which has not happened yet.
 *
 * An admin could move a customer's appointment outright: change the date on
 * the edit form, save, and the booking was simply on a different day. The
 * customer learned about it afterwards, if at all.
 *
 * This is deliberately its own table rather than a booking status. The status
 * column is what BookingAvailability::ACTIVE_STATUSES reads to decide whether
 * a booking occupies a mechanic, so a status like "awaiting_approval" would
 * drop the booking out of that list and silently free the slot the customer
 * still holds — handing their time to somebody else while they think about
 * it. The booking's own columns are untouched while a request is open; only
 * applying one moves it.
 *
 * The proposed slot is NOT held. Reserving it would mean one booking
 * occupying two slots at once, which is a real loss to other customers for
 * the sake of a maybe. It is re-checked when the request is applied, and the
 * application fails cleanly if the time has gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_reschedule_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // What is being proposed.
            $table->date('proposed_date');
            $table->time('proposed_time');

            // What it was when the request was raised. Snapshotted rather than
            // read back from the booking later, so the record of "moved from
            // X to Y" survives any further change.
            $table->date('original_date');
            $table->time('original_time');

            // Required of the admin. "Why is my appointment moving" is the
            // first thing the customer asks, and it travels in the email.
            $table->text('reason');

            // pending   — raised, waiting on the customer
            // applied   — the customer agreed and the booking moved
            // withdrawn — the admin took it back; the booking never moved
            $table->string('status')->default('pending')->index();

            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // The common question is "does this booking have one open?".
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reschedule_requests');
    }
};
