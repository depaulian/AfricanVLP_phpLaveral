<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use App\Services\DocumentManagementService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessDocumentExpirations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:process-expirations 
                            {--days=30 : Number of days to look ahead for expiring documents}
                            {--send-reminders : Send expiration reminder notifications}
                            {--cleanup-expired : Archive expired documents}';

    /**
     * The console command description.
     */
    protected $description = 'Process document expirations, send reminders, and cleanup expired documents';

    protected DocumentManagementService $documentService;

    public function __construct(DocumentManagementService $documentService)
    {
        parent::__construct();
        $this->documentService = $documentService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing document expirations...');

        $days = (int) $this->option('days');
        $sendReminders = $this->option('send-reminders');
        $cleanupExpired = $this->option('cleanup-expired');

        // Get expiring documents
        $expiringDocuments = $this->documentService->getExpiringDocuments($days);
        $expiredDocuments = $this->documentService->getExpiredDocuments();

        $this->info("Found {$expiringDocuments->count()} documents expiring within {$days} days");
        $this->info("Found {$expiredDocuments->count()} expired documents");

        // Send expiration reminders
        if ($sendReminders) {
            $this->sendExpirationReminders($expiringDocuments);
        }

        // Cleanup expired documents
        if ($cleanupExpired) {
            $this->cleanupExpiredDocuments($expiredDocuments);
        }

        // Generate summary report
        $this->generateSummaryReport($expiringDocuments, $expiredDocuments);

        $this->info('Document expiration processing completed successfully.');
        return 0;
    }

    /**
     * Send expiration reminder notifications.
     */
    protected function sendExpirationReminders($expiringDocuments): void
    {
        $this->info('Sending expiration reminder notifications...');

        $remindersSent = 0;
        $bar = $this->output->createProgressBar($expiringDocuments->count());

        foreach ($expiringDocuments as $document) {
            try {
                $daysUntilExpiry = $document->expiry_date->diffInDays(now());
                
                // Send reminders at specific intervals (30, 14, 7, 1 days)
                $reminderDays = [30, 14, 7, 1];
                
                if (in_array($daysUntilExpiry, $reminderDays)) {
                    $document->user->notify(
                        new \App\Notifications\DocumentExpirationReminder($document, $daysUntilExpiry)
                    );
                    $remindersSent++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for document {$document->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Sent {$remindersSent} expiration reminder notifications.");
    }

    /**
     * Cleanup expired documents.
     */
    protected function cleanupExpiredDocuments($expiredDocuments): void
    {
        $this->info('Processing expired documents...');

        $archivedCount = 0;
        $deletedCount = 0;
        $bar = $this->output->createProgressBar($expiredDocuments->count());

        foreach ($expiredDocuments as $document) {
            try {
                $daysSinceExpiry = now()->diffInDays($document->expiry_date);
                
                // Archive documents expired for less than 1 year
                if ($daysSinceExpiry <= 365) {
                    $this->archiveDocument($document);
                    $archivedCount++;
                } 
                // Delete documents expired for more than 7 years (legal retention)
                elseif ($daysSinceExpiry > (7 * 365)) {
                    if ($this->confirm("Delete document '{$document->name}' expired {$daysSinceExpiry} days ago?")) {
                        $this->deleteDocument($document);
                        $deletedCount++;
                    }
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to process expired document {$document->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Archived {$archivedCount} expired documents.");
        $this->info("Deleted {$deletedCount} old expired documents.");
    }

    /**
     * Archive an expired document.
     */
    protected function archiveDocument(UserDocument $document): void
    {
        // Create backup before archiving
        $this->documentService->createDocumentBackup($document);

        // Update document status
        $document->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archive_reason' => 'Expired document auto-archived'
        ]);

        // Log archival
        \Log::info('Document archived due to expiration', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'expiry_date' => $document->expiry_date,
            'archived_at' => now()
        ]);
    }

    /**
     * Delete an old expired document.
     */
    protected function deleteDocument(UserDocument $document): void
    {
        try {
            // Delete file from storage
            if (\Storage::disk('private')->exists($document->file_path)) {
                \Storage::disk('private')->delete($document->file_path);
            }

            // Log deletion before removing record
            \Log::info('Expired document deleted', [
                'document_id' => $document->id,
                'user_id' => $document->user_id,
                'name' => $document->name,
                'expiry_date' => $document->expiry_date,
                'deleted_at' => now()
            ]);

            // Delete database record
            $document->delete();

        } catch (\Exception $e) {
            $this->error("Failed to delete document {$document->id}: {$e->getMessage()}");
        }
    }

    /**
     * Generate summary report.
     */
    protected function generateSummaryReport($expiringDocuments, $expiredDocuments): void
    {
        $this->newLine();
        $this->info('=== Document Expiration Summary Report ===');
        
        // Expiring documents by category
        $expiringByCategory = $expiringDocuments->groupBy('category');
        $this->info('Expiring Documents by Category:');
        foreach ($expiringByCategory as $category => $documents) {
            $this->line("  {$category}: {$documents->count()} documents");
        }

        // Expired documents by category
        $expiredByCategory = $expiredDocuments->groupBy('category');
        $this->info('Expired Documents by Category:');
        foreach ($expiredByCategory as $category => $documents) {
            $this->line("  {$category}: {$documents->count()} documents");
        }

        // Critical expirations (within 7 days)
        $criticalExpirations = $expiringDocuments->filter(function ($document) {
            return $document->expiry_date->diffInDays(now()) <= 7;
        });

        if ($criticalExpirations->count() > 0) {
            $this->warn("CRITICAL: {$criticalExpirations->count()} documents expire within 7 days!");
            foreach ($criticalExpirations as $document) {
                $this->line("  - {$document->name} (User: {$document->user->name}) expires {$document->expiry_date->format('M d, Y')}");
            }
        }

        // Users with most expiring documents
        $userExpirations = $expiringDocuments->groupBy('user_id')
            ->map(function ($documents) {
                return [
                    'user' => $documents->first()->user,
                    'count' => $documents->count()
                ];
            })
            ->sortByDesc('count')
            ->take(5);

        if ($userExpirations->count() > 0) {
            $this->info('Top Users with Expiring Documents:');
            foreach ($userExpirations as $userData) {
                $this->line("  {$userData['user']->name}: {$userData['count']} documents");
            }
        }

        $this->info('=== End Report ===');
    }
}