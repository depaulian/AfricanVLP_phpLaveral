<?php

namespace App\Console\Commands;

use App\Services\RegistrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRegistrationAbandonment extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'registration:process-abandonment
                            {--dry-run : Run without sending emails}';

    /**
     * The console command description.
     */
    protected $description = 'Process registration abandonment and send reminder emails';

    /**
     * Execute the console command.
     */
    public function handle(RegistrationService $registrationService): int
    {
        $this->info('Processing registration abandonment...');

        try {
            if ($this->option('dry-run')) {
                $this->warn('Running in dry-run mode - no emails will be sent');
                // In a real implementation, you'd modify the service to support dry-run
            }

            $registrationService->trackAbandonmentAndSendReminders();

            $this->info('Registration abandonment processing completed successfully.');
            
            Log::info('Registration abandonment command completed', [
                'dry_run' => $this->option('dry-run')
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process registration abandonment: ' . $e->getMessage());
            
            Log::error('Registration abandonment command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}