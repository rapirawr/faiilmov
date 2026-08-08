<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('parental_pin')->nullable()->after('banned_until');
            $table->string('max_allowed_rating')->nullable()->default('R')->after('parental_pin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['parental_pin', 'max_allowed_rating']);
        });
    }
};
