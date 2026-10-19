<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What counts as "due for maintenance".
 *
 * The paper describes the system telling a customer their car is due for
 * servicing, not just that they have an appointment on Thursday. That needs a
 * rule table: how often a thing should be done, and what to book when it is.
 *
 * Intervals are stored two ways because customers know their mileage about
 * half the time. interval_months always applies; interval_km applies only when
 * the vehicle has a reading on file. Whichever comes first wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();

            // What to recommend booking. A bundle is the usual answer (the PMS
            // packages), a single service the exception. Both nullable so a
            // rule can be advisory only.
            $table->foreignId('bundle_id')->nullable()->constrained('bundles')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

            $table->unsignedSmallInteger('interval_months')->nullable();
            $table->unsignedInteger('interval_km')->nullable();

            // How many days ahead of the due date to tell them. Enough notice
            // to book, not so much they forget.
            $table->unsignedSmallInteger('lead_days')->default(14);

            // null = applies to every vehicle.
            $table->string('vehicle_type')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_rules');
    }
};
