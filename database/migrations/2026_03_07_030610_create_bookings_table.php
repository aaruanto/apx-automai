<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
        $table->foreignId('service_id')->constrained()->onDelete('cascade');
        $table->foreignId('staff_id')->nullable()->constrained('users')->onDelete('set null');
        $table->date('booking_date');
        $table->time('booking_time');
        $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
        $table->text('notes')->nullable();
        $table->string('reference_number')->unique();  // for tracking
        $table->timestamps();
        $table->softDeletes(); // safe deletion, records are kept
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
