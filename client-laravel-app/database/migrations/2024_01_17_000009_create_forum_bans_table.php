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
        Schema::create('forum_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('forum_id')->nullable()->constrained('forums')->onDelete('cascade');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('lifted_at')->nullable();
            $table->foreignId('lifted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('lift_reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['forum_id', 'created_at']);
            $table->index(['ip_address', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_bans');
    }
};