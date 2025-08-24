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
        Schema::create('user_volunteering_organization_category_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->name('uvoci_user_foreign');
            $table->foreignId('organization_category_id')->constrained('organization_categories')->onDelete('cascade')->name('uvoci_org_cat_foreign');
            $table->timestamps();
            
            $table->unique(['user_id', 'organization_category_id'], 'uvoci_user_org_cat_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Fixed: Drop the correct table name
        Schema::dropIfExists('user_volunteering_organization_category_interests');
    }
};