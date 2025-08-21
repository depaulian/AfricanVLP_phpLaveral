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
        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->enum('category', ['general', 'announcements', 'events', 'volunteering', 'alumni', 'support'])->default('general');
            $table->enum('status', ['active', 'locked', 'archived'])->default('active');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->integer('views_count')->default(0);
            $table->datetime('last_post_at')->nullable();
            $table->unsignedBigInteger('last_post_user_id')->nullable();
            $table->datetime('created');
            $table->datetime('modified');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('last_post_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['organization_id', 'status', 'is_pinned']);
            $table->index(['last_post_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_threads');
    }
};