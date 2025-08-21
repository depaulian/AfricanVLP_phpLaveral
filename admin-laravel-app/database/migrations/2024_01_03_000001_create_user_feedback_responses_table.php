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
        Schema::create('user_feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_feedback_id')->constrained('user_feedback')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_solution')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index(['user_feedback_id', 'created']);
            $table->index(['admin_id', 'created']);
            $table->index(['is_internal', 'is_solution']);
            $table->index('created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedback_responses');
    }
};
