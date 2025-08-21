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
        Schema::create('forum_user_reputation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_points')->default(0);
            $table->integer('post_points')->default(0);
            $table->integer('vote_points')->default(0);
            $table->integer('solution_points')->default(0);
            $table->integer('badge_points')->default(0);
            $table->string('rank')->default('Newcomer');
            $table->integer('rank_level')->default(1);
            $table->integer('posts_count')->default(0);
            $table->integer('threads_count')->default(0);
            $table->integer('votes_received')->default(0);
            $table->integer('solutions_provided')->default(0);
            $table->integer('consecutive_days_active')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['total_points']);
            $table->index(['rank_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_user_reputation');
    }
};