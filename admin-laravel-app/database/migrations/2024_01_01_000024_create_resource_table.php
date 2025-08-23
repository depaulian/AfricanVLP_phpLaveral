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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('resource_type_id')->nullable();
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->json('tags')->nullable();
            $table->string('author', 255)->nullable();
            $table->datetime('published_date')->nullable();
            $table->string('language', 10)->default('en');
            $table->enum('access_level', ['public', 'members', 'restricted'])->default('public');
            $table->string('external_url', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('resource_type_id')->references('id')->on('resource_types')->onDelete('set null');
            
            $table->index(['status', 'published_date']);
            $table->index(['organization_id', 'status']);
            $table->index(['resource_type_id', 'status']);
            $table->index('featured');
            $table->index('access_level');
            $table->fullText(['title', 'description', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};