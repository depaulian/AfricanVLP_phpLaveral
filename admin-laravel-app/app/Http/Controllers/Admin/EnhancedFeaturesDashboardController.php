<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EnhancedFeaturesService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnhancedFeaturesDashboardController extends Controller
{
    protected $enhancedFeaturesService;
    protected $activityLogService;

    public function __construct(EnhancedFeaturesService $enhancedFeaturesService, ActivityLogService $activityLogService)
    {
        $this->enhancedFeaturesService = $enhancedFeaturesService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display the enhanced features dashboard
     */
    public function index(Request $request)
    {
        try {
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            $verification = $this->enhancedFeaturesService->verifyEnhancedFeatures();
            
            // Log dashboard access
            $this->activityLogService->log('enhanced_features_dashboard_viewed', null, [
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'analytics' => $analytics,
                        'verification' => $verification,
                    ]
                ]);
            }

            return view('admin.enhanced-features.dashboard', compact('analytics', 'verification'));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading dashboard: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error loading dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Get system analytics data
     */
    public function analytics(Request $request)
    {
        try {
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get feature verification status
     */
    public function verification(Request $request)
    {
        try {
            $verification = $this->enhancedFeaturesService->verifyEnhancedFeatures();
            
            return response()->json([
                'success' => true,
                'data' => $verification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading verification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content statistics
     */
    public function contentStats(Request $request)
    {
        try {
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            
            return response()->json([
                'success' => true,
                'data' => $analytics['content_stats']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading content statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user engagement metrics
     */
    public function userEngagement(Request $request)
    {
        try {
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            
            return response()->json([
                'success' => true,
                'data' => $analytics['user_engagement']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading user engagement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system health metrics
     */
    public function systemHealth(Request $request)
    {
        try {
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            
            return response()->json([
                'success' => true,
                'data' => $analytics['system_health']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading system health: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get feature usage statistics
     */
    public function featureUsage(Request $request)
    {
        try {
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            
            return response()->json([
                'success' => true,
                'data' => $analytics['feature_usage']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading feature usage: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function performance(Request $request)
    {
        try {
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            
            return response()->json([
                'success' => true,
                'data' => $analytics['performance_metrics']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading performance metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            $analytics = $this->enhancedFeaturesService->getSystemAnalytics();
            
            // Log export action
            $this->activityLogService->log('enhanced_features_analytics_export', null, [
                'format' => $format,
                'user_id' => auth()->id(),
                'exported_at' => now(),
            ]);

            if ($format === 'json') {
                return response()->json($analytics)
                    ->header('Content-Disposition', 'attachment; filename="system_analytics_' . now()->format('Y-m-d_H-i-s') . '.json"');
            }

            // Default to CSV export
            $csvData = $this->convertAnalyticsToCSV($analytics);
            
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="system_analytics_' . now()->format('Y-m-d_H-i-s') . '.csv"');
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export feature verification report
     */
    public function exportVerification(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            $verification = $this->enhancedFeaturesService->verifyEnhancedFeatures();
            
            // Log export action
            $this->activityLogService->log('enhanced_features_verification_export', null, [
                'format' => $format,
                'user_id' => auth()->id(),
                'exported_at' => now(),
            ]);

            if ($format === 'json') {
                return response()->json($verification)
                    ->header('Content-Disposition', 'attachment; filename="feature_verification_' . now()->format('Y-m-d_H-i-s') . '.json"');
            }

            // Default to CSV export
            $csvData = $this->convertVerificationToCSV($verification);
            
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="feature_verification_' . now()->format('Y-m-d_H-i-s') . '.csv"');
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting verification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(Request $request)
    {
        try {
            Cache::forget('system_analytics');
            Cache::tags(['analytics', 'features'])->flush();
            
            // Log cache clear action
            $this->activityLogService->log('enhanced_features_cache_cleared', null, [
                'user_id' => auth()->id(),
                'cleared_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Analytics cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time system status
     */
    public function systemStatus(Request $request)
    {
        try {
            $status = [
                'timestamp' => now()->toISOString(),
                'system_online' => true,
                'database_connected' => $this->checkDatabaseConnection(),
                'cache_working' => $this->checkCacheConnection(),
                'storage_accessible' => $this->checkStorageAccess(),
                'queue_processing' => $this->checkQueueStatus(),
            ];

            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking system status: ' . $e->getMessage(),
                'data' => [
                    'timestamp' => now()->toISOString(),
                    'system_online' => false,
                ]
            ], 500);
        }
    }

    /**
     * Convert analytics data to CSV format
     */
    private function convertAnalyticsToCSV($analytics)
    {
        $csv = "Category,Metric,Value\n";
        
        foreach ($analytics as $category => $data) {
            if (is_array($data)) {
                foreach ($data as $subcategory => $subdata) {
                    if (is_array($subdata)) {
                        foreach ($subdata as $metric => $value) {
                            $csv .= "{$category}_{$subcategory},{$metric}," . (is_numeric($value) ? $value : '"' . str_replace('"', '""', $value) . '"') . "\n";
                        }
                    } else {
                        $csv .= "{$category},{$subcategory}," . (is_numeric($subdata) ? $subdata : '"' . str_replace('"', '""', $subdata) . '"') . "\n";
                    }
                }
            } else {
                $csv .= "{$category},value," . (is_numeric($data) ? $data : '"' . str_replace('"', '""', $data) . '"') . "\n";
            }
        }
        
        return $csv;
    }

    /**
     * Convert verification data to CSV format
     */
    private function convertVerificationToCSV($verification)
    {
        $csv = "Feature,Component,Status,Details\n";
        
        foreach ($verification['features'] as $feature => $data) {
            if (isset($data['status'])) {
                $status = $data['status'];
                unset($data['status']);
                
                foreach ($data as $component => $value) {
                    $details = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
                    $csv .= "{$feature},{$component},{$status}," . (is_numeric($details) ? $details : '"' . str_replace('"', '""', $details) . '"') . "\n";
                }
            }
        }
        
        return $csv;
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection()
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check cache connection
     */
    private function checkCacheConnection()
    {
        try {
            Cache::put('system_check', 'ok', 10);
            return Cache::get('system_check') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check storage access
     */
    private function checkStorageAccess()
    {
        try {
            \Storage::disk('public')->put('system_check.txt', 'ok');
            $result = \Storage::disk('public')->get('system_check.txt') === 'ok';
            \Storage::disk('public')->delete('system_check.txt');
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check queue status
     */
    private function checkQueueStatus()
    {
        try {
            // This would require queue monitoring implementation
            return true; // Placeholder
        } catch (\Exception $e) {
            return false;
        }
    }
}
