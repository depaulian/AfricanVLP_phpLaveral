<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupProfileAnalyticsReports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profile:cleanup-reports 
                            {--days= : Number of days to retain reports (overrides config)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old profile analytics reports based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Starting Profile Analytics Reports Cleanup...');

        try {
            $retentionDays = $this->option('days') ?: config('profile_analytics.storage.retention_days', 90);
            $dryRun = $this->option('dry-run');
            $force = $this->option('force');

            if (!config('profile_analytics.storage.cleanup_enabled', true)) {
                $this->warn('⚠️  Cleanup is disabled in configuration. Enable it to proceed.');
                return Command::SUCCESS;
            }

            $this->info("📅 Retention period: {$retentionDays} days");
            
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            $this->info("🗓️  Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");

            $disk = Storage::disk(config('profile_analytics.storage.disk', 'local'));
            $reportPath = config('profile_analytics.storage.path', 'reports/profile-analytics');

            if (!$disk->exists($reportPath)) {
                $this->info('📁 No reports directory found. Nothing to clean up.');
                return Command::SUCCESS;
            }

            $files = $disk->files($reportPath);
            $filesToDelete = [];
            $totalSize = 0;

            foreach ($files as $file) {
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));
                
                if ($lastModified->lt($cutoffDate)) {
                    $size = $disk->size($file);
                    $filesToDelete[] = [
                        'path' => $file,
                        'size' => $size,
                        'modified' => $lastModified,
                    ];
                    $totalSize += $size;
                }
            }

            if (empty($filesToDelete)) {
                $this->info('✅ No old reports found. Nothing to clean up.');
                return Command::SUCCESS;
            }

            $this->info("🔍 Found " . count($filesToDelete) . " files to delete");
            $this->info("💾 Total size: " . $this->formatBytes($totalSize));

            if ($dryRun) {
                $this->warn('🔍 DRY RUN MODE - No files will be deleted');
                $this->displayFilesToDelete($filesToDelete);
                return Command::SUCCESS;
            }

            if (!$force && !$this->confirm('Do you want to proceed with the cleanup?')) {
                $this->info('❌ Cleanup cancelled by user.');
                return Command::SUCCESS;
            }

            $deletedCount = 0;
            $deletedSize = 0;
            $errors = [];

            $progressBar = $this->output->createProgressBar(count($filesToDelete));
            $progressBar->start();

            foreach ($filesToDelete as $fileInfo) {
                try {
                    if ($disk->delete($fileInfo['path'])) {
                        $deletedCount++;
                        $deletedSize += $fileInfo['size'];
                    } else {
                        $errors[] = "Failed to delete: {$fileInfo['path']}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error deleting {$fileInfo['path']}: " . $e->getMessage();
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            $this->info("✅ Cleanup completed!");
            $this->info("🗑️  Deleted {$deletedCount} files");
            $this->info("💾 Freed up " . $this->formatBytes($deletedSize));

            if (!empty($errors)) {
                $this->warn("⚠️  Encountered " . count($errors) . " errors:");
                foreach ($errors as $error) {
                    $this->error("  • {$error}");
                }
            }

            // Log the cleanup activity
            Log::info('Profile Analytics Reports Cleanup Completed', [
                'retention_days' => $retentionDays,
                'files_deleted' => $deletedCount,
                'size_freed' => $deletedSize,
                'errors' => count($errors),
            ]);

            return empty($errors) ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error("❌ Error during cleanup: " . $e->getMessage());
            Log::error('Profile Analytics Reports Cleanup Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Display files that would be deleted.
     */
    protected function displayFilesToDelete(array $files): void
    {
        $this->table(
            ['File', 'Size', 'Last Modified'],
            array_map(function ($file) {
                return [
                    basename($file['path']),
                    $this->formatBytes($file['size']),
                    $file['modified']->format('Y-m-d H:i:s'),
                ];
            }, $files)
        );
    }

    /**
     * Format bytes into human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}