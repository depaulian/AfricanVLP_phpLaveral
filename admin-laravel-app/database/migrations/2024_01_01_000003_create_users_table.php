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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 45)->nullable();
            $table->string('last_name', 45)->nullable();
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('profile_image', 255)->nullable();
            $table->string('profile_image_thumbnail', 255)->nullable();
            $table->string('profile_image_medium', 255)->nullable();
            $table->string('profile_image_large', 255)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token', 255)->nullable();
            $table->string('password_reset_token', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_count')->default(0);
            $table->string('fcm_token', 500)->nullable();
            $table->json('volunteer_notification_preferences')->nullable();
            $table->integer('registration_step')->default(1);
            $table->timestamp('registration_completed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            
            $table->index('city_id', 'users_city_id_foreign');
            $table->index('country_id', 'users_country_id_foreign');
            $table->index(['email', 'status']);
            $table->index(['first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};