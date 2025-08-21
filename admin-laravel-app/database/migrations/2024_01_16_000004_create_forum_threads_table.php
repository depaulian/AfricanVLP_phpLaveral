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
            $table->unsignedBigInteger('forum_id');
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('content');
            $table->unsignedBigInteger('author_id');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->timestamp('last_reply_at')->nullable();
            $table->unsignedBigInteger('last_reply_by')->nullable();
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('forum_id')->references('id')->on('forums')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('last_reply_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['forum_id']);
            $table->index(['author_id']);
            $table->index(['status']);
            $table->index(['is_pinned']);
            $table->index(['last_reply_at']);
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