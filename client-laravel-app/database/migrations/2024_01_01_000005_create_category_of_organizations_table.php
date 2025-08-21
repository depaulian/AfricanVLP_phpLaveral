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
        Schema::create('category_of_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45)->nullable();
            $table->text('description')->nullable();
            $table->datetime('created');
            $table->datetime('modified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_of_organizations');
    }
};