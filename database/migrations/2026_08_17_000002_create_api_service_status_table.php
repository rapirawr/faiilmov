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
        Schema::create('api_service_status', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('host')->nullable();
            $table->enum('current_status', ['up', 'degraded', 'down'])->default('up');
            $table->integer('consecutive_failures')->default(0);
            $table->decimal('uptime_24h', 5, 2)->default(100.00);
            $table->integer('avg_latency_ms')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['service', 'host']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_service_status');
    }
};
