<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repoint booking assignments from staff logins to mechanic records.
 *
 * bookings.staff_id was a foreign key to users, where a "mechanic" was a user
 * with role = staff who could sign in and reach a portal. Mechanics do not log
 * in any more, so the assignment points at the mechanics table instead.
 *
 * Nothing is deleted. Each staff login found is copied into mechanics, its
 * bookings are repointed, and the account itself is demoted to customer and
 * deactivated — reversible if this turns out to be wrong, which deleting the
 * row would not be.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'mechanic_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('mechanic_id')->nullable()->after('service_id')
                    ->constrained('mechanics')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('bookings', 'staff_id')) {
            // Every staff login that is actually referenced by a booking, plus
            // any that are not — the shop's list of people should survive even
            // where no work was assigned to them yet.
            $staff = DB::table('users')->where('role', 'staff')->get();

            foreach ($staff as $user) {
                $mechanicId = DB::table('mechanics')->insertGetId([
                    'name'           => $user->name,
                    'contact_number' => $user->phone,
                    'status'         => ($user->is_active ?? true) ? 'active' : 'inactive',
                    'created_at'     => $user->created_at ?? now(),
                    'updated_at'     => now(),
                ]);

                DB::table('bookings')
                    ->where('staff_id', $user->id)
                    ->update(['mechanic_id' => $mechanicId]);
            }

            // Demoted and deactivated rather than removed. A former mechanic
            // keeps any booking history they have as a customer, and cannot
            // sign in to anything.
            DB::table('users')->where('role', 'staff')->update([
                'role'       => 'customer',
                'is_active'  => false,
                'updated_at' => now(),
            ]);

            // The foreign key goes first, and on every driver. SQLite rebuilds
            // the table to drop a column but carries the old foreign-key clause
            // across verbatim, so dropping the column alone leaves a constraint
            // referencing a column that no longer exists and the rebuild fails
            // with "unknown column staff_id in foreign key definition".
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropForeign(['staff_id']);
            });

            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('staff_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bookings', 'staff_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('staff_id')->nullable()->after('service_id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        // The mechanic rows and the demoted accounts are deliberately left in
        // place: this reverses the schema, not the decision. Re-pairing a
        // mechanic to the login it came from is not something this migration
        // can know how to do.
        if (Schema::hasColumn('bookings', 'mechanic_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['mechanic_id']);
                }

                $table->dropColumn('mechanic_id');
            });
        }
    }
};
