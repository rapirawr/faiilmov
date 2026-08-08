<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add MySQL FULLTEXT index on films.title and films.synopsis for fast full-text search
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE films ADD FULLTEXT INDEX ft_films_title_synopsis (title, synopsis)');
        }

        // Create search_logs table for tracking popular queries
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->unsignedInteger('result_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('query');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE films DROP INDEX ft_films_title_synopsis');
        Schema::dropIfExists('search_logs');
    }
};
