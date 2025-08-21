<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class ForumPerformanceService
{
    private ForumCacheService $cacheService;

    public function __construct(ForumCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Optimize forum queries with eager loading
     */
    public function optimizeForumQuery(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
            'latestThread:id,forum_id,title,user_id,updated_at',
            'latestThread.user:id,name,email'
        ])
        ->withCount(['threads', 'posts'])
        ->select([
            'id',
            'organization_id',
            'name',
            'description',
            'is_active',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Create database indexes for better performance
     */
    public function createOptimalIndexes(): array
    {
        $indexes = [];

        try {
            // Forum indexes
            DB::statement('CREATE INDEX IF NOT EXISTS idx_forums_organization_active ON forums(organization_id, is_active)');
            $indexes[] = 'idx_forums_organization_active';

            // Thread indexes
            DB::statement('CREATE INDEX IF NOT EXISTS idx_threads_forum_updated ON forum_threads(forum_id, updated_at DESC)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_threads_user_created ON forum_threads(user_id, created_at DESC)');
            $indexes[] = 'idx_threads_forum_updated';
            $indexes[] = 'idx_threads_user_created';

            // Post indexes
            DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_thread_created ON forum_posts(thread_id, created_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_user_created ON forum_posts(user_id, created_at DESC)');
            $indexes[] = 'idx_posts_thread_created';
            $indexes[] = 'idx_posts_user_created';

            // Reputation indexes
            DB::statement('CREATE INDEX IF NOT EXISTS idx_reputation_points_rank ON forum_user_reputation(total_points DESC, rank_level DESC)');
            $indexes[] = 'idx_reputation_points_rank';

        } catch (\Exception $e) {
            \Log::warning('Failed to create some database indexes', ['error' => $e->getMessage()]);
        }

        return $indexes;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'cache_stats' => $this->cacheService->getCacheStats(),
            'database_stats' => $this->getDatabaseStats(),
            'recommendations' => $this->getPerformanceRecommendations(),
        ];
    }

    /**
     * Get database statistics
     */
    private function getDatabaseStats(): array
    {
        try {
            $stats = [];
            
            // Table sizes
            $tables = ['forums', 'forum_threads', 'forum_posts', 'forum_votes', 'forum_user_reputation'];
            foreach ($tables as $table) {
                $count = DB::table($table)->count();
                $stats['table_counts'][$table] = $count;
            }

            // Connection info
            $stats['connection_info'] = [
                'driver' => DB::connection()->getDriverName(),
                'database' => DB::connection()->getDatabaseName(),
            ];

            return $stats;
        } catch (\Exception $e) {
            return ['error' => 'Unable to retrieve database statistics'];
        }
    }

    /**
     * Get performance recommendations
     */
    private function getPerformanceRecommendations(): array
    {
        $recommendations = [];

        // Check cache hit rate
        $cacheStats = $this->cacheService->getCacheStats();
        if (isset($cacheStats['hit_rate'])) {
            $hitRate = (float) str_replace('%', '', $cacheStats['hit_rate']);
            if ($hitRate < 80) {
                $recommendations[] = [
                    'type' => 'cache',
                    'priority' => 'high',
                    'message' => 'Cache hit rate is below 80%. Consider increasing cache TTL or warming up caches.',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Clean up old cache entries
     */
    public function cleanupOldCaches(): int
    {
        $cleaned = 0;
        
        try {
            if (config('cache.default') === 'redis') {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                
                // Get all forum-related keys
                $patterns = [
                    'forum_*',
                    'thread_*',
                    'post_*',
                    'user_reputation_*',
                    'leaderboard_*',
                ];
                
                foreach ($patterns as $pattern) {
                    $keys = $redis->keys($pattern);
                    foreach ($keys as $key) {
                        $ttl = $redis->ttl($key);
                        // Remove keys that expire in less than 5 minutes or are expired
                        if ($ttl < 300 && $ttl !== -1) {
                            $redis->del($key);
                            $cleaned++;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to cleanup old caches', ['error' => $e->getMessage()]);
        }
        
        return $cleaned;
    }
}