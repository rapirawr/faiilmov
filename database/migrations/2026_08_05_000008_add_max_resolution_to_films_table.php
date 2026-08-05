<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->string('max_resolution')->default('1080P')->after('subject_type');
        });
    }

    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn('max_resolution');
        });
    }
};
