<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the loyalty feature: the rewards table and the loyalty columns
     * on customers.
     */
    public function up(): void
    {
        Schema::dropIfExists('rewards');

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'loyalty_points')) {
                $table->dropColumn('loyalty_points');
            }
            if (Schema::hasColumn('customers', 'tier')) {
                $table->dropColumn('tier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('loyalty_points')->default(0);
            $table->enum('tier', ['bronze', 'silver', 'gold'])->default('bronze');
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->timestamps();
        });
    }
};
