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
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('image', 255)->nullable();
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->datetime('published_at')->nullable();
            $table->string('author', 100)->nullable();
            $table->string('source', 255)->nullable();
            $table->datetime('created');
            $table->datetime('modified');
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
            
            $table->index(['status', 'published_at']);
            $table->index(['organization_id', 'status']);
            $table->fullText(['title', 'description', 'content']);
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