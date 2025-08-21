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
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url');
            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->integer('position')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->boolean('show_overlay')->default(false);
            $table->enum('text_position', ['left', 'center', 'right'])->default('left');
            $table->enum('animation_type', ['fade', 'slide', 'zoom'])->default('fade');
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['page_id', 'status', 'position']);
            $table->index(['status', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};