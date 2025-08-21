<?php

namespace App\Services;

use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\VolunteerApplication;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class VolunteeringCacheService
{
    const CACHE_TTL = 3600; // 1 hour
    const LONG_CACHE_TTL = 86400; // 24 hours
    const SHORT_CACHE_TTL = 300; // 5 minutes

    /**
     * Cache opportunity data
     */
    public function cacheOpportunity(VolunteeringOpportunity $opportunity): void
    {
        $key = "opportunity:{$opportunity->id}";
        
        Cache::put($key, [
            'id' => $opportunity->id,
            'title' => $opportunity->title,
            'description' => $opportunity->description,
            'organization' => $opportunity->organization->name,
            'category' => $opportunity->category->name,
            'city' => $opportunity->city->name,
            'volunteers_needed' => $opportunity->volunteers_needed,
            'application_deadline' => $opportunity->application_deadline,
            'start_date' => $opportunity->start_date,
            'end_date' => $opportunity->end_date,
            'status' => $opportunity->status,
            'featured' => $opportunity->featured,
            'urgent' => $opportunity->urgent,
            'skills_required' => $opportunity->skills_required,
            'cached_at' => now()
        ], self::CACHE_TTL);
    }

    /**
     * Get cached opportunity data
     */
    public function getCachedOpportunity(int $opportunityId): ?array
    {
        return Cache::get("opportunity:{$opportunityId}");
    }

    /**
     * Cache opportunity list with filters
     */
    public function cacheOpportunityList(array $filters, $opportunities): void
    {
        $key = $this->generateListCacheKey($filters);
        
        Cache::put($key, [
            'data' => $opportunities->toArray(),
            'filters' => $filters,
            'cached_at' => now()
        ], self::SHORT_CACHE_TTL);
    }

    /**
     * Get cached opportunity list
     */
    public function getCachedOpportunityList(array $filters): ?array
    {
        $key = $this->generateListCacheKey($filters);
        return Cache::get($key);
    }

    /**
     * Cache user application data
     */
    public function cacheUserApplications(int $userId): void
    {
        $applications = VolunteerApplication::with(['opportunity', 'assignment'])
            ->where('user_id', $userId)
            ->get();

        Cache::put("user_applications:{$userId}", [
            'applications' => $applications->toArray(),
            'count' => $applications->count(),
            'pending_count' => $applications->where('status', 'pending')->count(),
            'accepted_count' => $applications->where('status', 'accepted')->count(),
            'cached_at' => now()
        ], self::CACHE_TTL);
    }

    /**
     * Get cached user applications
     */
    public function getCachedUserApplications(int $userId): ?array
    {
        return Cache::get("user_applications:{$userId}");
    }

    /**
     * Cache opportunity statistics
     */
    public function cacheOpportunityStats(int $opportunityId): void
    {
        $opportunity = VolunteeringOpportunity::find($opportunityId);
        
        if (!$opportunity) {
            return;
        }

        $stats = [
            'total_applications' => $opportunity->applications()->count(),
            'accepted_applications' => $opportunity->applications()->where('status', 'accepted')->count(),
            'pending_applications' => $opportunity->applications()->where('status', 'pending')->count(),
            'available_spots' => $opportunity->getAvailableSpots(),
            'is_full' => $opportunity->isFull(),
            'days_until_deadline' => $opportunity->getDaysUntilDeadline(),
            'cached_at' => now()
        ];

        Cache::put("opportunity_stats:{$opportunityId}", $stats, self::CACHE_TTL);
    }

    /**
     * Get cached opportunity statistics
     */
    public function getCachedOpportunityStats(int $opportunityId): ?array
    {
        return Cache::get("opportunity_stats:{$opportunityId}");
    }

    /**
     * Cache popular opportunities
     */
    public function cachePopularOpportunities(): void
    {
        $opportunities = VolunteeringOpportunity::with(['organization', 'category', 'city'])
            ->withCount('applications')
            ->where('status', 'active')
            ->where('application_deadline', '>', now())
            ->orderBy('applications_count', 'desc')
            ->limit(10)
            ->get();

        Cache::put('popular_opportunities', $opportunities->toArray(), self::LONG_CACHE_TTL);
    }

    /**
     * Get cached popular opportunities
     */
    public function getCachedPopularOpportunities(): ?array
    {
        return Cache::get('popular_opportunities');
    }

    /**
     * Cache featured opportunities
     */
    public function cacheFeaturedOpportunities(): void
    {
        $opportunities = VolunteeringOpportunity::with(['organization', 'category', 'city'])
            ->where('featured', true)
            ->where('status', 'active')
            ->where('application_deadline', '>', now())
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        Cache::put('featured_opportunities', $opportunities->toArray(), self::LONG_CACHE_TTL);
    }

    /**
     * Get cached featured opportunities
     */
    public function getCachedFeaturedOpportunities(): ?array
    {
        return Cache::get('featured_opportunities');
    }

    /**
     * Cache categories with opportunity counts
     */
    public function cacheCategoriesWithCounts(): void
    {
        $categories = VolunteeringCategory::withCount([
            'opportunities' => function ($query) {
                $query->where('status', 'active')
                      ->where('application_deadline', '>', now());
            }
        ])->orderBy('name')->get();

        Cache::put('categories_with_counts', $categories->toArray(), self::LONG_CACHE_TTL);
    }

    /**
     * Get cached categories with counts
     */
    public function getCachedCategoriesWithCounts(): ?array
    {
        return Cache::get('categories_with_counts');
    }

    /**
     * Cache user matching opportunities
     */
    public function cacheUserMatchingOpportunities(int $userId): void
    {
        $user = User::with(['volunteeringInterests', 'skills'])->find($userId);
        
        if (!$user) {
            return;
        }

        // Get opportunities matching user interests and skills
        $opportunities = VolunteeringOpportunity::with(['organization', 'category', 'city'])
            ->where('status', 'active')
            ->where('application_deadline', '>', now())
            ->whereHas('category', function ($query) use ($user) {
                $query->whereIn('id', $user->volunteeringInterests->pluck('category_id'));
            })
            ->limit(20)
            ->get();

        Cache::put("user_matching_opportunities:{$userId}", [
            'opportunities' => $opportunities->toArray(),
            'count' => $opportunities->count(),
            'cached_at' => now()
        ], self::CACHE_TTL);
    }

    /**
     * Get cached user matching opportunities
     */
    public function getCachedUserMatchingOpportunities(int $userId): ?array
    {
        return Cache::get("user_matching_opportunities:{$userId}");
    }

    /**
     * Invalidate opportunity cache
     */
    public function invalidateOpportunityCache(int $opportunityId): void
    {
        Cache::forget("opportunity:{$opportunityId}");
        Cache::forget("opportunity_stats:{$opportunityId}");
        
        // Clear related list caches
        $this->clearListCaches();
    }

    /**
     * Invalidate user cache
     */
    public function invalidateUserCache(int $userId): void
    {
        Cache::forget("user_applications:{$userId}");
        Cache::forget("user_matching_opportunities:{$userId}");
    }

    /**
     * Clear all opportunity list caches
     */
    public function clearListCaches(): void
    {
        Cache::forget('popular_opportunities');
        Cache::forget('featured_opportunities');
        Cache::forget('categories_with_counts');
        
        // Clear filtered list caches (pattern-based clearing)
        $this->clearCacheByPattern('opportunity_list:*');
    }

    /**
     * Generate cache key for opportunity lists
     */
    private function generateListCacheKey(array $filters): string
    {
        ksort($filters);
        $filterString = http_build_query($filters);
        return 'opportunity_list:' . md5($filterString);
    }

    /**
     * Clear cache by pattern (Redis specific)
     */
    private function clearCacheByPattern(string $pattern): void
    {
        if (config('cache.default') === 'redis') {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        }
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUpCache(): void
    {
        $this->cachePopularOpportunities();
        $this->cacheFeaturedOpportunities();
        $this->cacheCategoriesWithCounts();
        
        // Cache recent opportunities
        $recentOpportunities = VolunteeringOpportunity::with(['organization', 'category', 'city'])
            ->where('status', 'active')
            ->where('application_deadline', '>', now())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        foreach ($recentOpportunities as $opportunity) {
            $this->cacheOpportunity($opportunity);
            $this->cacheOpportunityStats($opportunity->id);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'total_keys' => 0,
            'memory_usage' => 0,
            'hit_rate' => 0,
        ];

        if (config('cache.default') === 'redis') {
            $info = Redis::info();
            $stats['total_keys'] = $info['db0']['keys'] ?? 0;
            $stats['memory_usage'] = $info['used_memory_human'] ?? '0B';
            
            // Calculate hit rate if available
            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $hits = $info['keyspace_hits'];
                $misses = $info['keyspace_misses'];
                $total = $hits + $misses;
                $stats['hit_rate'] = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
            }
        }

        return $stats;
    }
}