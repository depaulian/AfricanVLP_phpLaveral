<?php

namespace App\Console\Commands;

use App\Services\ForumCacheService;
use App\Services\ForumPerformanceService;
use App\Models\Forum;
use Illuminate\Console\Command;

class ForumCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'forum:cache 
                            {action : The action to perform (warm, clear, stats, cleanup)}
                            {--forum= : Specific forum ID to target}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage forum caching operations';

    private ForumCacheService $cacheService;
    private ForumPerformanceService $performanceService;

    public function __construct(ForumCacheService $cacheService, ForumPerformanceService $performanceService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $forumId = $this->option('forum');
        $force = $this->option('force');

        switch ($action) {
            case 'warm':
                return $this->warmCaches($forumId);
            
            case 'clear':
                return $this->clearCaches($forumId, $force);
            
            case 'stats':
                return $this->showCacheStats();
            
            case 'cleanup':
                return $this->cleanupCaches($force);
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: warm, clear, stats, cleanup');
                return 1;
        }
    }

    /**
     * Warm up forum caches
     */
    private function warmCaches(?string $forumId): int
    {
        $this->info('Warming up forum caches...');
        
        if ($forumId) {
            $forum = Forum::find($forumId);
            if (!$forum) {
                $this->error("Forum with ID {$forumId} not found.");
                return 1;
            }
            
            $this->info("Warming cache for forum: {$forum->name}");
            $this->cacheService->warmUpForumCaches($forum->id);
            $this->info('✓ Forum cache warmed up successfully');
        } else {
            $forums = Forum::where('is_active', true)->get();
            $progressBar = $this->output->createProgressBar($forums->count());
            $progressBar->start();
            
            foreach ($forums as $forum) {
                $this->cacheService->warmUpForumCaches($forum->id);
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            $this->info("✓ Warmed up caches for {$forums->count()} forums");
        }
        
        // Warm up global caches
        $this->info('Warming up global caches...');
        $this->cacheService->getLeaderboard(20);
        $this->cacheService->getPopularThreads(20, 7);
        $this->performanceService->preloadPopularContent();
        $this->info('✓ Global caches warmed up successfully');
        
        return 0;
    }

    /**
     * Clear forum caches
     */
    private function clearCaches(?string $forumId, bool $force): int
    {
        if (!$force && !$this->confirm('Are you sure you want to clear forum caches?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $this->info('Clearing forum caches...');
        
        if ($forumId) {
            $forum = Forum::find($forumId);
            if (!$forum) {
                $this->error("Forum with ID {$forumId} not found.");
                return 1;
            }
            
            $this->cacheService->invalidateForumCaches($forum->id);
            $this->info("✓ Cleared cache for forum: {$forum->name}");
        } else {
            $this->cacheService->clearAllForumCaches();
            $this->info('✓ All forum caches cleared successfully');
        }
        
        return 0;
    }

    /**
     * Show cache statistics
     */
    private function showCacheStats(): int
    {
        $this->info('Forum Cache Statistics');
        $this->info('====================');
        
        $stats = $this->cacheService->getCacheStats();
        
        if (isset($stats['error'])) {
            $this->error('Error retrieving cache stats: ' . $stats['error']);
            return 1;
        }
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Used Memory', $stats['used_memory'] ?? 'N/A'],
                ['Peak Memory', $stats['used_memory_peak'] ?? 'N/A'],
                ['Cache Hits', number_format($stats['keyspace_hits'] ?? 0)],
                ['Cache Misses', number_format($stats['keyspace_misses'] ?? 0)],
                ['Hit Rate', $stats['hit_rate'] ?? 'N/A'],
            ]
        );
        
        // Show performance metrics
        $this->newLine();
        $this->info('Performance Metrics');
        $this->info('==================');
        
        $performanceMetrics = $this->performanceService->getPerformanceMetrics();
        
        if (isset($performanceMetrics['recommendations'])) {
            $this->info('Recommendations:');
            foreach ($performanceMetrics['recommendations'] as $recommendation) {
                $priority = strtoupper($recommendation['priority']);
                $this->line("  [{$priority}] {$recommendation['message']}");
            }
        }
        
        return 0;
    }

    /**
     * Cleanup old caches
     */
    private function cleanupCaches(bool $force): int
    {
        if (!$force && !$this->confirm('Clean up old and expired cache entries?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $this->info('Cleaning up old cache entries...');
        
        $cleaned = $this->performanceService->cleanupOldCaches();
        
        $this->info("✓ Cleaned up {$cleaned} old cache entries");
        
        return 0;
    }
}