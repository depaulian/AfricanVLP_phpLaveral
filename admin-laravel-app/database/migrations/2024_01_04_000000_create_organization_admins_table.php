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
        Schema::create('organization_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['super_admin', 'admin', 'moderator', 'editor', 'viewer'])->default('viewer');
            $table->json('permissions')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Indexes
            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'role']);
            $table->index(['user_id', 'is_active']);
            $table->index(['assigned_by', 'assigned_at']);
            $table->index(['role', 'is_active']);
            $table->index(['expires_at', 'is_active']);
            $table->index('created');
            
            // Unique constraint to prevent duplicate active admins
            $table->unique(['organization_id', 'user_id', 'is_active'], 'unique_active_org_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_admins');
    }
};
