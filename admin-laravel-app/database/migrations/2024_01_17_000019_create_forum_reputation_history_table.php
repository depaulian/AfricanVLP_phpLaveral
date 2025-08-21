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
        Schema::create('forum_reputation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'post_created', 'vote_received', 'solution_marked', etc.
            $table->integer('points_change');
            $table->integer('points_before');
            $table->integer('points_after');
            $table->string('source_type')->nullable(); // 'forum_post', 'forum_thread', etc.
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['source_type', 'source_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_reputation_history');
    }
};