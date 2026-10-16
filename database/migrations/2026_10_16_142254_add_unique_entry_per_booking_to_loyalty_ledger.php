<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One entry of each kind per booking, enforced by the database.
 *
 * Awarding was already idempotent: earnFor takes a row lock and checks for an
 * existing entry before writing. That holds under concurrency, but it is a
 * promise kept in one method — a future caller writing to the ledger directly,
 * or a second award path added without reading that method first, would defeat
 * it silently and mint points.
 *
 * This makes the rule structural instead. A booking may earn once, be redeemed
 * against once, be reversed once and be refunded once; a second attempt at any
 * of them fails at the database rather than succeeding quietly.
 *
 * Rows with a null booking_id — manual adjustments and expiries — are
 * unaffected, because SQL treats nulls as distinct in a unique index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_ledger', function (Blueprint $table) {
            $table->unique(['booking_id', 'reason'], 'loyalty_ledger_one_entry_per_booking');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_ledger', function (Blueprint $table) {
            $table->dropUnique('loyalty_ledger_one_entry_per_booking');
        });
    }
};
