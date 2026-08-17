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
        Schema::create('api_health_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service'); // e.g. 'moviebox', 'anichin', 'nvidia', 'itunes', 'dicebear'
            $table->string('host')->nullable(); // specific host URL/domain, e.g. MovieBox 7 hosts
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->integer('status_code')->nullable();
            $table->integer('latency_ms')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();

            $table->index(['service', 'checked_at']);
            $table->index(['service', 'host', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_health_logs');
    }
};
