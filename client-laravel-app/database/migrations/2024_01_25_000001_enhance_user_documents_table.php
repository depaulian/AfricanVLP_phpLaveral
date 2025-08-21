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
        Schema::table('user_documents', function (Blueprint $table) {
            // Add missing fields for enhanced document management
            $table->string('name')->nullable()->after('user_id');
            $table->string('category')->default('other')->after('document_type');
            $table->text('description')->nullable()->after('category');
            $table->date('expiry_date')->nullable()->after('rejection_reason');
            $table->boolean('is_sensitive')->default(false)->after('expiry_date');
            $table->boolean('is_archived')->default(false)->after('is_sensitive');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->string('archive_reason')->nullable()->after('archived_at');
            $table->json('metadata')->nullable()->after('archive_reason');
            $table->text('verification_notes')->nullable()->after('metadata');
            $table->timestamp('verification_requested_at')->nullable()->after('verification_notes');
            $table->string('upload_ip')->nullable()->after('verification_requested_at');
            $table->integer('download_count')->default(0)->after('upload_ip');
            $table->timestamp('last_downloaded_at')->nullable()->after('download_count');
            $table->integer('share_count')->default(0)->after('last_downloaded_at');
            $table->timestamp('last_shared_at')->nullable()->after('share_count');
            
            // Add indexes for better performance
            $table->index(['category']);
            $table->index(['expiry_date']);
            $table->index(['is_archived']);
            $table->index(['verification_requested_at']);
            $table->index(['created_at', 'verification_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['expiry_date']);
            $table->dropIndex(['is_archived']);
            $table->dropIndex(['verification_requested_at']);
            $table->dropIndex(['created_at', 'verification_status']);
            
            $table->dropColumn([
                'name',
                'category',
                'description',
                'expiry_date',
                'is_sensitive',
                'is_archived',
                'archived_at',
                'archive_reason',
                'metadata',
                'verification_notes',
                'verification_requested_at',
                'upload_ip',
                'download_count',
                'last_downloaded_at',
                'share_count',
                'last_shared_at'
            ]);
        });
    }
};