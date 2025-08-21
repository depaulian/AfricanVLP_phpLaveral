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
        Schema::create('profile_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('achievement_type', [
                'profile_completion',
                'skill_verification',
                'document_upload',
                'volunteering_history',
                'social_connection',
                'platform_engagement'
            ]);
            $table->string('achievement_name');
            $table->text('achievement_description');
            $table->string('badge_icon')->nullable();
            $table->string('badge_color')->default('blue');
            $table->integer('points_awarded')->default(0);
            $table->timestamp('earned_at');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'achievement_type']);
            $table->index(['earned_at']);
            $table->index(['is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_achievements');
    }
};