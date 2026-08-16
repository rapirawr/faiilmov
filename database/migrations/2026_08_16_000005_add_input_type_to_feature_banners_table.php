<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_banners', function (Blueprint $table) {
            $table->string('input_type')->default('text')->after('placeholder_text');
        });
    }

    public function down(): void
    {
        Schema::table('feature_banners', function (Blueprint $table) {
            $table->dropColumn('input_type');
        });
    }
};
