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
        // 1. Seasons Table
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained('films')->onDelete('cascade');
            $table->integer('season_number');
            $table->string('title')->nullable();
            $table->string('poster_url')->nullable();
            $table->integer('release_year')->nullable();
            $table->timestamps();

            $table->unique(['film_id', 'season_number']);
        });

        // 2. Episodes Table
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->integer('episode_number');
            $table->string('title');
            $table->text('synopsis')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->string('thumbnail_url')->nullable();
            $table->text('video_source')->nullable();
            $table->timestamps();

            $table->unique(['season_id', 'episode_number']);
        });

        // 3. User Watch Histories (Last watched episode & progress per user/film)
        Schema::create('watch_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('film_id')->constrained('films')->onDelete('cascade');
            $table->integer('season_number')->default(1);
            $table->integer('episode_number')->default(1);
            $table->integer('progress_seconds')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'film_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('seasons');
    }
};
