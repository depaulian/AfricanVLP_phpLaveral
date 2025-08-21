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
        Schema::create('organization_registration_steps', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('step_name');
            $table->json('step_data')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index(['session_id', 'step_name']);
            $table->index(['session_id', 'is_completed']);
            $table->index(['organization_id', 'step_name']);
            $table->index(['user_id', 'step_name']);
            $table->index('completed_at');

            // Unique constraint to prevent duplicate steps per session
            $table->unique(['session_id', 'step_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_registration_steps');
    }
};
