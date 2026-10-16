<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a booking was discounted by, and why.
 *
 * These columns are the record, not the arithmetic: the discount itself is
 * spread across booking_service.price, the same way a bundle's is, so every
 * revenue query keeps reading one column and no screen needs a second code
 * path for discounted work. Keeping the percentage here means a later change
 * to the setting cannot restate what an old booking was given.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('list_price', 10, 2)->nullable()->after('bundle_price');
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('list_price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['list_price', 'discount_percent', 'discount_amount']);
        });
    }
};
