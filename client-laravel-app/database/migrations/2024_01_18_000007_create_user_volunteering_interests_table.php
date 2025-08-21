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
        Schema::create('user_volunteering_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('volunteering_categories')->onDelete('cascade');
            $table->enum('interest_level', ['low', 'medium', 'high'])->default('medium');
            $table->text('notes')->nullable();
            $table->boolean('willing_to_travel')->default(false);
            $table->integer('max_travel_distance')->nullable(); // in kilometers
            $table->json('preferred_time_commitment')->nullable(); // hours per week, duration preferences
            $table->json('availability')->nullable(); // days of week, time slots
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);
            $table->index(['category_id', 'interest_level']);
            $table->index('willing_to_travel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_volunteering_interests');
    }
};