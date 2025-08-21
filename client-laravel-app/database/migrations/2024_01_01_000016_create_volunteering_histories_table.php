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
        Schema::create('volunteering_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('volunteering_oppurtunity_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->enum('status', ['applied', 'accepted', 'active', 'completed', 'cancelled', 'rejected'])->default('applied');
            $table->datetime('applied_date')->nullable();
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->decimal('hours_completed', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->integer('rating')->nullable(); // 1-5 rating
            $table->text('notes')->nullable();
            $table->boolean('certificate_issued')->default(false);
            $table->datetime('created');
            $table->datetime('modified');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('volunteering_oppurtunity_id')->references('id')->on('volunteering_oppurtunities')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            
            $table->index(['user_id', 'status']);
            $table->index(['volunteering_oppurtunity_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteering_histories');
    }
};