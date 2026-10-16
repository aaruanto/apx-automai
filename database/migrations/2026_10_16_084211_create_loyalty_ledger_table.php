<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loyalty points, as a ledger rather than a counter.
 *
 * A single balance column on the customer would be a number with no history:
 * when it disagrees with reality — and eventually it will — there is nothing
 * to reconstruct it from. Every movement is a row here, and the balance is
 * the sum of them. balance_after is carried on each row as well, so a
 * disagreement between the two is detectable rather than silent.
 *
 * Earn rows double as lots: `remaining` tracks how much of that earning is
 * still unspent, which is what makes expiry possible. Spending consumes the
 * oldest lots first, so points expire in the order they were earned rather
 * than whichever order happens to be convenient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // Signed: positive earns, negative spends, expiries and reversals.
            $table->integer('points');

            // Unspent remainder of an earn row. Null on everything else.
            $table->integer('remaining')->nullable();

            // The balance immediately after this row, so a drift between this
            // and SUM(points) is something we can find rather than guess at.
            $table->integer('balance_after');

            // earned | redeemed | reversed | expired | adjusted
            $table->string('reason')->index();
            $table->string('note')->nullable();

            // Only set on earn rows.
            $table->date('expires_at')->nullable();

            $table->timestamps();

            // The two queries this table actually serves: a customer's
            // history, and the FIFO walk over their unspent lots.
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'expires_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('points_earned')->default(0)->after('discount_amount');
            $table->unsignedInteger('points_spent')->default(0)->after('points_earned');
            $table->decimal('points_discount', 10, 2)->default(0)->after('points_spent');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'points_spent', 'points_discount']);
        });

        Schema::dropIfExists('loyalty_ledger');
    }
};
