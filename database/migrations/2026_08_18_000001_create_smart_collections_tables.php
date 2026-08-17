<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. film_tags
        if (!Schema::hasTable('film_tags')) {
            Schema::create('film_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('film_id')->constrained('films')->cascadeOnDelete();
                $table->enum('tag_type', ['franchise', 'universe', 'genre_mood', 'era']);
                $table->string('tag_value', 191);
                $table->decimal('confidence', 3, 2)->default(1.00);
                $table->enum('source', ['llm', 'relation', 'manual'])->default('llm');
                $table->timestamps();

                $table->index(['tag_type', 'tag_value']);
                $table->index('film_id');
            });
        }

        // 2. film_embeddings
        if (!Schema::hasTable('film_embeddings')) {
            Schema::create('film_embeddings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('film_id')->unique()->constrained('films')->cascadeOnDelete();
                $table->json('embedding');
                $table->string('model_version', 64)->default('text-embedding-004');
                $table->timestamps();
            });
        }

        // 3. collections
        if (!Schema::hasTable('collections')) {
            Schema::create('collections', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191);
                $table->string('slug', 191)->unique();
                $table->enum('type', ['auto', 'prompt', 'manual'])->default('auto');
                $table->text('description')->nullable();
                $table->string('cover_image', 255)->nullable();
                $table->string('source_tag', 191)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->timestamps();

                $table->index(['type', 'status']);
                $table->index('source_tag');
            });
        }

        // 4. collection_films
        if (!Schema::hasTable('collection_films')) {
            Schema::create('collection_films', function (Blueprint $table) {
                $table->id();
                $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
                $table->foreignId('film_id')->constrained('films')->cascadeOnDelete();
                $table->enum('added_by', ['system', 'admin', 'user'])->default('system');
                $table->timestamps();

                $table->unique(['collection_id', 'film_id']);
            });
        }

        // 5. collection_watch_orders
        if (!Schema::hasTable('collection_watch_orders')) {
            Schema::create('collection_watch_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
                $table->foreignId('film_id')->constrained('films')->cascadeOnDelete();
                $table->enum('order_type', ['release', 'chronological']);
                $table->integer('sequence')->default(1);
                $table->string('note', 255)->nullable();
                $table->timestamps();

                $table->unique(['collection_id', 'film_id', 'order_type']);
                $table->index(['collection_id', 'order_type', 'sequence']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_watch_orders');
        Schema::dropIfExists('collection_films');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('film_embeddings');
        Schema::dropIfExists('film_tags');
    }
};
