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
        Schema::table('forum_threads', function (Blueprint $table) {
            // Add new columns
            $table->unsignedBigInteger('forum_id')->nullable()->after('id');
            $table->string('slug', 255)->nullable()->after('title');
            $table->text('content')->nullable()->after('description');
            $table->unsignedBigInteger('author_id')->nullable()->after('content');
            $table->integer('view_count')->default(0)->after('views_count');
            $table->integer('reply_count')->default(0)->after('view_count');
            $table->timestamp('last_reply_at')->nullable()->after('last_post_at');
            $table->unsignedBigInteger('last_reply_by')->nullable()->after('last_reply_at');
            $table->softDeletes()->after('modified');
            
            // Update status enum to match design
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active')->change();
            
            // Add foreign key for forum_id
            $table->foreign('forum_id')->references('id')->on('forums')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('last_reply_by')->references('id')->on('users')->onDelete('set null');
            
            // Add new indexes
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
        Schema::table('forum_threads', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['forum_id']);
            $table->dropForeign(['author_id']);
            $table->dropForeign(['last_reply_by']);
            
            // Drop indexes
            $table->dropIndex(['forum_id']);
            $table->dropIndex(['author_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['is_pinned']);
            $table->dropIndex(['last_reply_at']);
            
            // Drop columns
            $table->dropColumn([
                'forum_id', 'slug', 'content', 'author_id', 
                'view_count', 'reply_count', 'last_reply_at', 
                'last_reply_by', 'deleted_at'
            ]);
            
            // Revert status enum
            $table->enum('status', ['active', 'locked', 'archived'])->default('active')->change();
        });
    }
};