<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ForumPerformanceMiddleware
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
        
        // Process the request
        $response = $next($request);
        
        // Calculate performance metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        
        // Add performance headers to response
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        $response->headers->set('X-Query-Count', $queryCount);
        $response->headers->set('X-Query-Time', round($totalQueryTime, 2) . 'ms');
        
        // Log slow requests
        if ($executionTime > 1000) { // Log requests taking more than 1 second
            Log::warning('Slow forum request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime . 'ms',
                'memory_usage' => $this->formatBytes($memoryUsage),
                'query_count' => $queryCount,
                'query_time' => $totalQueryTime . 'ms',
                'user_id' => auth()->id(),
            ]);
        }
        
        // Log excessive queries
        if ($queryCount > 20) {
            Log::warning('Excessive database queries detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $queryCount,
                'queries' => array_map(function ($query) {
                    return [
                        'sql' => $query['query'],
                        'time' => $query['time'] . 'ms',
                        'bindings' => $query['bindings'],
                    ];
                }, $queries),
                'user_id' => auth()->id(),
            ]);
        }
        
        // Store performance metrics for analytics
        $this->storePerformanceMetrics($request, [
            'execution_time' => $executionTime,
            'memory_usage' => $memoryUsage,
            'query_count' => $queryCount,
            'query_time' => $totalQueryTime,
        ]);
        
        return $response;
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Store performance metrics for analytics
     */
    private function storePerformanceMetrics(Request $request, array $metrics): void
    {
        try {
            // Only store metrics for forum-related routes
            if (!$this->isForumRoute($request)) {
                return;
            }
            
            // Store in cache for later processing
            $cacheKey = 'forum_performance_' . date('Y-m-d-H');
            $existingMetrics = cache($cacheKey, []);
            
            $existingMetrics[] = [
                'timestamp' => now()->toISOString(),
                'route' => $request->route()?->getName(),
                'url' => $request->path(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'execution_time' => $metrics['execution_time'],
                'memory_usage' => $metrics['memory_usage'],
                'query_count' => $metrics['query_count'],
                'query_time' => $metrics['query_time'],
                'response_size' => strlen(response()->getContent()),
            ];
            
            // Keep only last 1000 entries per hour
            if (count($existingMetrics) > 1000) {
                $existingMetrics = array_slice($existingMetrics, -1000);
            }
            
            cache([$cacheKey => $existingMetrics], now()->addHours(24));
            
        } catch (\Exception $e) {
            // Don't let performance monitoring break the application
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);
        }
    }
    
    /**
     * Check if the request is for a forum route
     */
    private function isForumRoute(Request $request): bool
    {
        $forumRoutes = [
            'forums',
            'forum',
            'threads',
            'thread',
            'posts',
            'post',
        ];
        
        $path = $request->path();
        
        foreach ($forumRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }
        
        return false;
    }
}