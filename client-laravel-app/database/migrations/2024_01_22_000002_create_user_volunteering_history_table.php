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
        Schema::create('user_volunteering_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('set null');
            $table->string('organization_name')->nullable();
            $table->string('role_title')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('hours_contributed')->nullable();
            $table->json('skills_gained')->nullable();
            $table->string('reference_contact')->nullable();
            $table->string('reference_email')->nullable();
            $table->string('reference_phone', 50)->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('organization_id');
            $table->index(['start_date', 'end_date']);
            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_volunteering_history');
    }
};