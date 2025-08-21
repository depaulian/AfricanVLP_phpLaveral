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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('country_id')->nullable()->constrained()->onDelete('set null');
            $table->string('linkedin_url', 500)->nullable();
            $table->string('twitter_url', 500)->nullable();
            $table->string('facebook_url', 500)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('profile_image_url', 500)->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->integer('profile_completion_percentage')->default(0);
            $table->boolean('is_public')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('city_id');
            $table->index('country_id');
            $table->index('is_public');
            $table->index('profile_completion_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};