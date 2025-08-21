<?php

namespace App\Services;

use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VolunteeringPerformanceService
{
    protected VolunteeringCacheService $cacheService;

    public function __construct(VolunteeringCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get optimized opportunity listing with caching
     */
    public function getOptimizedOpportunityList(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        // Try to get from cache first
        $cacheKey = $this->generateCacheKey($filters, $page, $perPage);
        $cached = $this->cacheService->getCachedOpportunityList($filters + ['page' => $page, 'per_page' => $perPage]);
        
        if ($cached && $this->isCacheValid($cached)) {
            return $cached['data'];
        }

        // Build optimized query
        $query = $this->buildOptimizedQuery($filters);
        
        // Use cursor pagination for better performance on large datasets
        if ($page > 100) {
            $opportunities = $this->getCursorPaginatedResults($query, $filters, $perPage);
        } else {
            $opportunities = $query->paginate($perPage, ['*'], 'page', $page);
        }

        // Cache the results
        $result = [
            'data' => $opportunities->items(),
            'pagination' => [
                'current_page' => $opportunities->currentPage(),
                'last_page' => $opportunities->lastPage(),
                'per_page' => $opportunities->perPage(),
                'total' => $opportunities->total(),
            ],
            'cached_at' => now()
        ];

        $this->cacheService->cacheOpportunityList($filters + ['page' => $page, 'per_page' => $perPage], $result);

        return $result;
    }

    /**
     * Build optimized query with proper indexing
     */
    protected function buildOptimizedQuery(array $filters): Builder
    {
        $query = VolunteeringOpportunity::query()
            ->select([
                'id', 'title', 'slug', 'description', 'organization_id', 
                'category_id', 'city_id', 'location_type', 'start_date', 
                'end_date', 'application_deadline', 'max_volunteers', 
                'current_volunteers', 'featured', 'status', 'created_at'
            ])
            ->with([
                'organization:id,name,slug,logo',
                'category:id,name,slug',
                'city:id,name',
            ]);

        // Apply filters in order of selectivity (most selective first)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'active');
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['featured']) && $filters['featured']) {
            $query->where('featured', true);
        }

        if (!empty($filters['location_type'])) {
            $query->where('location_type', $filters['location_type']);
        }

        // Date filters
        if (!empty($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }

        if (!empty($filters['start_date_to'])) {
            $query->where('start_date', '<=', $filters['start_date_to']);
        }

        // Application deadline filter
        if (!empty($filters['accepting_applications']) && $filters['accepting_applications']) {
            $query->where(function ($q) {
                $q->whereNull('application_deadline')
                  ->orWhere('application_deadline', '>=', now());
            });
        }

        // Search filter (applied last as it's least selective)
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'relevance';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'date':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'deadline':
                $query->orderBy('application_deadline', $sortOrder);
                break;
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'relevance':
            default:
                $query->orderBy('featured', 'desc')
                      ->orderBy('created_at', 'desc');
                break;
        }

        return $query;
    }

    /**
     * Get cursor-based pagination for large datasets
     */
    protected function getCursorPaginatedResults(Builder $query, array $filters, int $perPage): LengthAwarePaginator
    {
        $cursor = $filters['cursor'] ?? null;
        
        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $items = $query->limit($perPage + 1)->get();
        $hasMore = $items->count() > $perPage;
        
        if ($hasMore) {
            $items->pop();
        }

        $nextCursor = $hasMore ? $items->last()->id : null;

        // Create a mock paginator for consistency
        return new LengthAwarePaginator(
            $items,
            $items->count(),
            $perPage,
            1,
            [
                'path' => request()->url(),
                'pageName' => 'cursor',
                'cursor' => $nextCursor,
                'has_more' => $hasMore
            ]
        );
    }

    /**
     * Get user's applications with optimized loading
     */
    public function getUserApplicationsOptimized(int $userId): array
    {
        // Try cache first
        $cached = $this->cacheService->getCachedUserApplications($userId);
        if ($cached && $this->isCacheValid($cached)) {
            return $cached;
        }

        // Optimized query with minimal data
        $applications = VolunteerApplication::select([
                'id', 'user_id', 'opportunity_id', 'status', 
                'applied_at', 'reviewed_at', 'created_at'
            ])
            ->with([
                'opportunity:id,title,slug,organization_id,start_date,end_date',
                'opportunity.organization:id,name,slug'
            ])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [
            'applications' => $applications->toArray(),
            'summary' => [
                'total' => $applications->count(),
                'pending' => $applications->where('status', 'pending')->count(),
                'accepted' => $applications->where('status', 'accepted')->count(),
                'rejected' => $applications->where('status', 'rejected')->count(),
            ],
            'cached_at' => now()
        ];

        $this->cacheService->cacheUserApplications($userId);
        return $result;
    }

    /**
     * Get opportunity statistics with caching
     */
    public function getOpportunityStats(int $opportunityId): array
    {
        $cached = $this->cacheService->getCachedOpportunityStats($opportunityId);
        if ($cached && $this->isCacheValid($cached)) {
            return $cached;
        }

        // Use raw queries for better performance
        $stats = DB::select("
            SELECT 
                COUNT(*) as total_applications,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
            FROM volunteer_applications 
            WHERE opportunity_id = ?
        ", [$opportunityId]);

        $opportunity = VolunteeringOpportunity::select([
            'id', 'max_volunteers', 'current_volunteers', 'application_deadline'
        ])->find($opportunityId);

        if (!$opportunity) {
            return [];
        }

        $result = [
            'total_applications' => $stats[0]->total_applications ?? 0,
            'accepted_applications' => $stats[0]->accepted_applications ?? 0,
            'pending_applications' => $stats[0]->pending_applications ?? 0,
            'rejected_applications' => $stats[0]->rejected_applications ?? 0,
            'available_spots' => $opportunity->max_volunteers ? 
                ($opportunity->max_volunteers - $opportunity->current_volunteers) : null,
            'is_full' => $opportunity->max_volunteers ? 
                ($opportunity->current_volunteers >= $opportunity->max_volunteers) : false,
            'days_until_deadline' => $opportunity->application_deadline ? 
                now()->diffInDays($opportunity->application_deadline, false) : null,
            'cached_at' => now()
        ];

        $this->cacheService->cacheOpportunityStats($opportunityId);
        return $result;
    }

    /**
     * Preload and cache popular data
     */
    public function preloadPopularData(): void
    {
        // Cache popular opportunities
        $this->cacheService->cachePopularOpportunities();
        
        // Cache featured opportunities
        $this->cacheService->cacheFeaturedOpportunities();
        
        // Cache categories with counts
        $this->cacheService->cacheCategoriesWithCounts();
        
        // Cache recent opportunities
        $recentOpportunities = VolunteeringOpportunity::with(['organization', 'category', 'city'])
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subDays(7))
            ->limit(50)
            ->get();

        foreach ($recentOpportunities as $opportunity) {
            $this->cacheService->cacheOpportunity($opportunity);
        }
    }

    /**
     * Generate cache key for opportunity list
     */
    protected function generateCacheKey(array $filters, int $page, int $perPage): string
    {
        $keyData = array_merge($filters, ['page' => $page, 'per_page' => $perPage]);
        ksort($keyData);
        return 'opportunity_list:' . md5(serialize($keyData));
    }

    /**
     * Check if cached data is still valid
     */
    protected function isCacheValid(array $cached): bool
    {
        if (!isset($cached['cached_at'])) {
            return false;
        }

        $cacheAge = now()->diffInMinutes($cached['cached_at']);
        return $cacheAge < 15; // Cache valid for 15 minutes
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'cache_stats' => $this->cacheService->getCacheStats(),
            'database_stats' => $this->getDatabaseStats(),
            'query_performance' => $this->getQueryPerformanceStats(),
        ];
    }

    /**
     * Get database performance statistics
     */
    protected function getDatabaseStats(): array
    {
        $stats = [];

        try {
            // Get table sizes
            $tableSizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                AND table_name LIKE '%volunteer%'
                ORDER BY (data_length + index_length) DESC
            ");

            $stats['table_sizes'] = collect($tableSizes)->pluck('size_mb', 'table_name')->toArray();

            // Get index usage
            $indexStats = DB::select("
                SELECT 
                    table_name,
                    index_name,
                    cardinality
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE()
                AND table_name LIKE '%volunteer%'
                ORDER BY cardinality DESC
            ");

            $stats['index_usage'] = collect($indexStats)->groupBy('table_name')->toArray();

        } catch (\Exception $e) {
            $stats['error'] = 'Unable to fetch database stats: ' . $e->getMessage();
        }

        return $stats;
    }

    /**
     * Get query performance statistics
     */
    protected function getQueryPerformanceStats(): array
    {
        // This would typically integrate with query logging
        return [
            'slow_queries' => 0,
            'average_query_time' => 0,
            'total_queries' => 0,
        ];
    }
}