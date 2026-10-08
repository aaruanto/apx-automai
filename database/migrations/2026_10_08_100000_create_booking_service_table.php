<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A booking can cover several services.
 *
 * price and duration are snapshotted at booking time on purpose: a service's
 * price changing later must not silently rewrite what a past booking cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_service')) {
            return;
        }

        Schema::create('booking_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('duration')->default(0);
            $table->timestamps();

            // The same service cannot be attached to one booking twice.
            $table->unique(['booking_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_service');
    }
};
