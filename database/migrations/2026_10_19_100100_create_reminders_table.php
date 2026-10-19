<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every reminder the system has sent, and the reason it will not send it twice.
 *
 * Before this table the reminder command had no memory. Its windows matched on
 * whereDate(), which is true for the whole of that day, so a booking seven days
 * out matched every single time the scheduler ticked. At everyMinute() that is
 * 1,440 identical emails per booking per window against a 300/day sending
 * limit. The only thing preventing it was that the scheduler was never running
 * on Render — which is the very thing we are now fixing.
 *
 * dedupe_key is what makes a send exactly-once. It is a composed string
 * ("appointment:41:remind_7d:email") rather than a composite index over
 * nullable foreign keys, because in Postgres two NULLs are distinct and a
 * unique index containing one would not dedupe anything at all. A plain string
 * column behaves identically on SQLite and Postgres, which is the whole point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();

            // 'appointment' — you have a booking coming up.
            // 'maintenance'  — your vehicle is due for servicing.
            $table->string('type');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_rule_id')->nullable()->constrained('maintenance_rules')->nullOnDelete();

            // Which window fired: 'remind_7d', or for maintenance the due date.
            $table->string('trigger_key');
            $table->string('channel');

            $table->date('due_date')->nullable();
            $table->string('status')->default('sent');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->string('dedupe_key')->unique();
            $table->timestamps();

            $table->index(['type', 'sent_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
