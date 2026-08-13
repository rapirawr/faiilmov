<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add social OAuth provider columns to users table.
     * Nullable for backward compatibility with existing email/password accounts.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which OAuth provider: 'google', 'facebook', null (email/password)
            $table->string('provider', 50)->nullable()->after('remember_token');
            // Provider's unique user ID (e.g., Google sub, Facebook ID)
            $table->string('provider_id')->nullable()->after('provider');
            // Index for fast lookups during OAuth callback
            $table->index(['provider', 'provider_id'], 'users_provider_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_provider_id_index');
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
