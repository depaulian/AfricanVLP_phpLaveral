<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use App\Services\QueryOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceController extends Controller
{
    private CacheService $cacheService;
    private QueryOptimizationService $queryOptimizationService;

    public function __construct(CacheService $cacheService, QueryOptimizationService $queryOptimizationService)
    {
        $this->cacheService = $cacheService;
        $this->queryOptimizationService = $queryOptimizationService;
    }

    /**
     * Display performance dashboard.
     */
    public function index()
    {
        $data = [
            'cache_stats' => $this->cacheService->getStats(),
            'database_stats' => $this->queryOptimizationService->getPerformanceStats(),
            'recent_requests' => $this->getRecentRequests(),
            'system_info' => $this->getSystemInfo(),
        ];

        return view('admin.performance.index', $data);
    }

    /**
     * Get cache statistics.
     */
    public function cacheStats()
    {
        return response()->json($this->cacheService->getStats());
    }

    /**
     * Get database performance statistics.
     */
    public function databaseStats()
    {
        return response()->json($this->queryOptimizationService->getPerformanceStats());
    }

    /**
     * Clear application cache.
     */
    public function clearCache(Request $request)
    {
        try {
            $type = $request->input('type', 'all');

            switch ($type) {
                case 'config':
                    \Artisan::call('config:clear');
                    $message = 'Configuration cache cleared';
                    break;
                case 'route':
                    \Artisan::call('route:clear');
                    $message = 'Route cache cleared';
                    break;
                case 'view':
                    \Artisan::call('view:clear');
                    $message = 'View cache cleared';
                    break;
                case 'application':
                    $this->cacheService->clearAll();
                    $message = 'Application cache cleared';
                    break;
                default:
                    \Artisan::call('cache:clear');
                    \Artisan::call('config:clear');
                    \Artisan::call('route:clear');
                    \Artisan::call('view:clear');
                    $this->cacheService->clearAll();
                    $message = 'All caches cleared';
            }

            Log::info('Cache cleared', ['type' => $type, 'user' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear cache', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Warm up application cache.
     */
    public function warmUpCache()
    {
        try {
            $this->cacheService->warmUp();

            Log::info('Cache warmed up', ['user' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Cache warmed up successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to warm up cache', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to warm up cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize database indexes.
     */
    public function optimizeDatabase()
    {
        try {
            $indexes = $this->queryOptimizationService->createOptimizationIndexes();

            Log::info('Database optimized', ['indexes' => $indexes, 'user' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Database optimized successfully',
                'indexes' => $indexes
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to optimize database', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent performance metrics.
     */
    public function recentMetrics(Request $request)
    {
        $limit = $request->input('limit', 50);
        $metrics = cache()->get('performance_metrics', []);
        
        return response()->json([
            'metrics' => array_slice($metrics, 0, $limit),
            'total' => count($metrics)
        ]);
    }

    /**
     * Get slow queries.
     */
    public function slowQueries(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        try {
            // This would typically come from a log analysis or monitoring system
            $slowQueries = $this->getSlowQueriesFromLogs($limit);
            
            return response()->json([
                'queries' => $slowQueries,
                'total' => count($slowQueries)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve slow queries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyze query performance.
     */
    public function analyzeQuery(Request $request)
    {
        $request->validate([
            'sql' => 'required|string',
        ]);

        try {
            // This is a simplified example - in practice you'd need to parse the SQL
            // and create a proper query builder instance
            $suggestions = [
                [
                    'type' => 'info',
                    'message' => 'Query analysis completed',
                    'impact' => 'low'
                ]
            ];

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze query: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent requests from cache.
     */
    private function getRecentRequests(): array
    {
        return cache()->get('performance_metrics', []);
    }

    /**
     * Get system information.
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status(),
            'redis_connected' => $this->isRedisConnected(),
            'database_connected' => $this->isDatabaseConnected(),
        ];
    }

    /**
     * Check if Redis is connected.
     */
    private function isRedisConnected(): bool
    {
        try {
            Cache::store('redis')->get('test');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if database is connected.
     */
    private function isDatabaseConnected(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get slow queries from logs (simplified implementation).
     */
    private function getSlowQueriesFromLogs(int $limit): array
    {
        // This is a simplified implementation
        // In practice, you'd parse log files or use a monitoring system
        return [
            [
                'sql' => 'SELECT * FROM users WHERE created_at > ?',
                'time' => 2.5,
                'bindings' => ['2024-01-01'],
                'timestamp' => now()->subMinutes(10)->toISOString(),
            ],
            [
                'sql' => 'SELECT * FROM organizations LEFT JOIN users ON...',
                'time' => 1.8,
                'bindings' => [],
                'timestamp' => now()->subMinutes(15)->toISOString(),
            ],
        ];
    }
}