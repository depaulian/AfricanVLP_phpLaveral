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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3)->unique(); // ISO country code
            $table->string('iso_code', 2)->unique(); // ISO 2-letter code
            $table->foreignId('region_id')->nullable()->constrained()->onDelete('set null');
            $table->string('flag_url')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('phone_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['region_id', 'status']);
            $table->index(['status']);
            $table->index(['code']);
            $table->index(['iso_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};