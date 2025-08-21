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
        Schema::create('support_ticket_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_solution')->default(false);
            $table->integer('response_time_minutes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['support_ticket_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_internal');
            $table->index('is_solution');

            // Foreign key constraints
            $table->foreign('support_ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_responses');
    }
};
