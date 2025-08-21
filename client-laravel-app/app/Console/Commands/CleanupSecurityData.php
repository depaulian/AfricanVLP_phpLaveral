<?php

namespace App\Console\Commands;

use App\Services\SecurityService;
use Illuminate\Console\Command;

class CleanupSecurityData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:cleanup 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old security events and expired sessions';

    public function __construct(
        private SecurityService $securityService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting security data cleanup...');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (!$force && !$dryRun) {
            if (!$this->confirm('This will permanently delete old security data. Continue?')) {
                $this->info('Cleanup cancelled.');
                return self::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be actually deleted');
        }

        try {
            if (!$dryRun) {
                $result = $this->securityService->cleanupOldData();
                
                $this->info("Cleanup completed successfully:");
                $this->line("- Deleted {$result['deleted_events']} old security events");
                $this->line("- Deleted {$result['deleted_sessions']} expired sessions");
            } else {
                // Show what would be deleted
                $this->showCleanupPreview();
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Show what would be deleted in dry-run mode.
     */
    private function showCleanupPreview(): void
    {
        // Count old security events
        $oldEventsCount = \App\Models\SecurityEvent::where('created_at', '<', now()->subMonths(6))
            ->where('risk_level', 'low')
            ->count();

        // Count expired sessions
        $expiredSessionsCount = \App\Models\UserSession::expired()
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $this->info("Would delete:");
        $this->line("- {$oldEventsCount} old low-risk security events (older than 6 months)");
        $this->line("- {$expiredSessionsCount} expired sessions (older than 30 days)");
    }
}