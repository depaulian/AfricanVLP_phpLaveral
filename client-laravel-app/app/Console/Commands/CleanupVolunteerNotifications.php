<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VolunteerNotificationService;
use Illuminate\Support\Facades\Log;

class CleanupVolunteerNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteer:cleanup-notifications 
                            {--days=90 : Number of days to keep read notifications}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old volunteer notifications to free up database space';

    /**
     * Create a new command instance.
     */
    public function __construct(
        private VolunteerNotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Starting volunteer notification cleanup (keeping last {$days} days)...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No notifications will actually be deleted');
        }

        try {
            if ($dryRun) {
                // Count what would be deleted
                $cutoffDate = now()->subDays($days);
                $count = \App\Models\VolunteerNotification::where('created_at', '<', $cutoffDate)
                    ->where('is_read', true)
                    ->count();
                
                $this->info("Would delete {$count} old notifications.");
            } else {
                $deleted = $this->notificationService->cleanupOldNotifications($days);
                $this->info("Successfully deleted {$deleted} old notifications.");
            }

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Days Kept', $days],
                    ['Cutoff Date', now()->subDays($days)->toDateString()],
                    ['Processing Time', now()->toTimeString()],
                    ['Mode', $dryRun ? 'Dry Run' : 'Live'],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error cleaning up notifications: " . $e->getMessage());
            Log::error('Volunteer notification cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'days' => $days,
            ]);
            return Command::FAILURE;
        }
    }
}