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
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['html', 'text', 'stats', 'chart', 'list', 'feed', 'calendar', 'map', 'social', 'custom'])->default('text');
            $table->enum('position', ['header', 'sidebar', 'footer', 'content', 'dashboard'])->default('sidebar');
            $table->enum('page', ['home', 'dashboard', 'profile', 'organization', 'events', 'forums', '*'])->default('*');
            $table->json('content');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('visibility_rules')->nullable();
            $table->integer('cache_duration')->default(3600);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Indexes
            $table->index(['page', 'position', 'sort_order']);
            $table->index(['type', 'is_active']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['created_by', 'created']);
            $table->index(['is_active', 'is_system']);
            $table->index('created');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
