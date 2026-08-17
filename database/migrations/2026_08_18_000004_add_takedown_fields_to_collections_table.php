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
        Schema::table('collections', function (Blueprint $table) {
            if (!Schema::hasColumn('collections', 'takedown_reason')) {
                $table->text('takedown_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('collections', 'taken_down_at')) {
                $table->timestamp('taken_down_at')->nullable()->after('takedown_reason');
            }
            if (!Schema::hasColumn('collections', 'taken_down_by')) {
                $table->foreignId('taken_down_by')->nullable()->constrained('users')->nullOnDelete()->after('taken_down_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['taken_down_by']);
            $table->dropColumn(['takedown_reason', 'taken_down_at', 'taken_down_by']);
        });
    }
};
