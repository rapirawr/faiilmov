<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Performance indexes for notifications
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });

        Schema::table('watch_histories', function (Blueprint $table) {
            // Performance indexes for continue watching
            $table->index('updated_at');
            $table->index(['user_id', 'updated_at']);
        });

        Schema::table('watchlists', function (Blueprint $table) {
            // Performance indexes for watchlist filtering
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            // Performance index for duplicate review checks
            $table->index(['user_id', 'film_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_read']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('watch_histories', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['user_id', 'updated_at']);
        });

        Schema::table('watchlists', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'film_id']);
        });
    }
};
