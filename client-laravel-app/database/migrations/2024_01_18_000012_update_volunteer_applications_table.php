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
        Schema::table('volunteer_applications', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('volunteer_applications', 'experience')) {
                $table->text('experience')->nullable()->after('availability');
            }
            
            if (!Schema::hasColumn('volunteer_applications', 'skills')) {
                $table->json('skills')->nullable()->after('experience');
            }
            
            if (!Schema::hasColumn('volunteer_applications', 'withdrawal_reason')) {
                $table->text('withdrawal_reason')->nullable()->after('reviewer_notes');
            }
            
            if (!Schema::hasColumn('volunteer_applications', 'withdrawn_at')) {
                $table->timestamp('withdrawn_at')->nullable()->after('withdrawal_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->dropColumn(['experience', 'skills', 'withdrawal_reason', 'withdrawn_at']);
        });
    }
};