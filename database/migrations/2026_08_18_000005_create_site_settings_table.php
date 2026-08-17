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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // Branding
            $table->string('site_name')->default('Faiilmov');
            $table->string('site_tagline')->nullable()->default('Streaming Movie, Anime & TV Series Subtitle Indonesia');
            $table->string('logo_path')->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('favicon_path')->nullable();

            // Warna Tema (Theme Colors)
            $table->string('primary_color')->default('#ffffff');
            $table->string('secondary_color')->nullable()->default('#a1a1aa');
            $table->string('accent_color')->nullable()->default('#f59e0b');
            $table->string('background_color')->nullable()->default('#09090b');

            // SEO Default
            $table->string('seo_meta_title')->default('Faiilmov | Nonton Film & TV Series Streaming Subtitle Indonesia');
            $table->text('seo_meta_description')->nullable();
            $table->string('seo_meta_keywords')->nullable();
            $table->string('seo_og_image')->nullable();
            $table->string('seo_canonical_url')->nullable();

            // Tampilan Umum
            $table->text('footer_text')->nullable();
            $table->json('social_links')->nullable();
            $table->string('contact_email')->nullable()->default('support@faiilmov.my.id');

            // Maintenance Mode
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
