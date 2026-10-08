<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's database notifications need a `notifications` table of a specific
 * shape (uuid key, notifiable morph, json data, read_at).
 *
 * That name was already taken by an unused SMS/email delivery log — no model,
 * no reads, no writes, no rows. It is renamed to notification_logs rather than
 * dropped: it costs nothing to keep, it pairs with the Message Templates
 * feature if that ever starts recording sends, and a rename is reversible
 * where a drop is not.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only rename the legacy table, identified by a column Laravel's
        // version never has. Re-running, or running against a database that
        // already has the right shape, does nothing.
        if (Schema::hasTable('notifications')
            && Schema::hasColumn('notifications', 'recipient')
            && ! Schema::hasTable('notification_logs')) {
            Schema::rename('notifications', 'notification_logs');
        }

        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        if (Schema::hasTable('notification_logs')) {
            Schema::rename('notification_logs', 'notifications');
        }
    }
};
