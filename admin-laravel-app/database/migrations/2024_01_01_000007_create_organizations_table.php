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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('organization_category_id')->nullable();
            $table->unsignedBigInteger('institution_type_id')->nullable();
            $table->string('logo', 255)->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('facebook_url', 255)->nullable();
            $table->string('twitter_url', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('instagram_url', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->year('established_year')->nullable();
            $table->integer('employee_count')->nullable();
            $table->timestamps();
            
            // Indexes (matching your schema)
            $table->index('city_id', 'organizations_city_id_foreign');
            $table->index('country_id', 'organizations_country_id_foreign');
            $table->index('organization_category_id', 'organizations_category_id_foreign');
            $table->index('institution_type_id', 'organizations_institution_type_id_foreign');
            
            // Foreign key constraints
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            $table->foreign('organization_category_id')->references('id')->on('organization_categories')->onDelete('set null');
            $table->foreign('institution_type_id')->references('id')->on('institution_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};