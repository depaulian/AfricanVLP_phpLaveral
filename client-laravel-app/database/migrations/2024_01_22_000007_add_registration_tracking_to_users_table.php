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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('registration_completed_at')->nullable()->after('email_verified_at');
            $table->boolean('onboarding_completed')->default(false)->after('registration_completed_at');
            $table->json('registration_metadata')->nullable()->after('onboarding_completed');
            
            // Add indexes for performance
            $table->index('registration_completed_at');
            $table->index('onboarding_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['registration_completed_at']);
            $table->dropIndex(['onboarding_completed']);
            
            $table->dropColumn([
                'registration_completed_at',
                'onboarding_completed',
                'registration_metadata'
            ]);
        });
    }
};