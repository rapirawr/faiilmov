<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('films', 'ai_embeddings')) {
            Schema::table('films', function (Blueprint $table) {
                $table->json('ai_embeddings')->nullable()->after('synopsis');
            });
        }
    }

    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn('ai_embeddings');
        });
    }
};
