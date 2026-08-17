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
        Schema::create('film_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained('films')->onDelete('cascade');
            $table->date('date');
            $table->integer('views')->default(0);
            $table->integer('unique_viewers')->default(0);
            $table->bigInteger('watch_time_seconds')->default(0);
            $table->decimal('completion_rate', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['film_id', 'date']);
            $table->index(['date', 'views']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('film_daily_stats');
    }
};
