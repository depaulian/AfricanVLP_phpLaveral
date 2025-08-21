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
        Schema::table('forum_posts', function (Blueprint $table) {
            // Rename foreign key column to match design
            $table->renameColumn('forum_thread_id', 'thread_id');
            $table->renameColumn('user_id', 'author_id');
            
            // Add new columns
            $table->unsignedBigInteger('parent_post_id')->nullable()->after('author_id');
            $table->integer('upvotes')->default(0)->after('parent_post_id');
            $table->integer('downvotes')->default(0)->after('upvotes');
            $table->boolean('is_solution')->default(false)->after('downvotes');
            $table->softDeletes()->after('modified');
            
            // Update status enum to match design
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active')->change();
            
            // Add foreign key for parent_post_id
            $table->foreign('parent_post_id')->references('id')->on('forum_posts')->onDelete('cascade');
            
            // Add new indexes
            $table->index(['parent_post_id']);
            $table->index(['is_solution']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            // Drop foreign key and indexes
            $table->dropForeign(['parent_post_id']);
            $table->dropIndex(['parent_post_id']);
            $table->dropIndex(['is_solution']);
            
            // Drop columns
            $table->dropColumn([
                'parent_post_id', 'upvotes', 'downvotes', 
                'is_solution', 'deleted_at'
            ]);
            
            // Rename columns back
            $table->renameColumn('thread_id', 'forum_thread_id');
            $table->renameColumn('author_id', 'user_id');
            
            // Revert status enum
            $table->enum('status', ['active', 'deleted', 'moderated'])->default('active')->change();
        });
    }
};