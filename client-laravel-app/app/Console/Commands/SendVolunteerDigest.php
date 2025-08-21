<?php

namespace App\Console\Commands;

use App\Services\VolunteerNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendVolunteerDigest extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteer:send-digest 
                            {--type=weekly : Type of digest to send (weekly, trending)}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send volunteer opportunity digest notifications';

    /**
     * Execute the console command.
     */
    public function handle(VolunteerNotificationService $notificationService): int
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No notifications will be sent');
        }

        $this->info("Starting {$type} volunteer digest...");

        try {
            switch ($type) {
                case 'weekly':
                    if (!$dryRun) {
                        $notificationService->sendWeeklyDigest();
                    }
                    $this->info('Weekly digest sent successfully');
                    break;

                case 'trending':
                    if (!$dryRun) {
                        $notificationService->sendTrendingOpportunities();
                    }
                    $this->info('Trending opportunities notifications sent successfully');
                    break;

                default:
                    $this->error("Unknown digest type: {$type}");
                    return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to send {$type} digest: " . $e->getMessage());
            Log::error("Volunteer digest command failed", [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}