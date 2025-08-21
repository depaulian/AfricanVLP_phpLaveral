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
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image_thumbnail')->nullable()->after('profile_image');
            $table->string('profile_image_medium')->nullable()->after('profile_image_thumbnail');
            
            // Add indexes for profile image fields
            $table->index('profile_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['profile_image']);
            $table->dropColumn([
                'profile_image_thumbnail',
                'profile_image_medium'
            ]);
        });
    }
};
