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
        Schema::table('watch_parties', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('status');
        });

        Schema::table('watch_party_participants', function (Blueprint $table) {
            $table->boolean('is_muted')->default(false)->after('is_host');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watch_parties', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });

        Schema::table('watch_party_participants', function (Blueprint $table) {
            $table->dropColumn('is_muted');
        });
    }
};
