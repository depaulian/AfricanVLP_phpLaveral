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
            $table->char('iso', 2)->nullable();
            $table->string('name', 80)->nullable();
            $table->string('nicename', 80)->nullable();
            $table->char('iso3', 3)->nullable();
            $table->smallInteger('numcode')->nullable();
            $table->integer('phonecode')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->datetime('created');
            $table->datetime('modified');
            
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
            $table->index(['iso', 'name']);
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