<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update collections table
        Schema::table('collections', function (Blueprint $table) {
            if (!Schema::hasColumn('collections', 'custom_watch_order_enabled')) {
                $table->boolean('custom_watch_order_enabled')->default(true)->after('cover_image');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE collections MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'published'");
            DB::statement("ALTER TABLE collections MODIFY COLUMN type VARCHAR(32) NOT NULL DEFAULT 'manual'");
        }

        // 2. Update collection_films table for sequence & custom note
        Schema::table('collection_films', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_films', 'sequence')) {
                $table->integer('sequence')->default(1)->after('film_id');
            }
            if (!Schema::hasColumn('collection_films', 'note')) {
                $table->string('note', 255)->nullable()->after('sequence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['custom_watch_order_enabled']);
        });

        Schema::table('collection_films', function (Blueprint $table) {
            $table->dropColumn(['sequence', 'note']);
        });
    }
};
