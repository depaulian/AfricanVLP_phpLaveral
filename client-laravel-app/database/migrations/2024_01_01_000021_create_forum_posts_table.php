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
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('forum_thread_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('content');
            $table->enum('status', ['active', 'deleted', 'moderated'])->default('active');
            $table->boolean('is_edited')->default(false);
            $table->datetime('edited_at')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->integer('likes_count')->default(0);
            $table->datetime('created');
            $table->datetime('modified');
            
            $table->foreign('forum_thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['forum_thread_id', 'status', 'created']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};