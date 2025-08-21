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
        Schema::create('forum_user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('forum_badge_id')->constrained()->onDelete('cascade');
            $table->timestamp('earned_at');
            $table->json('earning_context')->nullable(); // Context about how badge was earned
            $table->boolean('is_featured')->default(false); // Whether to display prominently
            $table->timestamps();

            $table->unique(['user_id', 'forum_badge_id']);
            $table->index(['user_id']);
            $table->index(['forum_badge_id']);
            $table->index(['earned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_user_badges');
    }
};