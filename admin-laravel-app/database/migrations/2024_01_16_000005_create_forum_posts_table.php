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
            $table->unsignedBigInteger('thread_id');
            $table->text('content');
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('parent_post_id')->nullable();
            $table->integer('upvotes')->default(0);
            $table->integer('downvotes')->default(0);
            $table->boolean('is_solution')->default(false);
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_post_id')->references('id')->on('forum_posts')->onDelete('cascade');
            
            $table->index(['thread_id']);
            $table->index(['author_id']);
            $table->index(['parent_post_id']);
            $table->index(['status']);
            $table->index(['is_solution']);
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