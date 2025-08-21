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
        // Volunteering opportunities indexes
        Schema::table('volunteering_opportunities', function (Blueprint $table) {
            // Search and filtering indexes
            $table->index(['status', 'application_deadline'], 'idx_status_deadline');
            $table->index(['category_id', 'status'], 'idx_category_status');
            $table->index(['city_id', 'status'], 'idx_city_status');
            $table->index(['organization_id', 'status'], 'idx_org_status');
            $table->index(['featured', 'status'], 'idx_featured_status');
            $table->index(['urgent', 'status'], 'idx_urgent_status');
            
            // Date-based indexes
            $table->index(['start_date', 'end_date'], 'idx_date_range');
            $table->index(['created_at', 'status'], 'idx_created_status');
            
            // Full-text search index
            $table->fullText(['title', 'description'], 'idx_fulltext_search');
            
            // Composite indexes for common queries
            $table->index(['status', 'application_deadline', 'featured'], 'idx_active_featured');
            $table->index(['category_id', 'city_id', 'status'], 'idx_category_city_status');
        });

        // Volunteer applications indexes
        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['opportunity_id', 'status'], 'idx_opportunity_status');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['user_id', 'opportunity_id'], 'idx_user_opportunity');
        });

        // Volunteer assignments indexes
        Schema::table('volunteer_assignments', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['opportunity_id', 'status'], 'idx_opportunity_status');
            $table->index(['supervisor_id', 'status'], 'idx_supervisor_status');
            $table->index(['start_date', 'end_date'], 'idx_assignment_dates');
        });

        // Volunteer time logs indexes
        Schema::table('volunteer_time_logs', function (Blueprint $table) {
            $table->index(['assignment_id', 'status'], 'idx_assignment_status');
            $table->index(['user_id', 'log_date'], 'idx_user_date');
            $table->index(['supervisor_id', 'status'], 'idx_supervisor_status');
            $table->index(['log_date', 'status'], 'idx_date_status');
        });

        // User volunteering interests indexes
        Schema::table('user_volunteering_interests', function (Blueprint $table) {
            $table->index(['user_id', 'category_id'], 'idx_user_category');
            $table->index('category_id', 'idx_category');
        });

        // User skills indexes
        Schema::table('user_skills', function (Blueprint $table) {
            $table->index(['user_id', 'skill_name'], 'idx_user_skill');
            $table->index('skill_name', 'idx_skill_name');
            $table->index(['skill_name', 'proficiency_level'], 'idx_skill_proficiency');
        });

        // Volunteering categories indexes
        Schema::table('volunteering_categories', function (Blueprint $table) {
            $table->index(['parent_id', 'active'], 'idx_parent_active');
            $table->index('slug', 'idx_slug');
        });

        // Volunteering roles indexes
        Schema::table('volunteering_roles', function (Blueprint $table) {
            $table->index(['category_id', 'active'], 'idx_category_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('volunteering_opportunities', function (Blueprint $table) {
            $table->dropIndex('idx_status_deadline');
            $table->dropIndex('idx_category_status');
            $table->dropIndex('idx_city_status');
            $table->dropIndex('idx_org_status');
            $table->dropIndex('idx_featured_status');
            $table->dropIndex('idx_urgent_status');
            $table->dropIndex('idx_date_range');
            $table->dropIndex('idx_created_status');
            $table->dropIndex('idx_fulltext_search');
            $table->dropIndex('idx_active_featured');
            $table->dropIndex('idx_category_city_status');
        });

        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->dropIndex('idx_user_status');
            $table->dropIndex('idx_opportunity_status');
            $table->dropIndex('idx_status_created');
            $table->dropIndex('idx_user_opportunity');
        });

        Schema::table('volunteer_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_user_status');
            $table->dropIndex('idx_opportunity_status');
            $table->dropIndex('idx_supervisor_status');
            $table->dropIndex('idx_assignment_dates');
        });

        Schema::table('volunteer_time_logs', function (Blueprint $table) {
            $table->dropIndex('idx_assignment_status');
            $table->dropIndex('idx_user_date');
            $table->dropIndex('idx_supervisor_status');
            $table->dropIndex('idx_date_status');
        });

        Schema::table('user_volunteering_interests', function (Blueprint $table) {
            $table->dropIndex('idx_user_category');
            $table->dropIndex('idx_category');
        });

        Schema::table('user_skills', function (Blueprint $table) {
            $table->dropIndex('idx_user_skill');
            $table->dropIndex('idx_skill_name');
            $table->dropIndex('idx_skill_proficiency');
        });

        Schema::table('volunteering_categories', function (Blueprint $table) {
            $table->dropIndex('idx_parent_active');
            $table->dropIndex('idx_slug');
        });

        Schema::table('volunteering_roles', function (Blueprint $table) {
            $table->dropIndex('idx_category_active');
        });
    }
};