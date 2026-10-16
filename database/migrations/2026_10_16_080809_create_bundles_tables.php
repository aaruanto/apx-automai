<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service bundles: several services sold together for less.
 *
 * bookings.bundle_price is a snapshot, for the same reason the service pivot
 * carries one — a bundle re-priced next month must not change what a booking
 * made today earned. The booking also keeps its individual services in the
 * existing pivot, with the bundle's discount spread across them, so every
 * query that already sums booking_service.price keeps working and per-service
 * revenue stays honest without a second code path for money.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);

            // Optional run dates. Null at either end means open-ended.
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();

            // Null means it suits any vehicle; otherwise 'car' or 'motorcycle',
            // matching vehicles.vehicle_type.
            $table->string('vehicle_type')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('bundle_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bundle_id', 'service_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Which bundle, and what it cost at the time. Kept even if the
            // bundle is later deleted, so the booking still reads correctly.
            $table->foreignId('bundle_id')->nullable()->after('service_id')
                ->constrained()->nullOnDelete();
            $table->decimal('bundle_price', 10, 2)->nullable()->after('bundle_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'bundle_id')) {
                $table->dropForeign(['bundle_id']);
                $table->dropColumn(['bundle_id', 'bundle_price']);
            }
        });

        Schema::dropIfExists('bundle_service');
        Schema::dropIfExists('bundles');
    }
};
