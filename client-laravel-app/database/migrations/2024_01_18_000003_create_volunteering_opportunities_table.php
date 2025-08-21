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
        Schema::create('volunteering_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('volunteering_categories')->onDelete('restrict');
            $table->foreignId('role_id')->nullable()->constrained('volunteering_roles')->onDelete('set null');
            $table->enum('location_type', ['onsite', 'remote', 'hybrid'])->default('onsite');
            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->json('required_skills')->nullable();
            $table->string('time_commitment')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('application_deadline')->nullable();
            $table->integer('max_volunteers')->nullable();
            $table->integer('current_volunteers')->default(0);
            $table->enum('experience_level', ['beginner', 'intermediate', 'advanced', 'any'])->default('any');
            $table->string('age_requirement', 100)->nullable();
            $table->boolean('background_check_required')->default(false);
            $table->boolean('training_provided')->default(false);
            $table->text('benefits')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'closed', 'cancelled'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['organization_id']);
            $table->index(['category_id']);
            $table->index(['status']);
            $table->index(['city_id', 'country_id']);
            $table->index(['start_date', 'end_date']);
            $table->index(['featured']);
            $table->index(['application_deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteering_opportunities');
    }
};