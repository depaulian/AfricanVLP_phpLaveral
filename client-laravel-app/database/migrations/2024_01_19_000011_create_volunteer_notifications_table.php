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
        Schema::create('volunteer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'opportunity_match', 'application_status', 'hour_approval', 'deadline_reminder', 'supervisor_notification', 'digest'
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data for the notification
            $table->string('channel')->default('database'); // 'database', 'email', 'sms', 'push'
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'sent', 'failed', 'cancelled'
            $table->text('failure_reason')->nullable();
            $table->timestamp('scheduled_for')->nullable(); // For scheduled notifications
            $table->integer('priority')->default(3); // 1=high, 2=medium, 3=low
            $table->string('related_type')->nullable(); // Model type (VolunteeringOpportunity, VolunteerApplication, etc.)
            $table->unsignedBigInteger('related_id')->nullable(); // Model ID
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_read']);
            $table->index(['status', 'scheduled_for']);
            $table->index(['related_type', 'related_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_notifications');
    }
};