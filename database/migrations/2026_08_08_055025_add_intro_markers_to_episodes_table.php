<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->integer('intro_start_seconds')->nullable()->after('duration_minutes');
            $table->integer('intro_end_seconds')->nullable()->after('intro_start_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['intro_start_seconds', 'intro_end_seconds']);
        });
    }
};
