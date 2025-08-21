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
        Schema::create('volunteer_application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('volunteer_applications')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'withdrawn']);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_application_status_histories');
    }
};