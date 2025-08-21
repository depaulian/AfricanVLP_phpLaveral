<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    private float $startTime;
    private int $startMemory;
    private int $queryCount;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start performance monitoring
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->queryCount = count(DB::getQueryLog());

        // Enable query logging for this request
        DB::enableQueryLog();

        $response = $next($request);

        // Calculate performance metrics
        $executionTime = microtime(true) - $this->startTime;
        $memoryUsage = memory_get_usage(true) - $this->startMemory;
        $peakMemory = memory_get_peak_usage(true);
        $queryCount = count(DB::getQueryLog()) - $this->queryCount;

        // Log performance metrics
        $this->logPerformanceMetrics($request, $response, [
            'execution_time' => $executionTime,
            'memory_usage' => $memoryUsage,
            'peak_memory' => $peakMemory,
            'query_count' => $queryCount,
        ]);

        // Add performance headers in debug mode
        if (config('app.debug')) {
            $response->headers->set('X-Execution-Time', number_format($executionTime * 1000, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
            $response->headers->set('X-Peak-Memory', $this->formatBytes($peakMemory));
            $response->headers->set('X-Query-Count', $queryCount);
        }

        // Alert on slow requests
        if ($executionTime > 2.0) { // Requests taking more than 2 seconds
            $this->alertSlowRequest($request, $executionTime, $queryCount);
        }

        // Alert on high memory usage
        if ($peakMemory > 128 * 1024 * 1024) { // More than 128MB
            $this->alertHighMemoryUsage($request, $peakMemory);
        }

        return $response;
    }

    /**
     * Log performance metrics.
     */
    private function logPerformanceMetrics(Request $request, Response $response, array $metrics): void
    {
        $logData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'execution_time' => $metrics['execution_time'],
            'memory_usage' => $this->formatBytes($metrics['memory_usage']),
            'peak_memory' => $this->formatBytes($metrics['peak_memory']),
            'query_count' => $metrics['query_count'],
            'timestamp' => now()->toISOString(),
        ];

        // Log to performance channel
        Log::channel('performance')->info('Request performance', $logData);

        // Store in cache for dashboard (keep last 100 requests)
        $this->storePerformanceData($logData);
    }

    /**
     * Alert on slow requests.
     */
    private function alertSlowRequest(Request $request, float $executionTime, int $queryCount): void
    {
        Log::warning('Slow request detected', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time' => $executionTime,
            'query_count' => $queryCount,
            'user_id' => auth()->id(),
            'queries' => $this->getSlowQueries(),
        ]);
    }

    /**
     * Alert on high memory usage.
     */
    private function alertHighMemoryUsage(Request $request, int $peakMemory): void
    {
        Log::warning('High memory usage detected', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'peak_memory' => $this->formatBytes($peakMemory),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Store performance data for dashboard.
     */
    private function storePerformanceData(array $data): void
    {
        try {
            $cacheKey = 'performance_metrics';
            $existingData = cache()->get($cacheKey, []);
            
            // Add new data
            array_unshift($existingData, $data);
            
            // Keep only last 100 requests
            $existingData = array_slice($existingData, 0, 100);
            
            // Store for 1 hour
            cache()->put($cacheKey, $existingData, 3600);
        } catch (\Exception $e) {
            Log::error('Failed to store performance data', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get slow queries from the current request.
     */
    private function getSlowQueries(): array
    {
        $queries = DB::getQueryLog();
        $slowQueries = [];

        foreach ($queries as $query) {
            if ($query['time'] > 1000) { // Queries taking more than 1 second
                $slowQueries[] = [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $query['time'],
                ];
            }
        }

        return $slowQueries;
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