<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('film_request_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_request_id')->constrained('film_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['film_request_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('film_request_user');
    }
};
