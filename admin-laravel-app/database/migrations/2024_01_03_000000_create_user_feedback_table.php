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
        Schema::create('user_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['bug_report', 'feature_request', 'improvement', 'complaint', 'compliment', 'question', 'general'])->default('general');
            $table->enum('category', ['ui_ux', 'performance', 'functionality', 'content', 'accessibility', 'security', 'other'])->default('other');
            $table->string('title');
            $table->text('message');
            $table->tinyInteger('rating')->nullable()->comment('1-5 star rating');
            $table->enum('status', ['pending', 'in_review', 'responded', 'implemented', 'closed'])->default('pending');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->string('page_url')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'created']);
            $table->index(['admin_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['category', 'priority']);
            $table->index(['status', 'priority']);
            $table->index(['is_public', 'is_featured']);
            $table->index('created');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedback');
    }
};
