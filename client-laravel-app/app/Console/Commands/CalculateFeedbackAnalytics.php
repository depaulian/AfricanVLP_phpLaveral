<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FeedbackAnalytics;
use App\Models\Organization;
use Carbon\Carbon;

class CalculateFeedbackAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'feedback:calculate-analytics 
                            {--organization= : Calculate analytics for specific organization ID}
                            {--period=monthly : Period type (daily, weekly, monthly, quarterly, yearly)}
                            {--date= : Specific date to calculate for (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate feedback analytics for organizations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $organizationId = $this->option('organization');
        $periodType = $this->option('period');
        $date = $this->option('date');

        // Validate period type
        if (!in_array($periodType, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
            $this->error('Invalid period type. Must be one of: daily, weekly, monthly, quarterly, yearly');
            return 1;
        }

        // Parse date or use current date
        try {
            $targetDate = $date ? Carbon::parse($date) : now();
        } catch (\Exception $e) {
            $this->error('Invalid date format. Use YYYY-MM-DD format.');
            return 1;
        }

        // Get period boundaries
        [$periodStart, $periodEnd] = $this->getPeriodBoundaries($targetDate, $periodType);

        $this->info("Calculating feedback analytics for period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}");

        // Get organizations to process
        $organizations = $this->getOrganizations($organizationId);

        $feedbackTypes = ['all', 'volunteer_to_organization', 'organization_to_volunteer', 'supervisor_to_volunteer', 'beneficiary_to_volunteer'];

        $totalCalculations = 0;

        foreach ($organizations as $organization) {
            $this->info("Processing organization: {$organization->name}");
            
            foreach ($feedbackTypes as $feedbackType) {
                $this->line("  - Calculating {$feedbackType} analytics...");
                
                try {
                    FeedbackAnalytics::calculateAllAnalytics(
                        $organization,
                        $feedbackType,
                        $periodType,
                        $periodStart,
                        $periodEnd
                    );
                    $totalCalculations++;
                } catch (\Exception $e) {
                    $this->error("    Error calculating {$feedbackType}: " . $e->getMessage());
                }
            }
        }

        // Also calculate global analytics (no organization)
        if (!$organizationId) {
            $this->info("Processing global analytics...");
            
            foreach ($feedbackTypes as $feedbackType) {
                $this->line("  - Calculating global {$feedbackType} analytics...");
                
                try {
                    FeedbackAnalytics::calculateAllAnalytics(
                        null,
                        $feedbackType,
                        $periodType,
                        $periodStart,
                        $periodEnd
                    );
                    $totalCalculations++;
                } catch (\Exception $e) {
                    $this->error("    Error calculating global {$feedbackType}: " . $e->getMessage());
                }
            }
        }

        $this->info("Completed! Calculated {$totalCalculations} analytics entries.");
        
        return 0;
    }

    /**
     * Get period boundaries based on date and period type
     */
    private function getPeriodBoundaries(Carbon $date, string $periodType): array
    {
        return match ($periodType) {
            'daily' => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay()
            ],
            'weekly' => [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek()
            ],
            'monthly' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth()
            ],
            'quarterly' => [
                $date->copy()->startOfQuarter(),
                $date->copy()->endOfQuarter()
            ],
            'yearly' => [
                $date->copy()->startOfYear(),
                $date->copy()->endOfYear()
            ],
            default => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth()
            ]
        };
    }

    /**
     * Get organizations to process
     */
    private function getOrganizations(?string $organizationId)
    {
        if ($organizationId) {
            $organization = Organization::find($organizationId);
            if (!$organization) {
                $this->error("Organization with ID {$organizationId} not found.");
                return collect();
            }
            return collect([$organization]);
        }

        return Organization::all();
    }
}