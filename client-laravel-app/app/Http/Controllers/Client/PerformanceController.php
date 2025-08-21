<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use App\Services\QueryOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
     * Get basic performance metrics for client monitoring.
     */
    public function metrics()
    {
        // Only return basic metrics for client app
        $data = [
            'cache_status' => $this->getCacheStatus(),
            'database_status' => $this->getDatabaseStatus(),
            'system_status' => $this->getSystemStatus(),
        ];

        return response()->json($data);
    }

    /**
     * Get cache hit rate and basic stats.
     */
    public function cacheStats()
    {
        $stats = $this->cacheService->getStats();
        
        // Return only essential stats for client monitoring
        return response()->json([
            'driver' => $stats['driver'],
            'status' => $stats['status'] ?? 'active',
            'hit_rate' => $stats['hit_rate'] ?? 'N/A',
        ]);
    }

    /**
     * Warm up frequently accessed cache data.
     */
    public function warmUpCache()
    {
        try {
            // Only warm up client-specific cache
            $this->warmUpClientCache();

            return response()->json([
                'success' => true,
                'message' => 'Client cache warmed up successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to warm up cache'
            ], 500);
        }
    }

    /**
     * Get application health status.
     */
    public function health()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => [
                'database' => $this->isDatabaseConnected(),
                'cache' => $this->isCacheConnected(),
                'storage' => $this->isStorageWritable(),
            ]
        ];

        // Determine overall status
        $allHealthy = array_reduce($health['checks'], function ($carry, $check) {
            return $carry && $check;
        }, true);

        $health['status'] = $allHealthy ? 'healthy' : 'degraded';

        return response()->json($health);
    }

    /**
     * Get recent performance data for monitoring.
     */
    public function recentPerformance(Request $request)
    {
        $limit = min($request->input('limit', 10), 50); // Max 50 records
        $metrics = cache()->get('client_performance_metrics', []);
        
        // Return only essential data
        $essentialMetrics = array_map(function ($metric) {
            return [
                'url' => $metric['url'],
                'method' => $metric['method'],
                'status_code' => $metric['status_code'],
                'execution_time' => $metric['execution_time'],
                'timestamp' => $metric['timestamp'],
            ];
        }, array_slice($metrics, 0, $limit));

        return response()->json([
            'metrics' => $essentialMetrics,
            'total' => count($metrics)
        ]);
    }

    /**
     * Get cache status.
     */
    private function getCacheStatus(): array
    {
        try {
            $stats = $this->cacheService->getStats();
            return [
                'status' => 'connected',
                'driver' => $stats['driver'],
                'hit_rate' => $stats['hit_rate'] ?? 'N/A',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache connection failed',
            ];
        }
    }

    /**
     * Get database status.
     */
    private function getDatabaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'driver' => DB::connection()->getDriverName(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
        }
    }

    /**
     * Get system status.
     */
    private function getSystemStatus(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Warm up client-specific cache.
     */
    private function warmUpClientCache(): void
    {
        // Cache popular organizations
        $popularOrgs = \App\Models\Organization::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(10)
            ->get();
        $this->cacheService->put('popular_organizations', $popularOrgs, CacheService::LONG_TTL, ['organizations']);

        // Cache upcoming events
        $upcomingEvents = \App\Models\Event::where('start_date', '>', now())
            ->where('status', 'active')
            ->orderBy('start_date')
            ->limit(20)
            ->get();
        $this->cacheService->cacheUpcomingEvents($upcomingEvents);

        // Cache popular resources
        $popularResources = \App\Models\Resource::where('status', 'published')
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get();
        $this->cacheService->put('popular_resources', $popularResources, CacheService::LONG_TTL, ['resources']);
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
     * Check if cache is connected.
     */
    private function isCacheConnected(): bool
    {
        try {
            Cache::get('test');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if storage is writable.
     */
    private function isStorageWritable(): bool
    {
        try {
            $testFile = storage_path('logs/health_check.tmp');
            file_put_contents($testFile, 'test');
            unlink($testFile);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}