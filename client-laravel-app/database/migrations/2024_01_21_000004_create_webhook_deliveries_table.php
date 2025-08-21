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
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('event'); // The event that triggered this delivery
            $table->json('payload'); // The payload sent to the webhook
            $table->json('context')->nullable(); // Additional context data
            
            // Delivery status and tracking
            $table->enum('status', ['pending', 'delivered', 'failed', 'exhausted'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            
            // Timestamps
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['webhook_id', 'status']);
            $table->index(['event']);
            $table->index(['status', 'created_at']);
            $table->index(['attempts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};