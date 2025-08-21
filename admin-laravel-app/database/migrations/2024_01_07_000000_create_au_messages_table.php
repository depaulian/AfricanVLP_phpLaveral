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
        Schema::create('au_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('body');
            $table->enum('type', ['system', 'notification', 'announcement', 'warning', 'alert', 'personal'])->default('personal');
            $table->enum('priority', ['urgent', 'high', 'normal', 'low'])->default('normal');
            $table->enum('status', ['draft', 'sent', 'delivered', 'read', 'replied', 'archived'])->default('sent');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('au_messages')->onDelete('cascade');
            $table->foreignId('thread_id')->nullable()->constrained('au_messages')->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Indexes
            $table->index(['recipient_id', 'is_read', 'created']);
            $table->index(['sender_id', 'created']);
            $table->index(['type', 'priority']);
            $table->index(['status', 'created']);
            $table->index(['organization_id', 'created']);
            $table->index(['parent_id', 'thread_id']);
            $table->index(['expires_at', 'is_read']);
            $table->index('created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('au_messages');
    }
};
