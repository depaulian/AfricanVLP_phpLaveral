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
        Schema::create('forum_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->string('target_type'); // thread, post, user, report
            $table->unsignedBigInteger('target_id');
            $table->string('action_type'); // pin, lock, delete, warn, suspend, ban, etc.
            $table->json('details')->nullable(); // Additional context about the action
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['moderator_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->index(['action_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_moderation_logs');
    }
};