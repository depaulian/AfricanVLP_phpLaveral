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
        Schema::create('volunteer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('volunteer_assignments')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade'); // Who is giving the feedback
            $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade'); // Who is being reviewed
            $table->enum('feedback_type', ['volunteer_to_organization', 'organization_to_volunteer', 'supervisor_to_volunteer', 'volunteer_to_supervisor', 'beneficiary_to_volunteer']);
            $table->enum('reviewer_type', ['volunteer', 'supervisor', 'organization_admin', 'beneficiary']);
            $table->foreignId('template_id')->nullable()->constrained('feedback_templates')->onDelete('set null');
            
            // Rating fields (1-5 scale)
            $table->decimal('overall_rating', 2, 1)->nullable();
            $table->decimal('communication_rating', 2, 1)->nullable();
            $table->decimal('reliability_rating', 2, 1)->nullable();
            $table->decimal('skill_rating', 2, 1)->nullable();
            $table->decimal('attitude_rating', 2, 1)->nullable();
            $table->decimal('impact_rating', 2, 1)->nullable();
            
            // Feedback content
            $table->text('positive_feedback')->nullable();
            $table->text('improvement_feedback')->nullable();
            $table->text('additional_comments')->nullable();
            
            // Structured feedback
            $table->json('structured_ratings')->nullable(); // For category-specific ratings
            $table->json('tags')->nullable(); // Predefined tags like 'punctual', 'creative', 'helpful'
            
            // Feedback metadata
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_public')->default(false);
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'published'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Response and follow-up
            $table->text('response')->nullable(); // Response from the reviewee
            $table->timestamp('response_at')->nullable();
            $table->boolean('follow_up_requested')->default(false);
            $table->timestamp('follow_up_scheduled_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['assignment_id', 'feedback_type']);
            $table->index(['reviewer_id', 'reviewee_id']);
            $table->index(['feedback_type', 'status']);
            $table->index('overall_rating');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_feedback');
    }
};