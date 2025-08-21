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
        Schema::create('skill_endorsements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained('user_skills')->onDelete('cascade');
            $table->foreignId('endorser_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('request_message')->nullable();
            $table->text('endorsement_comment')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('endorsed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['skill_id', 'status']);
            $table->index(['endorser_id', 'status']);
            $table->index('endorsed_at');
            
            // Unique constraint to prevent duplicate endorsements
            $table->unique(['skill_id', 'endorser_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_endorsements');
    }
};