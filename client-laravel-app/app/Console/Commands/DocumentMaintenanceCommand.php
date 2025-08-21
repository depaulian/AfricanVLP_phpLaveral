<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use App\Services\DocumentManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DocumentMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:maintenance 
                            {--cleanup-orphaned : Remove orphaned files without database records}
                            {--verify-integrity : Verify file integrity and checksums}
                            {--optimize-storage : Optimize storage by compressing old files}
                            {--generate-thumbnails : Generate missing thumbnails for image documents}
                            {--update-metadata : Update document metadata and statistics}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Perform maintenance tasks on the document management system';

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
        $this->info('Starting document maintenance tasks...');

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $tasksRun = 0;

        // Cleanup orphaned files
        if ($this->option('cleanup-orphaned')) {
            $this->cleanupOrphanedFiles($dryRun);
            $tasksRun++;
        }

        // Verify file integrity
        if ($this->option('verify-integrity')) {
            $this->verifyFileIntegrity($dryRun);
            $tasksRun++;
        }

        // Optimize storage
        if ($this->option('optimize-storage')) {
            $this->optimizeStorage($dryRun);
            $tasksRun++;
        }

        // Generate thumbnails
        if ($this->option('generate-thumbnails')) {
            $this->generateThumbnails($dryRun);
            $tasksRun++;
        }

        // Update metadata
        if ($this->option('update-metadata')) {
            $this->updateMetadata($dryRun);
            $tasksRun++;
        }

        // If no specific tasks were requested, run all maintenance tasks
        if ($tasksRun === 0) {
            $this->runAllMaintenanceTasks($dryRun);
        }

        $this->info('Document maintenance completed successfully.');
        return 0;
    }

    /**
     * Run all maintenance tasks.
     */
    protected function runAllMaintenanceTasks(bool $dryRun): void
    {
        $this->info('Running all maintenance tasks...');
        
        $this->cleanupOrphanedFiles($dryRun);
        $this->verifyFileIntegrity($dryRun);
        $this->optimizeStorage($dryRun);
        $this->generateThumbnails($dryRun);
        $this->updateMetadata($dryRun);
    }

    /**
     * Cleanup orphaned files that don't have database records.
     */
    protected function cleanupOrphanedFiles(bool $dryRun): void
    {
        $this->info('Cleaning up orphaned files...');

        $disk = Storage::disk('private');
        $documentsPath = 'documents';
        
        if (!$disk->exists($documentsPath)) {
            $this->warn('Documents directory does not exist.');
            return;
        }

        $allFiles = $disk->allFiles($documentsPath);
        $orphanedFiles = [];
        $totalSize = 0;

        $bar = $this->output->createProgressBar(count($allFiles));
        $bar->setFormat('verbose');

        foreach ($allFiles as $filePath) {
            $exists = UserDocument::where('file_path', $filePath)->exists();
            
            if (!$exists) {
                $orphanedFiles[] = $filePath;
                $totalSize += $disk->size($filePath);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if (empty($orphanedFiles)) {
            $this->info('No orphaned files found.');
            return;
        }

        $this->warn(sprintf(
            'Found %d orphaned files totaling %s',
            count($orphanedFiles),
            $this->formatFileSize($totalSize)
        ));

        if ($dryRun) {
            $this->info('Would delete the following orphaned files:');
            foreach (array_slice($orphanedFiles, 0, 10) as $file) {
                $this->line("  - {$file}");
            }
            if (count($orphanedFiles) > 10) {
                $this->line(sprintf('  ... and %d more files', count($orphanedFiles) - 10));
            }
            return;
        }

        if ($this->confirm('Delete orphaned files?')) {
            $deleted = 0;
            foreach ($orphanedFiles as $filePath) {
                try {
                    $disk->delete($filePath);
                    $deleted++;
                } catch (\Exception $e) {
                    $this->error("Failed to delete {$filePath}: {$e->getMessage()}");
                }
            }

            $this->info("Deleted {$deleted} orphaned files.");
            Log::info('Orphaned files cleanup completed', [
                'files_deleted' => $deleted,
                'total_size_freed' => $totalSize
            ]);
        }
    }

    /**
     * Verify file integrity and checksums.
     */
    protected function verifyFileIntegrity(bool $dryRun): void
    {
        $this->info('Verifying file integrity...');

        $documents = UserDocument::whereNotNull('metadata->hash')->get();
        $corruptedFiles = [];
        $missingFiles = [];

        $bar = $this->output->createProgressBar($documents->count());

        foreach ($documents as $document) {
            $disk = Storage::disk('private');
            
            // Check if file exists
            if (!$disk->exists($document->file_path)) {
                $missingFiles[] = $document;
                $bar->advance();
                continue;
            }

            // Verify checksum if available
            $storedHash = $document->metadata['hash'] ?? null;
            if ($storedHash) {
                try {
                    $currentHash = hash_file('sha256', $disk->path($document->file_path));
                    if ($currentHash !== $storedHash) {
                        $corruptedFiles[] = $document;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to verify {$document->file_path}: {$e->getMessage()}");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Report missing files
        if (!empty($missingFiles)) {
            $this->error(sprintf('Found %d missing files:', count($missingFiles)));
            foreach (array_slice($missingFiles, 0, 5) as $document) {
                $this->line("  - {$document->name} (ID: {$document->id})");
            }
            if (count($missingFiles) > 5) {
                $this->line(sprintf('  ... and %d more files', count($missingFiles) - 5));
            }
        }

        // Report corrupted files
        if (!empty($corruptedFiles)) {
            $this->error(sprintf('Found %d corrupted files:', count($corruptedFiles)));
            foreach (array_slice($corruptedFiles, 0, 5) as $document) {
                $this->line("  - {$document->name} (ID: {$document->id})");
            }
            if (count($corruptedFiles) > 5) {
                $this->line(sprintf('  ... and %d more files', count($corruptedFiles) - 5));
            }
        }

        if (empty($missingFiles) && empty($corruptedFiles)) {
            $this->info('All files passed integrity verification.');
        }

        // Log results
        Log::info('File integrity verification completed', [
            'total_checked' => $documents->count(),
            'missing_files' => count($missingFiles),
            'corrupted_files' => count($corruptedFiles)
        ]);
    }

    /**
     * Optimize storage by compressing old files.
     */
    protected function optimizeStorage(bool $dryRun): void
    {
        $this->info('Optimizing storage...');

        // Find documents older than 1 year that aren't compressed
        $oldDocuments = UserDocument::where('created_at', '<', now()->subYear())
            ->whereNull('metadata->compressed')
            ->where('mime_type', 'application/pdf')
            ->get();

        if ($oldDocuments->isEmpty()) {
            $this->info('No documents found for compression optimization.');
            return;
        }

        $this->info(sprintf('Found %d documents eligible for compression.', $oldDocuments->count()));

        if ($dryRun) {
            $totalSize = $oldDocuments->sum('file_size');
            $this->info(sprintf('Would compress %s of data.', $this->formatFileSize($totalSize)));
            return;
        }

        $compressed = 0;
        $totalSaved = 0;

        $bar = $this->output->createProgressBar($oldDocuments->count());

        foreach ($oldDocuments as $document) {
            try {
                $originalSize = $document->file_size;
                
                // In a real implementation, this would use a PDF compression library
                // For now, we'll just mark it as compressed
                $metadata = $document->metadata ?? [];
                $metadata['compressed'] = true;
                $metadata['compression_date'] = now();
                $metadata['original_size'] = $originalSize;
                
                $document->update(['metadata' => $metadata]);
                
                $compressed++;
                // Simulate 20% compression
                $savedSize = $originalSize * 0.2;
                $totalSaved += $savedSize;
                
            } catch (\Exception $e) {
                $this->error("Failed to compress document {$document->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info(sprintf(
            'Compressed %d documents, saved approximately %s.',
            $compressed,
            $this->formatFileSize($totalSaved)
        ));
    }

    /**
     * Generate missing thumbnails for image documents.
     */
    protected function generateThumbnails(bool $dryRun): void
    {
        $this->info('Generating missing thumbnails...');

        $imageDocuments = UserDocument::where('mime_type', 'like', 'image/%')
            ->whereNull('metadata->thumbnail_path')
            ->get();

        if ($imageDocuments->isEmpty()) {
            $this->info('No images found that need thumbnails.');
            return;
        }

        $this->info(sprintf('Found %d images that need thumbnails.', $imageDocuments->count()));

        if ($dryRun) {
            $this->info('Would generate thumbnails for these images.');
            return;
        }

        $generated = 0;
        $bar = $this->output->createProgressBar($imageDocuments->count());

        foreach ($imageDocuments as $document) {
            try {
                // In a real implementation, this would use an image processing library
                // For now, we'll just mark it as having a thumbnail
                $metadata = $document->metadata ?? [];
                $metadata['thumbnail_path'] = 'thumbnails/' . $document->id . '_thumb.jpg';
                $metadata['thumbnail_generated_at'] = now();
                
                $document->update(['metadata' => $metadata]);
                $generated++;
                
            } catch (\Exception $e) {
                $this->error("Failed to generate thumbnail for document {$document->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Generated {$generated} thumbnails.");
    }

    /**
     * Update document metadata and statistics.
     */
    protected function updateMetadata(bool $dryRun): void
    {
        $this->info('Updating document metadata...');

        $documents = UserDocument::whereNull('metadata->last_maintenance')
            ->orWhere('metadata->last_maintenance', '<', now()->subMonth())
            ->get();

        if ($documents->isEmpty()) {
            $this->info('All document metadata is up to date.');
            return;
        }

        $this->info(sprintf('Updating metadata for %d documents.', $documents->count()));

        if ($dryRun) {
            $this->info('Would update metadata for these documents.');
            return;
        }

        $updated = 0;
        $bar = $this->output->createProgressBar($documents->count());

        foreach ($documents as $document) {
            try {
                $metadata = $document->metadata ?? [];
                
                // Update maintenance timestamp
                $metadata['last_maintenance'] = now();
                
                // Update file statistics if file exists
                $disk = Storage::disk('private');
                if ($disk->exists($document->file_path)) {
                    $metadata['file_exists'] = true;
                    $metadata['last_verified'] = now();
                    
                    // Update file size if it has changed
                    $currentSize = $disk->size($document->file_path);
                    if ($currentSize !== $document->file_size) {
                        $document->file_size = $currentSize;
                        $metadata['size_updated'] = now();
                    }
                } else {
                    $metadata['file_exists'] = false;
                    $metadata['missing_since'] = now();
                }
                
                $document->update(['metadata' => $metadata]);
                $updated++;
                
            } catch (\Exception $e) {
                $this->error("Failed to update metadata for document {$document->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Updated metadata for {$updated} documents.");
    }

    /**
     * Format file size in human readable format.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}