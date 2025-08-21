<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            
            // Indexes with custom short names
            $table->index('application_id', 'va_app_id_idx');
            $table->index('supervisor_id', 'va_supervisor_id_idx');
            $table->index('status', 'va_status_idx');
            $table->index(['start_date', 'end_date'], 'va_dates_idx');
        });

        // Add check constraint using raw SQL (works with MySQL 8.0.16+)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE volunteer_assignments ADD CONSTRAINT va_rating_check CHECK (rating IS NULL OR (rating >= 1 AND rating <= 5))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop check constraint before dropping table (if it exists)
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE volunteer_assignments DROP CONSTRAINT va_rating_check');
            } catch (Exception $e) {
                // Constraint might not exist, ignore error
            }
        }
        
        Schema::dropIfExists('volunteer_assignments');
    }
};