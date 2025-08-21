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
        Schema::create('volunteering_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('volunteering_categories')->onDelete('set null');
            $table->json('typical_responsibilities')->nullable();
            $table->json('required_skills')->nullable();
            $table->enum('experience_level', ['beginner', 'intermediate', 'advanced', 'expert', 'any'])->default('any');
            $table->integer('estimated_time_commitment')->nullable(); // hours per week
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'active']);
            $table->index('experience_level');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteering_roles');
    }
};