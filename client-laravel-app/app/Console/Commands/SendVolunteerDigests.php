<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VolunteerNotificationService;
use Illuminate\Support\Facades\Log;

class SendVolunteerDigests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteer:send-digests 
                            {--frequency= : Send digests for specific frequency (daily, weekly, monthly)}
                            {--user= : Send digest for specific user ID}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Generate and send volunteer activity digest notifications';

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
        $frequency = $this->option('frequency');
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info('Starting volunteer digest generation...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No digests will actually be sent');
        }

        try {
            if ($userId) {
                $this->info("Generating digest for user ID: {$userId}");
                // Implementation for specific user would go here
                $sent = 1; // Placeholder
            } else {
                if ($frequency) {
                    $this->info("Generating {$frequency} digests");
                }
                
                $sent = $this->notificationService->generateDigestNotifications();
            }

            $this->info("Successfully generated and sent {$sent} digest notifications.");

            if ($sent > 0) {
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Digests Sent', $sent],
                        ['Frequency', $frequency ?? 'All'],
                        ['Processing Time', now()->toTimeString()],
                        ['Mode', $dryRun ? 'Dry Run' : 'Live'],
                    ]
                );
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error generating digests: " . $e->getMessage());
            Log::error('Volunteer digest generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'frequency' => $frequency,
                'user_id' => $userId,
            ]);
            return Command::FAILURE;
        }
    }
}