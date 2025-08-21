<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VolunteeringAnalyticsService;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalculateVolunteeringAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteering:calculate-analytics 
                            {--period=daily : The period to calculate (daily, weekly, monthly, quarterly, yearly)}
                            {--organization= : Specific organization ID to calculate for}
                            {--date= : Specific date to calculate for (YYYY-MM-DD)}
                            {--force : Force recalculation even if data exists}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate volunteering analytics for specified periods';

    /**
     * Create a new command instance.
     */
    public function __construct(
        private VolunteeringAnalyticsService $analyticsService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period');
        $organizationId = $this->option('organization');
        $date = $this->option('date');
        $force = $this->option('force');

        $this->info("Starting volunteering analytics calculation for period: {$period}");

        try {
            // Parse the date or use current date
            $targetDate = $date ? Carbon::parse($date) : now();
            
            // Get date range based on period
            [$startDate, $endDate] = $this->getDateRange($period, $targetDate);
            
            $this->info("Calculating analytics for period: {$startDate->toDateString()} to {$endDate->toDateString()}");

            // Get organizations to process
            $organizations = $this->getOrganizations($organizationId);
            
            $totalOrganizations = $organizations->count();
            $processedOrganizations = 0;

            // Process each organization
            foreach ($organizations as $organization) {
                $this->processOrganization($organization, $startDate, $endDate, $period, $force);
                $processedOrganizations++;
                
                $this->info("Processed {$processedOrganizations}/{$totalOrganizations} organizations");
            }

            // Also calculate global analytics (across all organizations)
            if (!$organizationId) {
                $this->info("Calculating global analytics...");
                $this->analyticsService->calculatePeriodAnalytics($startDate, $endDate, $period, null);
            }

            $this->info("Analytics calculation completed successfully!");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error calculating analytics: " . $e->getMessage());
            Log::error('Volunteering analytics calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'period' => $period,
                'organization_id' => $organizationId,
                'date' => $date,
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process analytics for a specific organization
     */
    private function processOrganization(Organization $organization, Carbon $startDate, Carbon $endDate, string $period, bool $force): void
    {
        $this->line("Processing organization: {$organization->name} (ID: {$organization->id})");
        
        try {
            // Check if analytics already exist for this period
            if (!$force && $this->analyticsExist($organization->id, $startDate, $period)) {
                $this->line("  - Analytics already exist for this period, skipping (use --force to recalculate)");
                return;
            }

            // Calculate analytics for this organization
            $this->analyticsService->calculatePeriodAnalytics($startDate, $endDate, $period, $organization->id);
            
            $this->line("  - Analytics calculated successfully");

        } catch (\Exception $e) {
            $this->error("  - Error processing organization {$organization->name}: " . $e->getMessage());
            Log::error('Organization analytics calculation failed', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'error' => $e->getMessage(),
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        }
    }

    /**
     * Get date range for the specified period
     */
    private function getDateRange(string $period, Carbon $targetDate): array
    {
        return match ($period) {
            'daily' => [
                $targetDate->copy()->startOfDay(),
                $targetDate->copy()->endOfDay()
            ],
            'weekly' => [
                $targetDate->copy()->startOfWeek(),
                $targetDate->copy()->endOfWeek()
            ],
            'monthly' => [
                $targetDate->copy()->startOfMonth(),
                $targetDate->copy()->endOfMonth()
            ],
            'quarterly' => [
                $targetDate->copy()->startOfQuarter(),
                $targetDate->copy()->endOfQuarter()
            ],
            'yearly' => [
                $targetDate->copy()->startOfYear(),
                $targetDate->copy()->endOfYear()
            ],
            default => throw new \InvalidArgumentException("Invalid period: {$period}")
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
                throw new \InvalidArgumentException("Organization with ID {$organizationId} not found");
            }
            return collect([$organization]);
        }

        return Organization::where('is_active', true)->get();
    }

    /**
     * Check if analytics already exist for the given parameters
     */
    private function analyticsExist(int $organizationId, Carbon $startDate, string $period): bool
    {
        return \App\Models\VolunteeringAnalytic::where('organization_id', $organizationId)
            ->where('period_type', $period)
            ->where('period_start', $startDate->toDateString())
            ->exists();
    }
}