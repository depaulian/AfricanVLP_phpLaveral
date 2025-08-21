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
        Schema::create('tagged_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_tag_id');
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
            $table->unsignedBigInteger('tagged_by')->nullable();
            $table->timestamp('tagged_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('content_tag_id')->references('id')->on('content_tags')->onDelete('cascade');
            $table->foreign('tagged_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['content_tag_id']);
            $table->index(['taggable_type', 'taggable_id']);
            $table->index(['tagged_by']);
            $table->index(['tagged_at']);
            $table->unique(['content_tag_id', 'taggable_type', 'taggable_id'], 'unique_tag_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagged_contents');
    }
};
