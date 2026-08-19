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
        Schema::create('episode_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained('films')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('season_number')->default(1);
            $table->unsignedInteger('episode_number')->default(1);
            $table->foreignId('episode_id')->nullable()->constrained('episodes')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('episode_comments')->cascadeOnDelete();
            $table->text('comment');
            $table->boolean('is_spoiler')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['film_id', 'season_number', 'episode_number']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episode_comments');
    }
};
