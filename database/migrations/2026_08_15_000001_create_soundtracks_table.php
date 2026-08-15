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
        Schema::create('soundtracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained('films')->cascadeOnDelete();
            $table->string('track_name');
            $table->string('artist_name');
            $table->string('collection_name')->nullable();
            $table->text('preview_audio_url')->nullable();
            $table->text('artwork_url')->nullable();
            $table->text('track_view_url')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('film_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soundtracks');
    }
};
