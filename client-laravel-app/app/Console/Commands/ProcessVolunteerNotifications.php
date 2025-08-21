<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VolunteerNotificationService;
use Illuminate\Support\Facades\Log;

class ProcessVolunteerNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteer:process-notifications 
                            {--type= : Process specific notification type}
                            {--limit=100 : Maximum number of notifications to process}
                            {--dry-run : Show what would be processed without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Process and send scheduled volunteer notifications';

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
        $type = $this->option('type');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Starting volunteer notification processing...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No notifications will actually be sent');
        }

        try {
            if ($type) {
                $this->info("Processing notifications of type: {$type}");
            }

            $processed = $this->notificationService->processScheduledNotifications();

            $this->info("Successfully processed {$processed} notifications.");

            if ($processed > 0) {
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Notifications Processed', $processed],
                        ['Processing Time', now()->toTimeString()],
                        ['Mode', $dryRun ? 'Dry Run' : 'Live'],
                    ]
                );
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error processing notifications: " . $e->getMessage());
            Log::error('Volunteer notification processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}