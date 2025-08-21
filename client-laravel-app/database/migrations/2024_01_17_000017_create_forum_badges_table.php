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
        Schema::create('forum_badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Hex color
            $table->enum('type', ['activity', 'achievement', 'milestone', 'special']);
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary']);
            $table->integer('points_value')->default(0);
            $table->json('criteria'); // JSON criteria for earning the badge
            $table->boolean('is_active')->default(true);
            $table->integer('awarded_count')->default(0);
            $table->timestamps();

            $table->index(['type']);
            $table->index(['rarity']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_badges');
    }
};