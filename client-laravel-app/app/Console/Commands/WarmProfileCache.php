<?php

namespace App\Console\Commands;

use App\Services\ProfileCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WarmProfileCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profile:warm-cache 
                            {--users=* : Specific user IDs to warm cache for}
                            {--active : Warm cache for recently active users}
                            {--limit=100 : Limit number of users to process}
                            {--force : Force refresh existing cache}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up profile caches for better performance';

    protected ProfileCacheService $cacheService;

    public function __construct(ProfileCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting profile cache warming...');
        
        $userIds = $this->option('users');
        $activeOnly = $this->option('active');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        try {
            if (!empty($userIds)) {
                $this->warmSpecificUsers($userIds, $force);
            } elseif ($activeOnly) {
                $this->warmActiveUsers($limit, $force);
            } else {
                $this->warmAllUsers($limit, $force);
            }

            $this->info('Profile cache warming completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Cache warming failed: ' . $e->getMessage());
            Log::error('Profile cache warming failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Warm cache for specific users
     */
    protected function warmSpecificUsers(array $userIds, bool $force): void
    {
        $this->info("Warming cache for " . count($userIds) . " specific users...");
        
        $bar = $this->output->createProgressBar(count($userIds));
        $bar->start();

        foreach ($userIds as $userId) {
            try {
                if ($force || !$this->cacheService->hasCachedData($userId)) {
                    $this->cacheService->warmUpUserCache($userId);
                }
                $bar->advance();
            } catch (\Exception $e) {
                $this->warn("Failed to warm cache for user {$userId}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Warm cache for active users
     */
    protected function warmActiveUsers(int $limit, bool $force): void
    {
        $this->info("Warming cache for up to {$limit} active users...");
        
        // This would use the preloadActiveUserProfiles method
        $this->cacheService->preloadActiveUserProfiles($limit);
        
        $this->info("Active user cache warming completed.");
    }

    /**
     * Warm cache for all users (with limit)
     */
    protected function warmAllUsers(int $limit, bool $force): void
    {
        $this->info("Warming cache for up to {$limit} users...");
        
        // Get user IDs in batches
        $userIds = \App\Models\User::whereHas('profile')
            ->limit($limit)
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            $this->warn('No users found to warm cache for.');
            return;
        }

        $this->warmSpecificUsers($userIds, $force);
    }
}