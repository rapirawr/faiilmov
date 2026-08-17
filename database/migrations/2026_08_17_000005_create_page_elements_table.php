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
        Schema::create('page_elements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Internal label for admin');
            $table->enum('type', [
                'broadcast_bar',
                'floating_widget',
                'popup_modal',
                'custom_block',
                'promo_banner'
            ])->default('broadcast_bar');

            // Content & visuals
            $table->string('title')->nullable();
            $table->text('content')->nullable()->comment('Text, markdown or body description');
            $table->string('image_url')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->string('button_target')->default('_self')->comment('_self or _blank');

            // Appearance & style
            $table->string('icon')->nullable()->comment('Lucide icon name');
            $table->string('position')->default('top')->comment('top, bottom, bottom_right, bottom_left, header, footer, content_top, content_bottom');
            $table->string('theme_color')->default('amber')->comment('amber, blue, emerald, rose, purple, zinc, custom');
            $table->text('custom_css')->nullable();
            $table->mediumText('custom_html')->nullable();

            // Targeting
            $table->string('target_page')->default('all')->comment('all, home, watch, detail, custom');
            $table->string('target_path_pattern')->nullable()->comment('URL regex or path pattern if custom');
            $table->enum('target_device', ['all', 'desktop', 'mobile'])->default('all');
            $table->enum('target_audience', ['all', 'guest', 'user'])->default('all');

            // Dismissal & Lifetime
            $table->boolean('is_dismissible')->default(true);
            $table->integer('dismiss_duration_hours')->default(24)->comment('0 for session, 24 for daily, -1 for forever');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            // Scheduling
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['type', 'is_active', 'order']);
            $table->index(['target_page', 'target_device', 'target_audience']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_elements');
    }
};
