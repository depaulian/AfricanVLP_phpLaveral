<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class QueryOptimizationService
{
    private CacheService $cacheService;
    private array $queryLog = [];
    private bool $enableQueryLogging = false;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->enableQueryLogging = config('app.debug', false);
    }

    /**
     * Execute optimized query with caching.
     */
    public function optimizedQuery(Builder $query, string $cacheKey, int $ttl = CacheService::MEDIUM_TTL, array $tags = [])
    {
        // Check cache first
        $cached = $this->cacheService->getCachedQuery($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Log query if debugging
        if ($this->enableQueryLogging) {
            $this->logQuery($query->toSql(), $query->getBindings());
        }

        // Execute query
        $startTime = microtime(true);
        $result = $query->get();
        $executionTime = microtime(true) - $startTime;

        // Log slow queries
        if ($executionTime > 1.0) { // Queries taking more than 1 second
            Log::warning('Slow query detected', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'execution_time' => $executionTime,
                'cache_key' => $cacheKey,
            ]);
        }

        // Cache the result
        $this->cacheService->cacheQuery($cacheKey, $result, $ttl, $tags);

        return $result;
    }

    /**
     * Optimize user queries with eager loading.
     */
    public function optimizedUserQuery(Builder $query, array $relations = []): Builder
    {
        // Default relations to eager load
        $defaultRelations = [
            'organizations' => function ($query) {
                $query->select(['organizations.id', 'organizations.name', 'organizations.logo'])
                      ->wherePivot('status', 'active');
            },
            'notifications' => function ($query) {
                $query->where('read_at', null)->limit(5);
            }
        ];

        $relations = array_merge($defaultRelations, $relations);

        return $query->with($relations)
                    ->select([
                        'id', 'name', 'email', 'avatar', 'status', 'bio',
                        'location', 'volunteering_interests', 'skills',
                        'created_at', 'updated_at', 'last_login_at'
                    ]);
    }

    /**
     * Optimize organization queries.
     */
    public function optimizedOrganizationQuery(Builder $query, array $relations = []): Builder
    {
        $defaultRelations = [
            'users' => function ($query) {
                $query->select(['users.id', 'users.name', 'users.avatar'])
                      ->wherePivot('status', 'active')
                      ->limit(10);
            },
            'upcomingEvents' => function ($query) {
                $query->select(['id', 'title', 'start_date', 'location'])
                      ->limit(3);
            }
        ];

        $relations = array_merge($defaultRelations, $relations);

        return $query->with($relations)
                    ->withCount(['users', 'events', 'resources'])
                    ->select([
                        'id', 'name', 'description', 'logo', 'category',
                        'location', 'contact_info', 'status', 'settings',
                        'created_at', 'updated_at'
                    ]);
    }

    /**
     * Optimize event queries.
     */
    public function optimizedEventQuery(Builder $query, array $relations = []): Builder
    {
        $defaultRelations = [
            'organization' => function ($query) {
                $query->select(['id', 'name', 'logo']);
            },
            'participants' => function ($query) {
                $query->select(['users.id', 'users.name', 'users.avatar'])
                      ->limit(10);
            }
        ];

        $relations = array_merge($defaultRelations, $relations);

        return $query->with($relations)
                    ->withCount('participants')
                    ->select([
                        'id', 'title', 'description', 'start_date', 'end_date',
                        'location', 'max_participants', 'organization_id',
                        'status', 'created_at', 'updated_at'
                    ]);
    }

    /**
     * Optimize resource queries.
     */
    public function optimizedResourceQuery(Builder $query, array $relations = []): Builder
    {
        $defaultRelations = [
            'category',
            'type',
            'files' => function ($query) {
                $query->select(['id', 'resource_id', 'filename', 'file_path', 'file_size']);
            },
            'createdBy' => function ($query) {
                $query->select(['id', 'name', 'avatar']);
            },
            'organization' => function ($query) {
                $query->select(['id', 'name', 'logo']);
            }
        ];

        $relations = array_merge($defaultRelations, $relations);

        return $query->with($relations)
                    ->select([
                        'id', 'title', 'description', 'content', 'status',
                        'category_id', 'type_id', 'organization_id', 'created_by',
                        'views', 'downloads', 'created_at', 'updated_at'
                    ]);
    }

    /**
     * Get paginated results with optimization.
     */
    public function optimizedPagination(Builder $query, int $perPage = 15, string $cacheKey = null)
    {
        // Add select if not already specified to avoid SELECT *
        if (empty($query->getQuery()->columns)) {
            $model = $query->getModel();
            $table = $model->getTable();
            $query->select(["{$table}.*"]);
        }

        // Use cursor pagination for better performance on large datasets
        if ($perPage > 50) {
            return $query->cursorPaginate($perPage);
        }

        // Cache count query for regular pagination
        if ($cacheKey) {
            $countCacheKey = $cacheKey . '_count';
            $total = $this->cacheService->remember(
                $countCacheKey,
                CacheService::SHORT_TTL,
                fn() => $query->count()
            );
        }

        return $query->paginate($perPage);
    }

    /**
     * Optimize search queries with full-text search.
     */
    public function optimizedSearch(string $model, string $query, array $searchFields, array $filters = [])
    {
        $modelClass = "App\\Models\\{$model}";
        $builder = $modelClass::query();

        // Use full-text search if available (MySQL)
        if (!empty($query) && $this->supportsFullTextSearch()) {
            $fullTextFields = $this->getFullTextFields($model);
            if (!empty($fullTextFields)) {
                $builder->whereRaw(
                    "MATCH(" . implode(',', $fullTextFields) . ") AGAINST(? IN BOOLEAN MODE)",
                    [$query . '*']
                );
            } else {
                $this->addLikeSearch($builder, $query, $searchFields);
            }
        } elseif (!empty($query)) {
            $this->addLikeSearch($builder, $query, $searchFields);
        }

        // Add filters
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (is_array($value)) {
                    $builder->whereIn($field, $value);
                } else {
                    $builder->where($field, $value);
                }
            }
        }

        return $builder;
    }

    /**
     * Optimize organization discovery queries.
     */
    public function optimizedOrganizationDiscovery(array $filters = [], string $sortBy = 'name'): Builder
    {
        $query = \App\Models\Organization::query()
            ->where('status', 'active')
            ->withCount(['users as member_count', 'events as event_count']);

        // Apply filters
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'LIKE', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        // Apply sorting
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('member_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'active':
                $query->orderBy('event_count', 'desc');
                break;
            default:
                $query->orderBy('name');
        }

        return $query;
    }

    /**
     * Optimize event discovery queries.
     */
    public function optimizedEventDiscovery(array $filters = [], string $sortBy = 'date'): Builder
    {
        $query = \App\Models\Event::query()
            ->where('status', 'active')
            ->where('start_date', '>', now())
            ->withCount('participants');

        // Apply filters
        if (!empty($filters['category'])) {
            $query->whereHas('organization', function ($q) use ($filters) {
                $q->where('category', $filters['category']);
            });
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'LIKE', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        // Apply sorting
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('participants_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'soonest':
                $query->orderBy('start_date');
                break;
            default:
                $query->orderBy('start_date');
        }

        return $query;
    }

    /**
     * Batch process large datasets.
     */
    public function batchProcess(Builder $query, callable $callback, int $batchSize = 1000): void
    {
        $query->chunk($batchSize, function ($items) use ($callback) {
            foreach ($items as $item) {
                $callback($item);
            }
        });
    }

    /**
     * Get database performance statistics.
     */
    public function getPerformanceStats(): array
    {
        try {
            $stats = [];

            // Get database connection info
            $connection = DB::connection();
            $stats['connection'] = [
                'driver' => $connection->getDriverName(),
                'database' => $connection->getDatabaseName(),
            ];

            // Get query log if enabled
            if ($this->enableQueryLogging) {
                $queryLog = DB::getQueryLog();
                $stats['queries'] = [
                    'total' => count($queryLog),
                    'slow_queries' => count(array_filter($queryLog, fn($q) => $q['time'] > 1000)),
                    'average_time' => count($queryLog) > 0 ? array_sum(array_column($queryLog, 'time')) / count($queryLog) : 0,
                ];
            }

            // Get table statistics for MySQL
            if ($connection->getDriverName() === 'mysql') {
                $stats['tables'] = $this->getMySQLTableStats();
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get performance stats', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Analyze and suggest query optimizations.
     */
    public function analyzeQuery(Builder $query): array
    {
        $suggestions = [];
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Check for SELECT *
        if (strpos($sql, 'SELECT *') !== false) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Avoid SELECT * - specify only needed columns',
                'impact' => 'medium'
            ];
        }

        // Check for missing WHERE clause
        if (strpos($sql, 'WHERE') === false && strpos($sql, 'LIMIT') === false) {
            $suggestions[] = [
                'type' => 'error',
                'message' => 'Query without WHERE clause may return too many results',
                'impact' => 'high'
            ];
        }

        // Check for N+1 queries (basic detection)
        if (strpos($sql, 'IN (') !== false && count($bindings) > 100) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Possible N+1 query detected - consider eager loading',
                'impact' => 'high'
            ];
        }

        // Check for ORDER BY without LIMIT
        if (strpos($sql, 'ORDER BY') !== false && strpos($sql, 'LIMIT') === false) {
            $suggestions[] = [
                'type' => 'info',
                'message' => 'ORDER BY without LIMIT may be inefficient for large datasets',
                'impact' => 'medium'
            ];
        }

        return $suggestions;
    }

    /**
     * Create database indexes for optimization.
     */
    public function createOptimizationIndexes(): array
    {
        $indexes = [];
        
        try {
            // User table indexes
            $this->createIndexIfNotExists('users', 'idx_users_email', ['email']);
            $this->createIndexIfNotExists('users', 'idx_users_status', ['status']);
            $this->createIndexIfNotExists('users', 'idx_users_created_at', ['created_at']);
            $indexes[] = 'Users table indexes created';

            // Organization table indexes
            $this->createIndexIfNotExists('organizations', 'idx_orgs_status', ['status']);
            $this->createIndexIfNotExists('organizations', 'idx_orgs_category', ['category']);
            $this->createIndexIfNotExists('organizations', 'idx_orgs_location', ['location']);
            $indexes[] = 'Organizations table indexes created';

            // Events table indexes
            $this->createIndexIfNotExists('events', 'idx_events_status', ['status']);
            $this->createIndexIfNotExists('events', 'idx_events_start_date', ['start_date']);
            $this->createIndexIfNotExists('events', 'idx_events_organization', ['organization_id']);
            $indexes[] = 'Events table indexes created';

            // Resources table indexes
            $this->createIndexIfNotExists('resources', 'idx_resources_status', ['status']);
            $this->createIndexIfNotExists('resources', 'idx_resources_category', ['category_id']);
            $this->createIndexIfNotExists('resources', 'idx_resources_created_by', ['created_by']);
            $indexes[] = 'Resources table indexes created';

            // Pivot table indexes
            $this->createIndexIfNotExists('organization_user', 'idx_org_user_org', ['organization_id']);
            $this->createIndexIfNotExists('organization_user', 'idx_org_user_user', ['user_id']);
            $this->createIndexIfNotExists('event_user', 'idx_event_user_event', ['event_id']);
            $this->createIndexIfNotExists('event_user', 'idx_event_user_user', ['user_id']);
            $indexes[] = 'Pivot table indexes created';

        } catch (\Exception $e) {
            Log::error('Failed to create optimization indexes', ['error' => $e->getMessage()]);
            $indexes[] = 'Error: ' . $e->getMessage();
        }

        return $indexes;
    }

    /**
     * Log query for analysis.
     */
    private function logQuery(string $sql, array $bindings): void
    {
        $this->queryLog[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'timestamp' => now(),
        ];
    }

    /**
     * Add LIKE search conditions.
     */
    private function addLikeSearch(Builder $builder, string $query, array $searchFields): void
    {
        $builder->where(function ($q) use ($query, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$query}%");
            }
        });
    }

    /**
     * Check if database supports full-text search.
     */
    private function supportsFullTextSearch(): bool
    {
        return config('database.default') === 'mysql';
    }

    /**
     * Get full-text search fields for model.
     */
    private function getFullTextFields(string $model): array
    {
        $fullTextFields = [
            'Organization' => ['name', 'description'],
            'Event' => ['title', 'description'],
            'Resource' => ['title', 'description', 'content'],
            'User' => ['name', 'bio'],
        ];

        return $fullTextFields[$model] ?? [];
    }

    /**
     * Get MySQL table statistics.
     */
    private function getMySQLTableStats(): array
    {
        try {
            $stats = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    data_length,
                    index_length,
                    (data_length + index_length) as total_size
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY total_size DESC
            ");

            return array_map(function ($stat) {
                return [
                    'table' => $stat->table_name,
                    'rows' => $stat->table_rows,
                    'data_size' => $this->formatBytes($stat->data_length),
                    'index_size' => $this->formatBytes($stat->index_length),
                    'total_size' => $this->formatBytes($stat->total_size),
                ];
            }, $stats);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Create index if it doesn't exist.
     */
    private function createIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        $connection = DB::connection();
        
        if ($connection->getDriverName() === 'mysql') {
            $exists = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = ? 
                AND index_name = ?
            ", [$table, $indexName]);

            if ($exists[0]->count == 0) {
                $columnList = implode(',', $columns);
                DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnList})");
            }
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}