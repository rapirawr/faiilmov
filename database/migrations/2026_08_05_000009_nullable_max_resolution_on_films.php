<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change column to nullable first
        Schema::table('films', function (Blueprint $table) {
            $table->string('max_resolution')->nullable()->default(null)->change();
        });

        // Reset all rows with the old default '1080P' to null (unverified)
        DB::table('films')->where('max_resolution', '1080P')->update(['max_resolution' => null]);
    }

    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->string('max_resolution')->default('1080P')->change();
        });
        DB::table('films')->whereNull('max_resolution')->update(['max_resolution' => '1080P']);
    }
};
