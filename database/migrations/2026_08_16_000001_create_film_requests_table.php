<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('film_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['movie', 'tv', 'dracin'])->default('movie');
            $table->integer('year')->nullable();
            $table->enum('status', ['pending', 'searching', 'added', 'rejected'])->default('pending');
            $table->integer('request_count')->default(1);
            $table->foreignId('matched_film_id')->nullable()->constrained('films')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('film_requests');
    }
};
