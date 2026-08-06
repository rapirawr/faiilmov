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
        Schema::table('films', function (Blueprint $table) {
            $table->index('rating');
            $table->index('release_year');
            $table->index('view_count');
            $table->index('subject_type');
        });

        Schema::table('watch_parties', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('rating');
            $table->index('created_at');
        });

        Schema::table('watch_party_participants', function (Blueprint $table) {
            $table->index(['watch_party_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropIndex(['rating']);
            $table->dropIndex(['release_year']);
            $table->dropIndex(['view_count']);
            $table->dropIndex(['subject_type']);
        });

        Schema::table('watch_parties', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['rating']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('watch_party_participants', function (Blueprint $table) {
            $table->dropIndex(['watch_party_id', 'session_id']);
        });
    }
};
