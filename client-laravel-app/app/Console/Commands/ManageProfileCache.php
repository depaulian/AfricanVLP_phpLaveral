<?php

namespace App\Console\Commands;

use App\Services\ProfileCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageProfileCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profile:cache 
                            {action : Action to perform (clear, stats, warm, invalidate)}
                            {--user=* : Specific user IDs}
                            {--type= : Cache type (profile, complete, stats, skills, interests, history, documents, analytics)}
                            {--all : Apply to all users}';

    /**
     * The console command description.
     */
    protected $description = 'Manage profile caches (clear, stats, warm, invalidate)';

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
        $action = $this->argument('action');
        $userIds = $this->option('user');
        $type = $this->option('type');
        $all = $this->option('all');

        try {
            switch ($action) {
                case 'clear':
                    return $this->clearCache($userIds, $type, $all);
                    
                case 'stats':
                    return $this->showStats();
                    
                case 'warm':
                    return $this->warmCache($userIds, $all);
                    
                case 'invalidate':
                    return $this->invalidateCache($userIds, $type, $all);
                    
                default:
                    $this->error("Unknown action: {$action}");
                    $this->info("Available actions: clear, stats, warm, invalidate");
                    return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            Log::error('Profile cache management failed', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Clear profile caches
     */
    protected function clearCache(array $userIds, ?string $type, bool $all): int
    {
        if ($all) {
            $this->info('Clearing all profile caches...');
            $this->cacheService->clearAllProfileCaches();
            $this->info('All profile caches cleared successfully!');
            return Command::SUCCESS;
        }

        if (empty($userIds)) {
            $this->error('Please specify user IDs or use --all flag');
            return Command::FAILURE;
        }

        $this->info("Clearing cache for " . count($userIds) . " users...");
        
        foreach ($userIds as $userId) {
            if ($type) {
                $this->cacheService->invalidateSpecificCache($userId, $type);
                $this->info("Cleared {$type} cache for user {$userId}");
            } else {
                $this->cacheService->invalidateUserCache($userId);
                $this->info("Cleared all caches for user {$userId}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Show cache statistics
     */
    protected function showStats(): int
    {
        $this->info('Profile Cache Statistics');
        $this->info('========================');

        $stats = $this->cacheService->getCacheStats();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cached Profiles', $stats['total_cached_profiles']],
                ['Cache Hit Rate', $stats['cache_hit_rate'] . '%'],
                ['Memory Usage', $stats['memory_usage']],
                ['Last Updated', $stats['last_updated']->format('Y-m-d H:i:s')]
            ]
        );

        // Show sample cache keys
        $this->info("\nSample Cache Status:");
        $sampleUsers = \App\Models\User::limit(5)->pluck('id');
        
        foreach ($sampleUsers as $userId) {
            $this->info("User {$userId}:");
            $types = ['profile', 'complete', 'stats', 'skills', 'interests'];
            
            foreach ($types as $type) {
                $exists = $this->cacheService->hasCachedData($userId, $type);
                $status = $exists ? '✓' : '✗';
                $expiration = $exists ? $this->cacheService->getCacheExpiration($userId, $type) : null;
                $expirationText = $expiration ? $expiration->format('H:i:s') : 'N/A';
                
                $this->line("  {$status} {$type} (expires: {$expirationText})");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Warm up caches
     */
    protected function warmCache(array $userIds, bool $all): int
    {
        if ($all) {
            $this->info('Warming cache for active users...');
            $this->cacheService->preloadActiveUserProfiles(100);
            $this->info('Cache warming completed!');
            return Command::SUCCESS;
        }

        if (empty($userIds)) {
            $this->error('Please specify user IDs or use --all flag');
            return Command::FAILURE;
        }

        $this->info("Warming cache for " . count($userIds) . " users...");
        
        $bar = $this->output->createProgressBar(count($userIds));
        $bar->start();

        foreach ($userIds as $userId) {
            try {
                $this->cacheService->warmUpUserCache($userId);
                $bar->advance();
            } catch (\Exception $e) {
                $this->warn("Failed to warm cache for user {$userId}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Cache warming completed!');

        return Command::SUCCESS;
    }

    /**
     * Invalidate caches
     */
    protected function invalidateCache(array $userIds, ?string $type, bool $all): int
    {
        if ($all) {
            $this->warn('This will invalidate ALL profile caches. Are you sure?');
            if (!$this->confirm('Continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
            
            $this->cacheService->clearAllProfileCaches();
            $this->info('All profile caches invalidated!');
            return Command::SUCCESS;
        }

        if (empty($userIds)) {
            $this->error('Please specify user IDs or use --all flag');
            return Command::FAILURE;
        }

        $this->info("Invalidating cache for " . count($userIds) . " users...");
        
        foreach ($userIds as $userId) {
            if ($type) {
                $this->cacheService->invalidateSpecificCache($userId, $type);
                $this->info("Invalidated {$type} cache for user {$userId}");
            } else {
                $this->cacheService->invalidateUserCache($userId);
                $this->info("Invalidated all caches for user {$userId}");
            }
        }

        return Command::SUCCESS;
    }
}