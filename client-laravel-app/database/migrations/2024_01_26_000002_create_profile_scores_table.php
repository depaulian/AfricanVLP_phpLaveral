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
        Schema::create('profile_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->integer('completion_score')->default(0);
            $table->integer('quality_score')->default(0);
            $table->integer('engagement_score')->default(0);
            $table->integer('verification_score')->default(0);
            $table->integer('total_score')->default(0);
            $table->integer('rank_position')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index(['total_score']);
            $table->index(['rank_position']);
            $table->index(['last_calculated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_scores');
    }
};