<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deletion is two-stage: the account is deactivated immediately (reversible),
     * then anonymized once the grace period expires (irreversible). These columns
     * track which stage a given deletion is at.
     */
    public function up(): void
    {
        Schema::table('account_deletions', function (Blueprint $table) {
            $table->timestamp('purge_at')->nullable()->after('user_id');
            $table->timestamp('anonymized_at')->nullable()->after('purge_at');
            $table->timestamp('restored_at')->nullable()->after('anonymized_at');
        });
    }

    public function down(): void
    {
        Schema::table('account_deletions', function (Blueprint $table) {
            $table->dropColumn(['purge_at', 'anonymized_at', 'restored_at']);
        });
    }
};
