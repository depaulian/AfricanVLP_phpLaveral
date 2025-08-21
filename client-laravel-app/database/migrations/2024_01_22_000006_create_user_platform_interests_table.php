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
        Schema::create('user_platform_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('interest_type', ['events', 'news', 'resources', 'forums', 'networking']);
            $table->enum('interest_level', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('notification_enabled')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('interest_type');
            $table->index('interest_level');
            $table->index('notification_enabled');
            
            // Unique constraint to prevent duplicate interest types per user
            $table->unique(['user_id', 'interest_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_platform_interests');
    }
};