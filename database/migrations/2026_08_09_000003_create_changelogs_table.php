<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version'); // e.g. v2.4.0
            $table->string('title');
            $table->string('type')->default('minor'); // major, minor, patch, security
            $table->date('release_date')->nullable();
            $table->text('summary')->nullable();
            $table->json('changes')->nullable(); // [{type: 'feature|fix|improvement', text: '...'}]
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelogs');
    }
};
