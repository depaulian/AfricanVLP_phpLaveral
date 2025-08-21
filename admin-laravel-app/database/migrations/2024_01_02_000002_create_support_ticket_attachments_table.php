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
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_ticket_id');
            $table->unsignedBigInteger('support_ticket_response_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('file_type', 10);
            $table->timestamps();

            // Indexes with custom shorter names
            $table->index(['support_ticket_id', 'created_at'], 'sta_ticket_id_created_idx');
            $table->index(['support_ticket_response_id', 'created_at'], 'sta_response_id_created_idx');
            $table->index('user_id', 'sta_user_id_idx');

            // Foreign key constraints with custom names
            $table->foreign('support_ticket_id', 'sta_ticket_id_fk')
                  ->references('id')
                  ->on('support_tickets')
                  ->onDelete('cascade');
            
            $table->foreign('support_ticket_response_id', 'sta_response_id_fk')
                  ->references('id')
                  ->on('support_ticket_responses')
                  ->onDelete('cascade');
            
            $table->foreign('user_id', 'sta_user_id_fk')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
    }
};