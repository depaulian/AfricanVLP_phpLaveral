<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForumSearchService
{
    /**
     * Perform comprehensive forum search
     */
    public function search(User $user, string $query = '', array $filters = [], int $perPage = 15): array
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
        $forumResults = $this->searchForums($user, $query, $filters);
        
        // Search threads
        $threadResults = $this->searchThreads($user, $query, $filters, $perPage);
        
        // Search posts
        $postResults = $this->searchPosts($user, $query, $filters, $perPage);

        $results['forums'] = $forumResults;
        $results['threads'] = $threadResults;
        $results['posts'] = $postResults;
        $results['total_results'] = $forumResults->count() + $threadResults->count() + $postResults->count();

        return $results;
    }

    /**
     * Search forums
     */
    public function searchForums(User $user, string $query = '', array $filters = []): Collection
    {
        $queryBuilder = Forum::query()
            ->where('status', 'active')
            ->where(function ($q) use ($user) {
                $q->where('is_private', false)
                  ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                      $orgQuery->where('users.id', $user->id);
                  });
            });

        // Apply text search
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $queryBuilder->where('category', $filters['category']);
        }

        // Apply organization filter
        if (!empty($filters['organization_id'])) {
            $queryBuilder->where('organization_id', $filters['organization_id']);
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
     * Search threads
     */
    public function searchThreads(User $user, string $query = '', array $filters = [], int $perPage = 15): Collection
    {
        $queryBuilder = ForumThread::query()
            ->where('status', 'active')
            ->whereHas('forum', function ($q) use ($user) {
                $q->where('status', 'active')
                  ->where(function ($subQ) use ($user) {
                      $subQ->where('is_private', false)
                           ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                               $orgQuery->where('users.id', $user->id);
                           });
                  });
            });

        // Apply text search
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            });
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
     * Search posts
     */
    public function searchPosts(User $user, string $query = '', array $filters = [], int $perPage = 15): Collection
    {
        $queryBuilder = ForumPost::query()
            ->where('status', 'active')
            ->whereHas('thread.forum', function ($q) use ($user) {
                $q->where('status', 'active')
                  ->where(function ($subQ) use ($user) {
                      $subQ->where('is_private', false)
                           ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                               $orgQuery->where('users.id', $user->id);
                           });
                  });
            });

        // Apply text search
        if (!empty($query)) {
            $queryBuilder->where('content', 'LIKE', "%{$query}%");
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

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $queryBuilder->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $queryBuilder->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply solution filter
        if (!empty($filters['solutions_only'])) {
            $queryBuilder->where('is_solution', true);
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
                    // Order by relevance based on query match
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
     * Get search suggestions based on partial query
     */
    public function getSearchSuggestions(User $user, string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // Get forum suggestions
        $forumSuggestions = Forum::where('status', 'active')
            ->where(function ($q) use ($user) {
                $q->where('is_private', false)
                  ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                      $orgQuery->where('users.id', $user->id);
                  });
            })
            ->where('name', 'LIKE', "%{$query}%")
            ->limit($limit / 2)
            ->pluck('name')
            ->map(function ($name) {
                return ['type' => 'forum', 'text' => $name];
            });

        // Get thread suggestions
        $threadSuggestions = ForumThread::where('status', 'active')
            ->whereHas('forum', function ($q) use ($user) {
                $q->where('status', 'active')
                  ->where(function ($subQ) use ($user) {
                      $subQ->where('is_private', false)
                           ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                               $orgQuery->where('users.id', $user->id);
                           });
                  });
            })
            ->where('title', 'LIKE', "%{$query}%")
            ->limit($limit / 2)
            ->pluck('title')
            ->map(function ($title) {
                return ['type' => 'thread', 'text' => Str::limit($title, 50)];
            });

        return $forumSuggestions->merge($threadSuggestions)->take($limit)->toArray();
    }

    /**
     * Get popular search terms
     */
    public function getPopularSearchTerms(int $limit = 10): array
    {
        // This would typically be stored in a search_logs table
        // For now, return some common terms based on forum content
        return [
            'help', 'support', 'question', 'problem', 'issue', 
            'tutorial', 'guide', 'how to', 'announcement', 'event'
        ];
    }

    /**
     * Get advanced search filters
     */
    public function getSearchFilters(User $user): array
    {
        return [
            'categories' => $this->getAvailableCategories($user),
            'organizations' => $this->getAvailableOrganizations($user),
            'forums' => $this->getAvailableForums($user),
            'sort_options' => $this->getSortOptions(),
            'date_ranges' => $this->getDateRanges()
        ];
    }

    /**
     * Get available categories for user
     */
    private function getAvailableCategories(User $user): array
    {
        return Forum::where('status', 'active')
            ->where(function ($q) use ($user) {
                $q->where('is_private', false)
                  ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                      $orgQuery->where('users.id', $user->id);
                  });
            })
            ->whereNotNull('category')
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
     * Get available organizations for user
     */
    private function getAvailableOrganizations(User $user): array
    {
        return $user->organizations()
            ->whereHas('forums', function ($q) {
                $q->where('status', 'active');
            })
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
     * Get available forums for user
     */
    private function getAvailableForums(User $user): array
    {
        return Forum::where('status', 'active')
            ->where(function ($q) use ($user) {
                $q->where('is_private', false)
                  ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                      $orgQuery->where('users.id', $user->id);
                  });
            })
            ->get(['id', 'name', 'category'])
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
     * Highlight search terms in text
     */
    public function highlightSearchTerms(string $text, string $query): string
    {
        if (empty($query)) {
            return $text;
        }

        $terms = explode(' ', $query);
        $highlightedText = $text;

        foreach ($terms as $term) {
            if (strlen($term) >= 2) {
                $highlightedText = preg_replace(
                    '/(' . preg_quote($term, '/') . ')/i',
                    '<mark class="bg-yellow-200 px-1 rounded">$1</mark>',
                    $highlightedText
                );
            }
        }

        return $highlightedText;
    }

    /**
     * Get search result excerpt
     */
    public function getSearchExcerpt(string $content, string $query, int $length = 200): string
    {
        if (empty($query)) {
            return Str::limit(strip_tags($content), $length);
        }

        $terms = explode(' ', $query);
        $content = strip_tags($content);
        
        // Find the first occurrence of any search term
        $firstMatch = false;
        $matchPosition = 0;
        
        foreach ($terms as $term) {
            if (strlen($term) >= 2) {
                $position = stripos($content, $term);
                if ($position !== false && (!$firstMatch || $position < $matchPosition)) {
                    $firstMatch = true;
                    $matchPosition = $position;
                }
            }
        }

        if ($firstMatch) {
            // Extract text around the match
            $start = max(0, $matchPosition - $length / 2);
            $excerpt = substr($content, $start, $length);
            
            // Ensure we don't cut words in half
            if ($start > 0) {
                $excerpt = '...' . substr($excerpt, strpos($excerpt, ' ') + 1);
            }
            
            if (strlen($content) > $start + $length) {
                $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')) . '...';
            }
            
            return $excerpt;
        }

        return Str::limit($content, $length);
    }

    /**
     * Log search query for analytics
     */
    public function logSearch(User $user, string $query, array $filters, int $resultCount): void
    {
        // This would typically log to a search_logs table for analytics
        // For now, we'll just log to Laravel's log system
        logger()->info('Forum search performed', [
            'user_id' => $user->id,
            'query' => $query,
            'filters' => $filters,
            'result_count' => $resultCount,
            'timestamp' => now()
        ]);
    }

    /**
     * Get search analytics
     */
    public function getSearchAnalytics(array $filters = []): array
    {
        // This would typically query a search_logs table
        // For now, return mock data
        return [
            'total_searches' => 0,
            'unique_searchers' => 0,
            'popular_terms' => [],
            'search_trends' => [],
            'zero_result_queries' => []
        ];
    }
}