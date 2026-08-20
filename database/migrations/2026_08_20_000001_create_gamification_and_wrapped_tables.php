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
        // 1. Add gamification columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'xp_total')) {
                $table->unsignedBigInteger('xp_total')->default(0)->index()->after('email');
            }
            if (!Schema::hasColumn('users', 'current_level')) {
                $table->unsignedInteger('current_level')->default(1)->after('xp_total');
            }
            if (!Schema::hasColumn('users', 'streak_count')) {
                $table->unsignedInteger('streak_count')->default(0)->after('current_level');
            }
            if (!Schema::hasColumn('users', 'last_watch_date')) {
                $table->date('last_watch_date')->nullable()->after('streak_count');
            }
            if (!Schema::hasColumn('users', 'is_anonymous_leaderboard')) {
                $table->boolean('is_anonymous_leaderboard')->default(false)->after('last_watch_date');
            }
        });

        // 2. Create user_xp_logs table
        if (!Schema::hasTable('user_xp_logs')) {
            Schema::create('user_xp_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('amount');
                $table->string('source', 50)->index(); // watch_time, daily_streak, review, comment, watch_party
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index(['user_id', 'created_at']);
                $table->index(['source', 'created_at']);
            });
        }

        // 3. Create badges catalog table
        if (!Schema::hasTable('badges')) {
            Schema::create('badges', function (Blueprint $table) {
                $table->id();
                $table->string('code', 60)->unique();
                $table->string('name', 100);
                $table->text('description');
                $table->string('category', 40)->default('milestone')->index(); // genre, habit, community, milestone
                $table->string('icon', 50)->default('award'); // Lucide icon name (No raw emojis)
                $table->string('color', 30)->default('amber'); // Tailwind color scheme (e.g. amber, purple, rose, cyan)
                $table->integer('xp_reward')->default(50);
                $table->integer('required_count')->default(1);
                $table->timestamps();
            });
        }

        // 4. Create user_badges pivot table
        if (!Schema::hasTable('user_badges')) {
            Schema::create('user_badges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
                $table->timestamp('unlocked_at')->useCurrent();

                $table->unique(['user_id', 'badge_id']);
                $table->index(['user_id', 'unlocked_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('user_xp_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'xp_total',
                'current_level',
                'streak_count',
                'last_watch_date',
                'is_anonymous_leaderboard',
            ]);
        });
    }
};
