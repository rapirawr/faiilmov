<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('films', function (Blueprint $table) {
            $table->id();
            $table->string('moviebox_subject_id')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis')->nullable();
            $table->integer('release_year')->default(2024);
            $table->integer('duration_minutes')->default(120);
            $table->string('poster_url')->nullable();
            $table->string('backdrop_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->decimal('rating', 3, 1)->default(0.0);
            $table->enum('subject_type', ['movie', 'series'])->default('movie');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('films');
    }
};
