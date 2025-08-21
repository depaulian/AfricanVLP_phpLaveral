<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VolunteeringPerformanceMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start performance monitoring
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $queryCount = 0;

        // Enable query logging for this request
        DB::enableQueryLog();

        $response = $next($request);

        // Calculate performance metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = collect($queries)->sum('time');

        // Log performance metrics for volunteering routes
        if ($this->isVolunteeringRoute($request)) {
            $this->logPerformanceMetrics($request, [
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'query_count' => $queryCount,
                'total_query_time' => $totalQueryTime,
                'response_size' => strlen($response->getContent()),
                'status_code' => $response->getStatusCode()
            ]);

            // Add performance headers for debugging
            if (config('app.debug')) {
                $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
                $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
                $response->headers->set('X-Query-Count', $queryCount);
                $response->headers->set('X-Query-Time', round($totalQueryTime, 2) . 'ms');
            }

            // Check for performance issues
            $this->checkPerformanceThresholds($request, $executionTime, $queryCount, $totalQueryTime);
        }

        // Disable query logging
        DB::disableQueryLog();

        return $response;
    }

    /**
     * Check if this is a volunteering-related route
     */
    protected function isVolunteeringRoute(Request $request): bool
    {
        $path = $request->path();
        
        return str_contains($path, 'volunteering') || 
               str_contains($path, 'volunteer') ||
               str_contains($path, 'opportunities');
    }

    /**
     * Log performance metrics
     */
    protected function logPerformanceMetrics(Request $request, array $metrics): void
    {
        $logData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metrics' => $metrics,
            'timestamp' => now()->toISOString()
        ];

        // Log to performance channel
        Log::channel('performance')->info('Volunteering Performance Metrics', $logData);

        // Store aggregated metrics in cache for dashboard
        $this->updateAggregatedMetrics($request->path(), $metrics);
    }

    /**
     * Update aggregated performance metrics
     */
    protected function updateAggregatedMetrics(string $route, array $metrics): void
    {
        $cacheKey = 'performance_metrics:' . md5($route);
        $ttl = 3600; // 1 hour

        $aggregated = Cache::get($cacheKey, [
            'route' => $route,
            'total_requests' => 0,
            'avg_execution_time' => 0,
            'avg_memory_usage' => 0,
            'avg_query_count' => 0,
            'avg_query_time' => 0,
            'max_execution_time' => 0,
            'max_memory_usage' => 0,
            'max_query_count' => 0,
            'slow_requests' => 0,
            'last_updated' => now()
        ]);

        $totalRequests = $aggregated['total_requests'] + 1;

        // Calculate new averages
        $aggregated['avg_execution_time'] = (
            ($aggregated['avg_execution_time'] * $aggregated['total_requests']) + $metrics['execution_time']
        ) / $totalRequests;

        $aggregated['avg_memory_usage'] = (
            ($aggregated['avg_memory_usage'] * $aggregated['total_requests']) + $metrics['memory_usage']
        ) / $totalRequests;

        $aggregated['avg_query_count'] = (
            ($aggregated['avg_query_count'] * $aggregated['total_requests']) + $metrics['query_count']
        ) / $totalRequests;

        $aggregated['avg_query_time'] = (
            ($aggregated['avg_query_time'] * $aggregated['total_requests']) + $metrics['total_query_time']
        ) / $totalRequests;

        // Update maximums
        $aggregated['max_execution_time'] = max($aggregated['max_execution_time'], $metrics['execution_time']);
        $aggregated['max_memory_usage'] = max($aggregated['max_memory_usage'], $metrics['memory_usage']);
        $aggregated['max_query_count'] = max($aggregated['max_query_count'], $metrics['query_count']);

        // Count slow requests (>1000ms)
        if ($metrics['execution_time'] > 1000) {
            $aggregated['slow_requests']++;
        }

        $aggregated['total_requests'] = $totalRequests;
        $aggregated['last_updated'] = now();

        Cache::put($cacheKey, $aggregated, $ttl);
    }

    /**
     * Check performance thresholds and alert if necessary
     */
    protected function checkPerformanceThresholds(Request $request, float $executionTime, int $queryCount, float $queryTime): void
    {
        $thresholds = [
            'execution_time' => 2000, // 2 seconds
            'query_count' => 50,
            'query_time' => 1000, // 1 second total query time
        ];

        $issues = [];

        if ($executionTime > $thresholds['execution_time']) {
            $issues[] = "Slow execution time: {$executionTime}ms";
        }

        if ($queryCount > $thresholds['query_count']) {
            $issues[] = "High query count: {$queryCount} queries";
        }

        if ($queryTime > $thresholds['query_time']) {
            $issues[] = "Slow query time: {$queryTime}ms";
        }

        if (!empty($issues)) {
            Log::warning('Volunteering Performance Issues Detected', [
                'url' => $request->fullUrl(),
                'issues' => $issues,
                'execution_time' => $executionTime,
                'query_count' => $queryCount,
                'query_time' => $queryTime,
                'user_id' => auth()->id()
            ]);

            // Increment performance issue counter
            $this->incrementPerformanceIssueCounter($request->path());
        }
    }

    /**
     * Increment performance issue counter
     */
    protected function incrementPerformanceIssueCounter(string $route): void
    {
        $cacheKey = 'performance_issues:' . md5($route);
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, 3600);
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get performance summary for a route
     */
    public static function getPerformanceSummary(string $route): ?array
    {
        $cacheKey = 'performance_metrics:' . md5($route);
        return Cache::get($cacheKey);
    }

    /**
     * Get all performance metrics
     */
    public static function getAllPerformanceMetrics(): array
    {
        $keys = Cache::get('performance_metric_keys', []);
        $metrics = [];

        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data) {
                $metrics[] = $data;
            }
        }

        return $metrics;
    }

    /**
     * Clear performance metrics
     */
    public static function clearPerformanceMetrics(): void
    {
        $keys = Cache::get('performance_metric_keys', []);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget('performance_metric_keys');
    }
}