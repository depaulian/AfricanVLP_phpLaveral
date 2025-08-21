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
        Schema::create('volunteer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('volunteer_applications')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('hours_committed')->nullable();
            $table->integer('hours_completed')->default(0);
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['active', 'completed', 'terminated', 'on_hold'])->default('active');
            $table->text('completion_notes')->nullable();
            $table->tinyInteger('rating')->nullable()->comment('1-5 rating');
            $table->text('feedback')->nullable();
            $table->boolean('certificate_issued')->default(false);
            $table->timestamps();
            
            $table->index(['application_id']);
            $table->index(['supervisor_id']);
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
            
            // Add check constraint for rating
            $table->check('rating >= 1 AND rating <= 5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_assignments');
    }
};