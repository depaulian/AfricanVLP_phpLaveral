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
        Schema::create('user_feedback_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_feedback_id')->constrained('user_feedback')->onDelete('cascade');
            $table->foreignId('user_feedback_response_id')->nullable()->constrained('user_feedback_responses')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Indexes with custom short names
            $table->index(['user_feedback_id', 'created'], 'ufa_feedback_id_created_idx');
            $table->index(['user_feedback_response_id', 'created'], 'ufa_response_id_created_idx');
            $table->index(['uploaded_by', 'created'], 'ufa_uploaded_by_created_idx');
            $table->index('mime_type', 'ufa_mime_type_idx');
            $table->index('created', 'ufa_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedback_attachments');
    }
};