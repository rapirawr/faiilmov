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
        // Add Admin & Ban fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('password');
            }
            if (!Schema::hasColumn('users', 'is_banned')) {
                $table->boolean('is_banned')->default(false)->after('is_admin');
            }
            if (!Schema::hasColumn('users', 'banned_reason')) {
                $table->string('banned_reason')->nullable()->after('is_banned');
            }
            if (!Schema::hasColumn('users', 'banned_until')) {
                $table->timestamp('banned_until')->nullable()->after('banned_reason');
            }
        });

        // Add view_count and deleted_at (SoftDeletes) to films table
        Schema::table('films', function (Blueprint $table) {
            if (!Schema::hasColumn('films', 'view_count')) {
                $table->unsignedBigInteger('view_count')->default(0)->after('rating');
            }
            if (!Schema::hasColumn('films', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Create Settings table
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Create Admin Activity Logs table
        if (!Schema::hasTable('admin_activity_logs')) {
            Schema::create('admin_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
                $table->string('action');
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Create Review Reports table
        if (!Schema::hasTable('review_reports')) {
            Schema::create('review_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained('reviews')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('reason');
                $table->string('status')->default('pending'); // pending, resolved, dismissed
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_reports');
        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('settings');

        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn(['view_count', 'deleted_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'is_banned', 'banned_reason', 'banned_until']);
        });
    }
};
