<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE films MODIFY COLUMN subject_type VARCHAR(20) NOT NULL DEFAULT 'movie'");
        } catch (\Exception $e) {
            Schema::table('films', function (Blueprint $table) {
                $table->string('subject_type', 20)->default('movie')->change();
            });
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE films MODIFY COLUMN subject_type ENUM('movie', 'series') NOT NULL DEFAULT 'movie'");
        } catch (\Exception $e) {
            Schema::table('films', function (Blueprint $table) {
                $table->enum('subject_type', ['movie', 'series'])->default('movie')->change();
            });
        }
    }
};
