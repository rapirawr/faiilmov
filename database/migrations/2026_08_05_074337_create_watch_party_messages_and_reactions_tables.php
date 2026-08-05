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
        Schema::create('watch_party_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_party_id')->constrained('watch_parties')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('sender_name');
            $table->text('message');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('watch_party_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_party_id')->constrained('watch_parties')->onDelete('cascade');
            $table->string('sender_name');
            $table->string('emoji', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_party_reactions');
        Schema::dropIfExists('watch_party_messages');
    }
};
