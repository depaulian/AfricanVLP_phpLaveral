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
        Schema::create('volunteering_oppurtunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('volunteering_role_id')->nullable();
            $table->unsignedBigInteger('volunteering_duration_id')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->string('location', 255)->nullable();
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->integer('max_volunteers')->nullable();
            $table->integer('current_volunteers')->default(0);
            $table->enum('status', ['active', 'inactive', 'completed', 'cancelled'])->default('active');
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->datetime('created');
            $table->datetime('modified');
            
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('volunteering_role_id')->references('id')->on('volunteering_roles')->onDelete('set null');
            $table->foreign('volunteering_duration_id')->references('id')->on('volunteering_durations')->onDelete('set null');
            
            $table->index(['status', 'start_date']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteering_oppurtunities');
    }
};