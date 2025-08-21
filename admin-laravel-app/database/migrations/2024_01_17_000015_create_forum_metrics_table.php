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
        Schema::create('forum_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric_type'); // daily_active_users, posts_created, threads_created, etc.
            $table->morphs('entity'); // Can be forum, organization, or null for global
            $table->bigInteger('value');
            $table->json('breakdown')->nullable(); // Additional metric breakdown
            $table->timestamps();
            
            $table->unique(['date', 'metric_type', 'entity_type', 'entity_id']);
            $table->index(['date', 'metric_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_metrics');
    }
};