<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->foreignId('profile_id')->nullable()->after('user_id')->constrained('profiles')->onDelete('cascade');
        });

        Schema::table('watchlists', function (Blueprint $table) {
            $table->foreignId('profile_id')->nullable()->after('user_id')->constrained('profiles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->dropForeign(['profile_id']);
            $table->dropColumn('profile_id');
        });

        Schema::table('watchlists', function (Blueprint $table) {
            $table->dropForeign(['profile_id']);
            $table->dropColumn('profile_id');
        });
    }
};
