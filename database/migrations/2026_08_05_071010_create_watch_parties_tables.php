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
        Schema::create('watch_parties', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 10)->unique();
            $table->foreignId('film_id')->constrained('films')->onDelete('cascade');
            $table->integer('season_number')->default(1);
            $table->integer('episode_number')->default(1);
            $table->foreignId('host_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('host_guest_name')->nullable()->default('Host');
            $table->string('status')->default('waiting'); // waiting, playing, ended
            $table->double('current_position_seconds', 8, 2)->default(0);
            $table->boolean('is_playing')->default(false);
            $table->float('playback_speed')->default(1.0);
            $table->timestamps();
        });

        Schema::create('watch_party_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_party_id')->constrained('watch_parties')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('guest_name')->default('Guest');
            $table->string('session_id');
            $table->boolean('is_host')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_party_participants');
        Schema::dropIfExists('watch_parties');
    }
};
