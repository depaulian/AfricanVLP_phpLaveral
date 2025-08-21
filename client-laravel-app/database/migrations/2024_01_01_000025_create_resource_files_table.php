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
        Schema::create('resource_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('filename');
            $table->string('path');
            $table->bigInteger('size');
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->boolean('is_image')->default(false);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('fileable_type')->nullable();
            $table->unsignedBigInteger('fileable_id')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('download_count')->default(0);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['fileable_type', 'fileable_id']);
            $table->index(['uploaded_by', 'created']);
            $table->index(['category', 'is_public']);
            $table->index(['is_image', 'is_public']);
            $table->index('extension');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_files');
    }
};