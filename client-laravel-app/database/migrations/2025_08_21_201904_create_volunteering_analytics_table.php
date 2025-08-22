<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteering_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('period_type'); // daily, weekly, monthly, quarterly, yearly
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->string('metric_type'); // e.g., volunteer_count, hours_logged
            $table->string('metric_category')->nullable(); // performance, engagement, impact, etc.
            $table->double('value')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('calculated_at')->nullable()->index();
            $table->timestamps();

            $table->index(['organization_id', 'period_type', 'period_start']);
            $table->index(['metric_type', 'metric_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteering_analytics');
    }
};
