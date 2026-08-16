<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_banners', function (Blueprint $table) {
            $table->id();
            $table->string('badge_text')->default('FITUR BARU: REQUEST FILM');
            $table->string('title');
            $table->text('description');
            $table->string('placeholder_text')->nullable()->default('Tulis judul film yang kamu minta...');
            $table->string('button_text')->default('Request Sekarang');
            $table->enum('action_type', ['request_modal', 'url_link'])->default('request_modal');
            $table->string('action_url')->nullable();
            $table->string('bg_gradient')->default('amber_purple');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default initial banner (Request Film)
        DB::table('feature_banners')->insert([
            'badge_text' => 'FITUR BARU: REQUEST FILM',
            'title' => 'Punya Film / Series Favorit yang Belum Ada?',
            'description' => 'Tuliskan judul film, drama China, atau serial TV yang kamu cari. Sistem otomatis kami akan langsung mencarikan dan mengimpornya untuk kamu tonton secara gratis!',
            'placeholder_text' => 'Tulis judul film yang kamu minta...',
            'button_text' => 'Request Sekarang',
            'action_type' => 'request_modal',
            'action_url' => null,
            'bg_gradient' => 'amber_purple',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_banners');
    }
};
