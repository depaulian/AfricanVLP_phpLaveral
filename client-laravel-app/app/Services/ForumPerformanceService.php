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
     * Optimize thread queries with eager loading
     */
    public function optimizeThreadQuery(Builder $query): Builder
    {
        return $query->with([
            'user:id,name,email',
            'user.forumUserReputation:user_id,total_points,rank,rank_level',
            'forum:id,name,organization_id',
            'latestPost:id,thread_id,user_id,created_at',
            'latestPost.user:id,name'
        ])
        ->withCount(['posts', 'votes'])
        ->select([
            'id',
            'forum_id',
            'user_id',
            'title',
            'is_pinned',
            'is_locked',
            'is_solved',
            'views_count',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Optimize post queries with eager loading
     */
    public function optimizePostQuery(Builder $query): Builder
    {
        return $query->with([
            'user:id,name,email',
            'user.forumUserReputation:user_id,total_points,rank,rank_level',
            'attachments:id,post_id,filename,file_path,file_size',
            'votes' => function ($query) {
                $query->select('id', 'voteable_id', 'user_id', 'vote_type');
            }
        ])
        ->select([
            'id',
            'thread_id',
            'user_id',
            'parent_id',
            'content',
            'is_solution',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Get optimized forum list with caching
     */
    public function getOptimizedForumList(?int $organizationId = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cacheService->getForumList($organizationId);
    }

    /**
     * Get optimized thread list with pagination and caching
     */
    public function getOptimizedThreadList(int $forumId, array $options = []): array
    {
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 20;
        $sort = $options['sort'] ?? 'latest';
        
        return $this->cacheService->getThreadList($forumId, $page, $perPage, $sort);
    }

    /**
     * Get optimized thread details with caching
     */
    public function getOptimizedThreadDetails(int $threadId): ?ForumThread
    {
        return $this->cacheService->getThreadDetails($threadId);
    }

    /**
     * Batch update thread view counts
     */
    public function batchUpdateViewCounts(array $threadIds): void
    {
        if (empty($threadIds)) {
            return;
        }

        // Use raw SQL for better performance
        DB::statement(
            "UPDATE forum_threads SET views_count = views_count + 1 WHERE id IN (" . 
            implode(',', array_fill(0, count($threadIds), '?')) . ")",
            $threadIds
        );

        // Invalidate relevant caches
        foreach ($threadIds as $threadId) {
            $thread = ForumThread::find($threadId);
            if ($thread) {
                $this->cacheService->invalidateThreadCaches($threadId, $thread->forum_id);
            }
        }
    }

    /**
     * Optimize search queries
     */
    public function optimizeSearchQuery(string $query, array $filters = []): Builder
    {
        $searchQuery = ForumThread::query();

        // Use full-text search if available
        if (DB::connection()->getDriverName() === 'mysql') {
            $searchQuery->whereRaw(
                "MATCH(title, content) AGAINST(? IN BOOLEAN MODE)",
                [$query]
            );
        } else {
            // Fallback to LIKE search
            $searchQuery->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            });
        }

        // Apply filters
        if (!empty($filters['forum_id'])) {
            $searchQuery->where('forum_id', $filters['forum_id']);
        }

        if (!empty($filters['user_id'])) {
            $searchQuery->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $searchQuery->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $searchQuery->where('created_at', '<=', $filters['date_to']);
        }

        return $this->optimizeThreadQuery($searchQuery);
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
            DB::statement('CREATE INDEX IF NOT EXISTS idx_threads_pinned_updated ON forum_threads(is_pinned DESC, updated_at DESC)');
            $indexes[] = 'idx_threads_forum_updated';
            $indexes[] = 'idx_threads_user_created';
            $indexes[] = 'idx_threads_pinned_updated';

            // Post indexes
            DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_thread_created ON forum_posts(thread_id, created_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_user_created ON forum_posts(user_id, created_at DESC)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_parent_created ON forum_posts(parent_id, created_at)');
            $indexes[] = 'idx_posts_thread_created';
            $indexes[] = 'idx_posts_user_created';
            $indexes[] = 'idx_posts_parent_created';

            // Vote indexes
            DB::statement('CREATE INDEX IF NOT EXISTS idx_votes_voteable_type ON forum_votes(voteable_type, voteable_id, vote_type)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_votes_user_created ON forum_votes(user_id, created_at DESC)');
            $indexes[] = 'idx_votes_voteable_type';
            $indexes[] = 'idx_votes_user_created';

            // Reputation indexes
            DB::statement('CREATE INDEX IF NOT EXISTS idx_reputation_points_rank ON forum_user_reputation(total_points DESC, rank_level DESC)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_reputation_activity ON forum_user_reputation(last_activity_date DESC, consecutive_days_active DESC)');
            $indexes[] = 'idx_reputation_points_rank';
            $indexes[] = 'idx_reputation_activity';

            // Full-text indexes for search
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE forum_threads ADD FULLTEXT(title, content)');
                DB::statement('ALTER TABLE forum_posts ADD FULLTEXT(content)');
                $indexes[] = 'fulltext_threads_title_content';
                $indexes[] = 'fulltext_posts_content';
            }

        } catch (\Exception $e) {
            \Log::warning('Failed to create some database indexes', ['error' => $e->getMessage()]);
        }

        return $indexes;
    }

    /**
     * Analyze query performance
     */
    public function analyzeQueryPerformance(): array
    {
        $queries = [
            'forum_list' => 'SELECT * FROM forums WHERE is_active = 1 ORDER BY name',
            'thread_list' => 'SELECT * FROM forum_threads WHERE forum_id = 1 ORDER BY updated_at DESC LIMIT 20',
            'post_list' => 'SELECT * FROM forum_posts WHERE thread_id = 1 ORDER BY created_at LIMIT 20',
            'user_reputation' => 'SELECT * FROM forum_user_reputation ORDER BY total_points DESC LIMIT 10',
        ];

        $results = [];

        foreach ($queries as $name => $sql) {
            try {
                $start = microtime(true);
                DB::select($sql);
                $end = microtime(true);
                
                $results[$name] = [
                    'execution_time' => round(($end - $start) * 1000, 2) . 'ms',
                    'query' => $sql,
                ];

                // Get query plan if MySQL
                if (DB::connection()->getDriverName() === 'mysql') {
                    $explain = DB::select("EXPLAIN $sql");
                    $results[$name]['explain'] = $explain;
                }
            } catch (\Exception $e) {
                $results[$name] = [
                    'error' => $e->getMessage(),
                    'query' => $sql,
                ];
            }
        }

        return $results;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'cache_stats' => $this->cacheService->getCacheStats(),
            'database_stats' => $this->getDatabaseStats(),
            'query_analysis' => $this->analyzeQueryPerformance(),
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

        // Check table sizes
        $stats = $this->getDatabaseStats();
        if (isset($stats['table_counts'])) {
            foreach ($stats['table_counts'] as $table => $count) {
                if ($count > 100000) {
                    $recommendations[] = [
                        'type' => 'database',
                        'priority' => 'medium',
                        'message' => "Table {$table} has {$count} records. Consider archiving old data or partitioning.",
                    ];
                }
            }
        }

        // Check if indexes exist
        try {
            $indexes = DB::select("SHOW INDEX FROM forum_threads WHERE Key_name = 'idx_threads_forum_updated'");
            if (empty($indexes)) {
                $recommendations[] = [
                    'type' => 'database',
                    'priority' => 'high',
                    'message' => 'Missing performance indexes. Run createOptimalIndexes() method.',
                ];
            }
        } catch (\Exception $e) {
            // Ignore if not MySQL
        }

        return $recommendations;
    }

    /**
     * Optimize forum data for CDN delivery
     */
    public function optimizeForCDN(array $data): array
    {
        // Remove sensitive data
        $optimized = $this->removeSensitiveData($data);
        
        // Add cache headers
        $optimized['cache_headers'] = [
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => md5(json_encode($optimized)),
            'Last-Modified' => now()->toRfc7231String(),
        ];
        
        return $optimized;
    }

    /**
     * Remove sensitive data from forum data
     */
    private function removeSensitiveData(array $data): array
    {
        // Remove email addresses and other sensitive info
        array_walk_recursive($data, function (&$value, $key) {
            if (in_array($key, ['email', 'password', 'remember_token', 'email_verified_at'])) {
                unset($value);
            }
        });
        
        return $data;
    }

    /**
     * Preload and cache popular content
     */
    public function preloadPopularContent(): void
    {
        // Cache popular threads
        $this->cacheService->getPopularThreads(20, 7);
        
        // Cache leaderboard
        $this->cacheService->getLeaderboard(20);
        
        // Cache forum stats for active forums
        $activeForums = Forum::where('is_active', true)->pluck('id');
        foreach ($activeForums as $forumId) {
            $this->cacheService->getForumStats($forumId);
        }
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