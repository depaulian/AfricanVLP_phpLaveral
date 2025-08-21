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
        Schema::create('forums', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('is_private')->default(false);
            $table->json('moderator_ids')->nullable();
            $table->integer('post_count')->default(0);
            $table->integer('thread_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            
            $table->index(['organization_id']);
            $table->index(['status']);
            $table->index(['category']);
            $table->index(['is_private']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forums');
    }
};