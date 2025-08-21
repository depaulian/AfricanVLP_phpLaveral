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
        Schema::table('user_volunteering_history', function (Blueprint $table) {
            // Reference contact enhancements
            $table->string('reference_position')->nullable()->after('reference_phone');
            $table->boolean('reference_verified')->default(false)->after('reference_position');
            $table->timestamp('reference_verified_at')->nullable()->after('reference_verified');
            
            // Impact tracking fields
            $table->text('impact_description')->nullable()->after('description');
            $table->json('impact_metrics')->nullable()->after('impact_description');
            $table->integer('people_helped')->nullable()->after('impact_metrics');
            $table->decimal('funds_raised', 10, 2)->nullable()->after('people_helped');
            $table->integer('events_organized')->nullable()->after('funds_raised');
            
            // Certificate and recognition tracking
            $table->json('certificates')->nullable()->after('events_organized');
            $table->json('recognitions')->nullable()->after('certificates');
            
            // Portfolio and visibility settings
            $table->boolean('portfolio_visible')->default(true)->after('recognitions');
            $table->json('verification_documents')->nullable()->after('portfolio_visible');
            
            // Add indexes for performance
            $table->index(['user_id', 'portfolio_visible']);
            $table->index(['reference_verified']);
            $table->index(['hours_contributed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_volunteering_history', function (Blueprint $table) {
            $table->dropColumn([
                'reference_position',
                'reference_verified',
                'reference_verified_at',
                'impact_description',
                'impact_metrics',
                'people_helped',
                'funds_raised',
                'events_organized',
                'certificates',
                'recognitions',
                'portfolio_visible',
                'verification_documents',
            ]);
        });
    }
};