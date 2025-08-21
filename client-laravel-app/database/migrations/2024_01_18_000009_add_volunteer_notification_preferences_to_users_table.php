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
            $table->boolean('volunteer_notifications_enabled')->default(true)->after('login_count');
            $table->boolean('trending_notifications_enabled')->default(true)->after('volunteer_notifications_enabled');
            $table->boolean('digest_notifications_enabled')->default(true)->after('trending_notifications_enabled');
            $table->boolean('immediate_notifications_enabled')->default(true)->after('digest_notifications_enabled');
            $table->boolean('email_notifications_enabled')->default(true)->after('immediate_notifications_enabled');
            $table->text('bio')->nullable()->after('email_notifications_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'volunteer_notifications_enabled',
                'trending_notifications_enabled',
                'digest_notifications_enabled',
                'immediate_notifications_enabled',
                'email_notifications_enabled',
                'bio'
            ]);
        });
    }
};