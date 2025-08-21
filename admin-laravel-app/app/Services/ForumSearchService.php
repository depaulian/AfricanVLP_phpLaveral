<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForumSearchService
{
    /**
     * Perform comprehensive forum search (admin version - no access restrictions)
     */
    public function search(string $query = '', array $filters = [], int $perPage = 15): array
    {
        $results = [
            'forums' => collect(),
            'threads' => collect(),
            'posts' => collect(),
            'query' => $query,
            'filters' => $filters,
            'total_results' => 0
        ];

        if (empty($query) && empty(array_filter($filters))) {
            return $results;
        }

        // Search forums
        $forumResults = $this->searchForums($query, $filters);
        
        // Search threads
        $threadResults = $this->searchThreads($query, $filters, $perPage);
        
        // Search posts
        $postResults = $this->searchPosts($query, $filters, $perPage);

        $results['forums'] = $forumResults;
        $results['threads'] = $threadResults;
        $results['posts'] = $postResults;
        $results['total_results'] = $forumResults->count() + $threadResults->count() + $postResults->count();

        return $results;
    }

    /**
     * Search forums (admin - all forums)
     */
    public function searchForums(string $query = '', array $filters = []): Collection
    {
        $queryBuilder = Forum::query();

        // Apply text search
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $queryBuilder->where('category', $filters['category']);
        }

        // Apply organization filter
        if (!empty($filters['organization_id'])) {
            $queryBuilder->where('organization_id', $filters['organization_id']);
        }

        // Apply privacy filter
        if (isset($filters['is_private'])) {
            $queryBuilder->where('is_private', $filters['is_private']);
        }

        return $queryBuilder
            ->with(['organization'])
            ->withCount(['threads', 'posts'])
            ->orderByRaw("CASE WHEN name LIKE ? THEN 1 ELSE 2 END", ["%{$query}%"])
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    /**
     * Search threads (admin - all threads)
     */
    public function searchThreads(string $query = '', array $filters = [], int $perPage = 15): Collection
    {
        $queryBuilder = ForumThread::query();

        // Apply text search
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $queryBuilder->whereHas('forum', function ($q) use ($filters) {
                $q->where('category', $filters['category']);
            });
        }

        // Apply organization filter
        if (!empty($filters['organization_id'])) {
            $queryBuilder->whereHas('forum', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        // Apply forum filter
        if (!empty($filters['forum_id'])) {
            $queryBuilder->where('forum_id', $filters['forum_id']);
        }

        // Apply author filter
        if (!empty($filters['author_id'])) {
            $queryBuilder->where('author_id', $filters['author_id']);
        }

        // Apply moderation filters
        if (isset($filters['is_pinned'])) {
            $queryBuilder->where('is_pinned', $filters['is_pinned']);
        }

        if (isset($filters['is_locked'])) {
            $queryBuilder->where('is_locked', $filters['is_locked']);
        }

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $queryBuilder->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $queryBuilder->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sort = $filters['sort'] ?? 'relevance';
        $this->applySorting($queryBuilder, $sort, $query);

        return $queryBuilder
            ->with(['author', 'forum', 'lastReplyBy'])
            ->withCount('posts')
            ->limit($perPage)
            ->get();
    }

    /**
     * Search posts (admin - all posts)
     */
    public function searchPosts(string $query = '', array $filters = [], int $perPage = 15): Collection
    {
        $queryBuilder = ForumPost::query();

        // Apply text search
        if (!empty($query)) {
            $queryBuilder->where('content', 'LIKE', "%{$query}%");
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $queryBuilder->whereHas('thread.forum', function ($q) use ($filters) {
                $q->where('category', $filters['category']);
            });
        }

        // Apply organization filter
        if (!empty($filters['organization_id'])) {
            $queryBuilder->whereHas('thread.forum', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        // Apply forum filter
        if (!empty($filters['forum_id'])) {
            $queryBuilder->whereHas('thread', function ($q) use ($filters) {
                $q->where('forum_id', $filters['forum_id']);
            });
        }

        // Apply thread filter
        if (!empty($filters['thread_id'])) {
            $queryBuilder->where('thread_id', $filters['thread_id']);
        }

        // Apply author filter
        if (!empty($filters['author_id'])) {
            $queryBuilder->where('author_id', $filters['author_id']);
        }

        // Apply solution filter
        if (isset($filters['is_solution'])) {
            $queryBuilder->where('is_solution', $filters['is_solution']);
        }

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $queryBuilder->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $queryBuilder->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sort = $filters['sort'] ?? 'relevance';
        $this->applySorting($queryBuilder, $sort, $query);

        return $queryBuilder
            ->with(['author', 'thread.forum'])
            ->limit($perPage)
            ->get();
    }

    /**
     * Apply sorting to query builder
     */
    private function applySorting($queryBuilder, string $sort, string $query = ''): void
    {
        switch ($sort) {
            case 'newest':
                $queryBuilder->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $queryBuilder->orderBy('created_at', 'asc');
                break;
            case 'most_replies':
                $queryBuilder->orderBy('reply_count', 'desc');
                break;
            case 'most_views':
                $queryBuilder->orderBy('view_count', 'desc');
                break;
            case 'most_votes':
                $queryBuilder->orderBy('upvotes', 'desc');
                break;
            case 'recent_activity':
                $queryBuilder->orderBy('last_reply_at', 'desc');
                break;
            case 'relevance':
            default:
                if (!empty($query)) {
                    $queryBuilder->orderByRaw("
                        CASE 
                            WHEN title LIKE ? THEN 1
                            WHEN content LIKE ? THEN 2
                            ELSE 3
                        END
                    ", ["%{$query}%", "%{$query}%"]);
                }
                $queryBuilder->orderBy('created_at', 'desc');
                break;
        }
    }

    /**
     * Get admin search filters
     */
    public function getAdminSearchFilters(): array
    {
        return [
            'categories' => $this->getAllCategories(),
            'organizations' => $this->getAllOrganizations(),
            'forums' => $this->getAllForums(),
            'statuses' => $this->getStatusOptions(),
            'sort_options' => $this->getSortOptions(),
            'date_ranges' => $this->getDateRanges()
        ];
    }

    /**
     * Get all categories
     */
    private function getAllCategories(): array
    {
        return Forum::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(function ($category) {
                return [
                    'value' => $category,
                    'label' => ucfirst(str_replace('_', ' ', $category))
                ];
            })
            ->toArray();
    }

    /**
     * Get all organizations
     */
    private function getAllOrganizations(): array
    {
        return \App\Models\Organization::whereHas('forums')
            ->get(['id', 'name'])
            ->map(function ($org) {
                return [
                    'value' => $org->id,
                    'label' => $org->name
                ];
            })
            ->toArray();
    }

    /**
     * Get all forums
     */
    private function getAllForums(): array
    {
        return Forum::get(['id', 'name', 'category'])
            ->map(function ($forum) {
                return [
                    'value' => $forum->id,
                    'label' => $forum->name,
                    'category' => $forum->category
                ];
            })
            ->toArray();
    }

    /**
     * Get status options
     */
    private function getStatusOptions(): array
    {
        return [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
            ['value' => 'pending', 'label' => 'Pending Moderation'],
            ['value' => 'rejected', 'label' => 'Rejected'],
            ['value' => 'deleted', 'label' => 'Deleted']
        ];
    }

    /**
     * Get sort options
     */
    private function getSortOptions(): array
    {
        return [
            ['value' => 'relevance', 'label' => 'Most Relevant'],
            ['value' => 'newest', 'label' => 'Newest First'],
            ['value' => 'oldest', 'label' => 'Oldest First'],
            ['value' => 'most_replies', 'label' => 'Most Replies'],
            ['value' => 'most_views', 'label' => 'Most Views'],
            ['value' => 'most_votes', 'label' => 'Most Votes'],
            ['value' => 'recent_activity', 'label' => 'Recent Activity']
        ];
    }

    /**
     * Get date range options
     */
    private function getDateRanges(): array
    {
        return [
            ['value' => 'today', 'label' => 'Today'],
            ['value' => 'week', 'label' => 'This Week'],
            ['value' => 'month', 'label' => 'This Month'],
            ['value' => 'quarter', 'label' => 'This Quarter'],
            ['value' => 'year', 'label' => 'This Year'],
            ['value' => 'custom', 'label' => 'Custom Range']
        ];
    }

    /**
     * Get content moderation search results
     */
    public function searchForModeration(string $query = '', array $filters = []): array
    {
        $results = [
            'reported_content' => $this->searchReportedContent($query, $filters),
            'flagged_threads' => $this->searchFlaggedThreads($query, $filters),
            'flagged_posts' => $this->searchFlaggedPosts($query, $filters),
            'user_violations' => $this->searchUserViolations($query, $filters)
        ];

        return $results;
    }

    /**
     * Search reported content
     */
    private function searchReportedContent(string $query, array $filters): Collection
    {
        $queryBuilder = \App\Models\ForumReport::with(['reportable', 'reporter', 'moderator']);

        if (!empty($query)) {
            $queryBuilder->where('reason', 'LIKE', "%{$query}%");
        }

        if (!empty($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $queryBuilder->where('severity', $filters['severity']);
        }

        return $queryBuilder->latest()->limit(20)->get();
    }

    /**
     * Search flagged threads
     */
    private function searchFlaggedThreads(string $query, array $filters): Collection
    {
        $queryBuilder = ForumThread::whereHas('reports', function ($q) use ($filters) {
            if (!empty($filters['status'])) {
                $q->where('status', $filters['status']);
            }
        });

        if (!empty($query)) {
            $queryBuilder->where('title', 'LIKE', "%{$query}%");
        }

        return $queryBuilder->with(['author', 'forum', 'reports'])
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * Search flagged posts
     */
    private function searchFlaggedPosts(string $query, array $filters): Collection
    {
        $queryBuilder = ForumPost::whereHas('reports', function ($q) use ($filters) {
            if (!empty($filters['status'])) {
                $q->where('status', $filters['status']);
            }
        });

        if (!empty($query)) {
            $queryBuilder->where('content', 'LIKE', "%{$query}%");
        }

        return $queryBuilder->with(['author', 'thread.forum', 'reports'])
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * Search user violations
     */
    private function searchUserViolations(string $query, array $filters): Collection
    {
        $queryBuilder = \App\Models\ForumWarning::with(['user', 'moderator']);

        if (!empty($query)) {
            $queryBuilder->whereHas('user', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            });
        }

        if (!empty($filters['severity'])) {
            $queryBuilder->where('severity', $filters['severity']);
        }

        if (isset($filters['is_active'])) {
            $queryBuilder->where('is_active', $filters['is_active']);
        }

        return $queryBuilder->latest()->limit(20)->get();
    }

    /**
     * Get search statistics for admin dashboard
     */
    public function getSearchStatistics(): array
    {
        return [
            'total_searches_today' => 0, // Would come from search logs
            'popular_search_terms' => $this->getPopularSearchTerms(),
            'zero_result_searches' => 0,
            'search_conversion_rate' => 0,
            'most_searched_categories' => $this->getMostSearchedCategories(),
            'search_trends' => $this->getSearchTrends()
        ];
    }

    /**
     * Get popular search terms
     */
    private function getPopularSearchTerms(): array
    {
        // This would typically come from search logs
        return [
            'help', 'support', 'question', 'problem', 'issue',
            'tutorial', 'guide', 'how to', 'announcement', 'event'
        ];
    }

    /**
     * Get most searched categories
     */
    private function getMostSearchedCategories(): array
    {
        // This would typically come from search logs
        return [
            ['category' => 'support', 'count' => 150],
            ['category' => 'general', 'count' => 120],
            ['category' => 'announcements', 'count' => 80],
            ['category' => 'feedback', 'count' => 60],
            ['category' => 'events', 'count' => 45]
        ];
    }

    /**
     * Get search trends
     */
    private function getSearchTrends(): array
    {
        // This would typically come from search logs
        return [
            'daily_searches' => [],
            'trending_up' => ['support', 'events'],
            'trending_down' => ['feedback']
        ];
    }
}