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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->text('requirements')->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('benefits')->nullable();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('opportunity_categories')->onDelete('set null');
            $table->enum('type', ['volunteer', 'internship', 'job', 'fellowship', 'scholarship', 'grant', 'competition'])->default('volunteer');
            $table->string('location')->nullable();
            $table->boolean('remote_allowed')->default(false);
            $table->string('duration')->nullable(); // e.g., "3 months", "1 year"
            $table->string('time_commitment')->nullable(); // e.g., "10 hours/week", "Full-time"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('application_deadline');
            $table->enum('status', ['draft', 'active', 'paused', 'closed', 'archived'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('external_url')->nullable();
            $table->json('skills_required')->nullable();
            $table->enum('experience_level', ['entry', 'intermediate', 'senior', 'executive'])->default('entry');
            $table->json('language_requirements')->nullable();
            $table->string('age_requirements')->nullable();
            $table->string('education_requirements')->nullable();
            $table->integer('max_applicants')->nullable();
            $table->integer('current_applicants')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('applications_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes with custom short names
            $table->index(['status', 'application_deadline'], 'opp_status_deadline_idx');
            $table->index(['featured', 'status'], 'opp_featured_status_idx');
            $table->index(['organization_id', 'status'], 'opp_org_status_idx');
            $table->index(['category_id', 'status'], 'opp_cat_status_idx');
            $table->index(['type', 'status'], 'opp_type_status_idx');
            $table->index('slug', 'opp_slug_idx');
            $table->index('location', 'opp_location_idx');
            $table->index('experience_level', 'opp_exp_level_idx');
            
            // Fulltext index with custom name
            $table->fullText(['title', 'description', 'requirements', 'responsibilities'], 'opp_search_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};