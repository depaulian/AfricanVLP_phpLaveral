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
        Schema::create('volunteer_application_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('volunteer_applications')->onDelete('cascade');
            $table->text('content');
            $table->boolean('from_admin')->default(false);
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_application_messages');
    }
};