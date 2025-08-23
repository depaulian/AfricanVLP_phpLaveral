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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('news_category_id')->nullable();
            $table->string('title', 500)->nullable();
            $table->string('slug', 500)->unique(); // Added slug field
            $table->text('excerpt')->nullable(); // Added excerpt field
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image', 255)->nullable(); // Added featured_image field
            $table->string('image', 255)->nullable();
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->datetime('published_at')->nullable();
            $table->string('author', 100)->nullable();
            $table->string('source', 255)->nullable();
            $table->timestamps();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
            $table->foreign('news_category_id')->references('id')->on('news_categories')->onDelete('set null');
            
            $table->index(['status', 'published_at']);
            $table->index(['organization_id', 'status']);
            $table->index('slug'); // Added slug index
            $table->fullText(['title', 'excerpt', 'description', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};