<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\ForumUserReputation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Collection;

class ForumCacheService
{
    // Cache TTL constants (in seconds)
    public const FORUM_LIST_TTL = 3600; // 1 hour
    public const THREAD_LIST_TTL = 1800; // 30 minutes
    public const THREAD_DETAIL_TTL = 900; // 15 minutes
    public const POST_LIST_TTL = 600; // 10 minutes
    public const USER_REPUTATION_TTL = 1800; // 30 minutes
    public const LEADERBOARD_TTL = 3600; // 1 hour
    public const FORUM_STATS_TTL = 1800; // 30 minutes
    public const POPULAR_THREADS_TTL = 3600; // 1 hour

    /**
     * Get cached forum list
     */
    public function getForumList(int $organizationId = null): Collection
    {
        $cacheKey = $this->getForumListCacheKey($organizationId);
        
        return Cache::remember($cacheKey, self::FORUM_LIST_TTL, function () use ($organizationId) {
            $query = Forum::with(['organization', 'latestThread.user'])
                ->withCount(['threads', 'posts']);
            
            if ($organizationId) {
                $query->where('organization_id', $organizationId);
            }
            
            return $query->orderBy('name')->get();
        });
    }

    /**
     * Get cached thread list for a forum
     */
    public function getThreadList(int $forumId, int $page = 1, int $perPage = 20, string $sort = 'latest'): array
    {
        $cacheKey = $this->getThreadListCacheKey($forumId, $page, $perPage, $sort);
        
        return Cache::remember($cacheKey, self::THREAD_LIST_TTL, function () use ($forumId, $page, $perPage, $sort) {
            $query = ForumThread::with(['user', 'forum', 'latestPost.user'])
                ->where('forum_id', $forumId)
                ->withCount(['posts', 'votes']);
            
            // Apply sorting
            switch ($sort) {
                case 'popular':
                    $query->orderByDesc('votes_count')->orderByDesc('posts_count');
                    break;
                case 'oldest':
                    $query->orderBy('created_at');
                    break;
                case 'latest':
                default:
                    $query->orderByDesc('updated_at');
                    break;
            }
            
            $threads = $query->paginate($perPage, ['*'], 'page', $page);
            
            return [
                'data' => $threads->items(),
                'pagination' => [
                    'current_page' => $threads->currentPage(),
                    'last_page' => $threads->lastPage(),
                    'per_page' => $threads->perPage(),
                    'total' => $threads->total(),
                ],
            ];
        });
    }

    /**
     * Get cached thread details
     */
    public function getThreadDetails(int $threadId): ?ForumThread
    {
        $cacheKey = $this->getThreadDetailsCacheKey($threadId);
        
        return Cache::remember($cacheKey, self::THREAD_DETAIL_TTL, function () use ($threadId) {
            return ForumThread::with([
                'user.forumUserReputation',
                'forum',
                'posts' => function ($query) {
                    $query->with(['user.forumUserReputation', 'attachments', 'votes'])
                        ->orderBy('created_at');
                },
                'posts.children' => function ($query) {
                    $query->with(['user.forumUserReputation', 'attachments'])
                        ->orderBy('created_at');
                }
            ])->find($threadId);
        });
    }

    /**
     * Get cached user reputation
     */
    public function getUserReputation(int $userId): ?ForumUserReputation
    {
        $cacheKey = $this->getUserReputationCacheKey($userId);
        
        return Cache::remember($cacheKey, self::USER_REPUTATION_TTL, function () use ($userId) {
            return ForumUserReputation::where('user_id', $userId)->first();
        });
    }

    /**
     * Get cached leaderboard
     */
    public function getLeaderboard(int $limit = 10): Collection
    {
        $cacheKey = $this->getLeaderboardCacheKey($limit);
        
        return Cache::remember($cacheKey, self::LEADERBOARD_TTL, function () use ($limit) {
            return ForumUserReputation::with('user')
                ->orderByDesc('total_points')
                ->orderByDesc('rank_level')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get cached forum statistics
     */
    public function getForumStats(int $forumId): array
    {
        $cacheKey = $this->getForumStatsCacheKey($forumId);
        
        return Cache::remember($cacheKey, self::FORUM_STATS_TTL, function () use ($forumId) {
            $forum = Forum::find($forumId);
            if (!$forum) {
                return [];
            }
            
            return [
                'total_threads' => ForumThread::where('forum_id', $forumId)->count(),
                'total_posts' => ForumPost::whereHas('thread', function ($query) use ($forumId) {
                    $query->where('forum_id', $forumId);
                })->count(),
                'active_users_today' => ForumPost::whereHas('thread', function ($query) use ($forumId) {
                    $query->where('forum_id', $forumId);
                })
                ->where('created_at', '>=', now()->startOfDay())
                ->distinct('user_id')
                ->count('user_id'),
                'latest_activity' => ForumPost::whereHas('thread', function ($query) use ($forumId) {
                    $query->where('forum_id', $forumId);
                })
                ->with('user')
                ->latest()
                ->first(),
            ];
        });
    }

    /**
     * Invalidate forum-related caches
     */
    public function invalidateForumCaches(int $forumId): void
    {
        $patterns = [
            "forum_list_*",
            "thread_list_{$forumId}_*",
            "forum_stats_{$forumId}",
            "popular_threads_*",
        ];
        
        foreach ($patterns as $pattern) {
            $this->invalidateCachePattern($pattern);
        }
    }

    /**
     * Clear all forum caches
     */
    public function clearAllForumCaches(): void
    {
        $patterns = [
            'forum_list_*',
            'thread_list_*',
            'thread_details_*',
            'post_list_*',
            'user_reputation_*',
            'leaderboard_*',
            'forum_stats_*',
            'popular_threads_*',
        ];
        
        foreach ($patterns as $pattern) {
            $this->invalidateCachePattern($pattern);
        }
    }

    /**
     * Generate cache keys
     */
    private function getForumListCacheKey(?int $organizationId): string
    {
        return "forum_list_" . ($organizationId ?? 'all');
    }

    private function getThreadListCacheKey(int $forumId, int $page, int $perPage, string $sort): string
    {
        return "thread_list_{$forumId}_{$page}_{$perPage}_{$sort}";
    }

    private function getThreadDetailsCacheKey(int $threadId): string
    {
        return "thread_details_{$threadId}";
    }

    private function getUserReputationCacheKey(int $userId): string
    {
        return "user_reputation_{$userId}";
    }

    private function getLeaderboardCacheKey(int $limit): string
    {
        return "leaderboard_{$limit}";
    }

    private function getForumStatsCacheKey(int $forumId): string
    {
        return "forum_stats_{$forumId}";
    }

    /**
     * Invalidate cache by pattern
     */
    private function invalidateCachePattern(string $pattern): void
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                Cache::forget($pattern);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to invalidate cache pattern: ' . $pattern, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cache usage statistics
     */
    public function getCacheStats(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info('memory');
            
            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'used_memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'keyspace_hits' => $redis->info('stats')['keyspace_hits'] ?? 0,
                'keyspace_misses' => $redis->info('stats')['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($redis),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate($redis): string
    {
        try {
            $stats = $redis->info('stats');
            $hits = $stats['keyspace_hits'] ?? 0;
            $misses = $stats['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            
            if ($total === 0) {
                return '0%';
            }
            
            $hitRate = ($hits / $total) * 100;
            return number_format($hitRate, 2) . '%';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}