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
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->enum('section_type', ['hero', 'content', 'gallery', 'testimonials', 'features', 'stats', 'cta', 'contact', 'custom'])->default('content');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->string('image_url')->nullable();
            $table->json('settings')->nullable();
            $table->integer('position')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['page_id', 'position']);
            $table->index(['page_id', 'status']);
            $table->index(['section_type']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
