<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks accounts created on someone's behalf by a guest booking, which the
 * person has never set a password for.
 *
 * The code used to infer this from email_verified_at being null, but nothing
 * in this app ever sets that column, so the check passed for every account —
 * which is what let registration overwrite a real user's password.
 *
 * Existing rows default to false, i.e. treated as real accounts. That is the
 * safe direction: the worst case is an old guest being asked to reset their
 * password, rather than a real account being claimable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_guest')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'is_guest')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_guest');
        });
    }
};
