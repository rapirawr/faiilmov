<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add poster_visual_summary & visual_style to films
        Schema::table('films', function (Blueprint $table) {
            if (!Schema::hasColumn('films', 'poster_visual_summary')) {
                $table->text('poster_visual_summary')->nullable()->after('synopsis');
            }
            if (!Schema::hasColumn('films', 'visual_style')) {
                $table->string('visual_style', 64)->nullable()->after('poster_visual_summary');
            }
        });

        // 2. Modify film_tags table column types if MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE film_tags MODIFY COLUMN tag_type VARCHAR(64) NOT NULL");
            DB::statement("ALTER TABLE film_tags MODIFY COLUMN source VARCHAR(32) NOT NULL DEFAULT 'llm'");
        }
    }

    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn(['poster_visual_summary', 'visual_style']);
        });
    }
};
