<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A trail for the changes that move money.
 *
 * Until now the only action in the whole admin area that recorded who did it
 * was cancelling a booking. A booking could be re-priced, moved back out of
 * completed, or deleted outright, and nothing anywhere said who or when.
 *
 * Deliberately not an everything-log: status changes, service and price
 * changes, deletions and price-list edits. A log full of noise is one nobody
 * reads, and the point of this one is that it can be read in full.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who. Nullable and nullOnDelete so removing an account never
            // erases the history of what it did.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name')->nullable();

            // What, as 'booking.status_changed', 'service.price_changed'.
            $table->string('action')->index();

            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            // A human handle for the record, kept here so the log still reads
            // sensibly after the row it describes is gone.
            $table->string('subject')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
