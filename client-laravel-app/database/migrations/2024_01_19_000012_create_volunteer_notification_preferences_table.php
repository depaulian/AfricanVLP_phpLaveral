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
        Schema::create('volunteer_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type'); // 'opportunity_match', 'application_status', etc.
            $table->json('channels'); // ['email', 'database', 'sms', 'push']
            $table->boolean('is_enabled')->default(true);
            $table->json('settings')->nullable(); // Additional settings like frequency, time preferences
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_type']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_notification_preferences');
    }
};