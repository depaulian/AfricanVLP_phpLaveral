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
        Schema::create('forum_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('forum_id')->nullable()->constrained('forums')->onDelete('cascade');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'expires_at']);
            $table->index(['forum_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_warnings');
    }
};