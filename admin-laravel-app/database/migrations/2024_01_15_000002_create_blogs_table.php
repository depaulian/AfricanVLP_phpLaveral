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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image', 255)->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->integer('reading_time')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('blog_category_id')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status', 'blogs_status_index');
            $table->index('is_featured', 'blogs_is_featured_index');
            $table->index('published_at', 'blogs_published_at_index');
            $table->index('author_id', 'blogs_author_id_foreign');
            $table->index('organization_id', 'blogs_organization_id_foreign');
            $table->index('blog_category_id', 'blogs_blog_category_id_foreign');
            
            // Full text search index
            $table->fullText(['title', 'content'], 'blogs_title_content_fulltext');
            
            // Foreign key constraints
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('blog_category_id')->references('id')->on('blog_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};