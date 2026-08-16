<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_banners', function (Blueprint $table) {
            $table->string('bg_gradient_from')->nullable()->after('bg_gradient');
            $table->string('bg_gradient_to')->nullable()->after('bg_gradient_from');
        });
    }

    public function down(): void
    {
        Schema::table('feature_banners', function (Blueprint $table) {
            $table->dropColumn(['bg_gradient_from', 'bg_gradient_to']);
        });
    }
};
