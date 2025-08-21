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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->json('events'); // Array of events this webhook subscribes to
            $table->string('secret')->nullable(); // Secret for signature verification
            $table->boolean('active')->default(true);
            $table->boolean('verify_ssl')->default(true);
            $table->integer('timeout')->default(30); // Timeout in seconds
            $table->integer('max_retries')->default(3);
            $table->json('metadata')->nullable(); // Additional webhook metadata
            
            // Statistics
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);
            $table->timestamp('last_successful_delivery')->nullable();
            $table->timestamp('last_failed_delivery')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['active']);
            $table->index(['events']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};