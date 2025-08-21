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
            $table->enum('admin_role', ['super_admin', 'admin', 'moderator', 'editor', 'viewer'])
                  ->nullable()
                  ->after('is_admin')
                  ->comment('Admin role for role-based access control');
            
            $table->json('admin_permissions')
                  ->nullable()
                  ->after('admin_role')
                  ->comment('JSON array of admin permissions');
            
            $table->unsignedBigInteger('created_by')
                  ->nullable()
                  ->after('admin_permissions')
                  ->comment('ID of admin who created this user');
            
            $table->index(['is_admin', 'admin_role'], 'users_admin_role_index');
            $table->index('created_by', 'users_created_by_index');
            
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex('users_admin_role_index');
            $table->dropIndex('users_created_by_index');
            $table->dropColumn(['admin_role', 'admin_permissions', 'created_by']);
        });
    }
};
