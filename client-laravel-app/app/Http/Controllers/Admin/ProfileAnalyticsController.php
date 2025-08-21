<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProfileAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class ProfileAnalyticsController extends Controller
{
    public function __construct(
        private ProfileAnalyticsService $analyticsService
    ) {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Gate::allows('admin-access')) {
                abort(403, 'Access denied. Admin privileges required.');
            }
            return $next($request);
        });
    }

    /**
     * Display the main analytics dashboard
     */
    public function index(): View
    {
        $dashboardData = $this->analyticsService->getAdminDashboardData();
        
        return view('admin.profile-analytics.index', [
            'dashboardData' => $dashboardData
        ]);
    }

    /**
     * Get user engagement analytics (AJAX)
     */
    public function userEngagement(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ? 
                Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? 
                Carbon::parse($request->input('end_date')) : null;

            $analytics = $this->analyticsService->getUserEngagementAnalytics($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user engagement analytics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get profile completion insights (AJAX)
     */
    public function profileCompletion(): JsonResponse
    {
        try {
            $insights = $this->analyticsService->getProfileCompletionInsights();

            return response()->json([
                'success' => true,
                'data' => $insights
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile completion insights',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user behavior analytics (AJAX)
     */
    public function userBehavior(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ? 
                Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? 
                Carbon::parse($request->input('end_date')) : null;

            $analytics = $this->analyticsService->getUserBehaviorAnalytics($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user behavior analytics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get demographic analytics (AJAX)
     */
    public function demographics(): JsonResponse
    {
        try {
            $analytics = $this->analyticsService->getDemographicAnalytics();

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load demographic analytics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get profile performance metrics (AJAX)
     */
    public function profilePerformance(): JsonResponse
    {
        try {
            $metrics = $this->analyticsService->getProfilePerformanceMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile performance metrics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|in:engagement,completion,behavior,demographics,performance',
                'format' => 'required|in:json,csv,excel',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            $type = $request->input('type');
            $format = $request->input('format');
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            // Get the appropriate data based on type
            $data = match ($type) {
                'engagement' => $this->analyticsService->getUserEngagementAnalytics($startDate, $endDate),
                'completion' => $this->analyticsService->getProfileCompletionInsights(),
                'behavior' => $this->analyticsService->getUserBehaviorAnalytics($startDate, $endDate),
                'demographics' => $this->analyticsService->getDemographicAnalytics(),
                'performance' => $this->analyticsService->getProfilePerformanceMetrics(),
            };

            // Generate filename
            $filename = "profile_analytics_{$type}_" . now()->format('Y-m-d_H-i-s');

            switch ($format) {
                case 'json':
                    return response()->json($data)
                        ->header('Content-Disposition', "attachment; filename={$filename}.json");
                
                case 'csv':
                    $csvData = $this->convertToCsv($data, $type);
                    return response($csvData)
                        ->header('Content-Type', 'text/csv')
                        ->header('Content-Disposition', "attachment; filename={$filename}.csv");
                
                case 'excel':
                    // For Excel export, you'd typically use a package like Laravel Excel
                    // For now, return CSV format
                    $csvData = $this->convertToCsv($data, $type);
                    return response($csvData)
                        ->header('Content-Type', 'application/vnd.ms-excel')
                        ->header('Content-Disposition', "attachment; filename={$filename}.xls");
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export analytics data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->analyticsService->clearAnalyticsCache();

            return response()->json([
                'success' => true,
                'message' => 'Analytics cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear analytics cache',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get real-time dashboard updates
     */
    public function realtimeUpdates(): JsonResponse
    {
        try {
            // Get recent activity and key metrics
            $dashboardData = $this->analyticsService->getAdminDashboardData();
            
            // Return only the data that changes frequently
            return response()->json([
                'success' => true,
                'data' => [
                    'key_metrics' => $dashboardData['key_metrics'],
                    'recent_activity' => array_slice($dashboardData['recent_activity'], 0, 10),
                    'quick_stats' => $dashboardData['quick_stats'],
                    'last_updated' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load real-time updates',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Convert analytics data to CSV format
     */
    private function convertToCsv(array $data, string $type): string
    {
        $csv = [];
        
        switch ($type) {
            case 'engagement':
                $csv[] = ['Metric', 'Value'];
                $csv[] = ['Period', $data['period']['start_date'] . ' to ' . $data['period']['end_date']];
                $csv[] = ['Total Users', $data['user_metrics']['total_users']];
                $csv[] = ['Active Users', $data['user_metrics']['active_users']];
                $csv[] = ['New Users', $data['user_metrics']['new_users']];
                $csv[] = ['Engagement Rate (%)', $data['user_metrics']['engagement_rate']];
                $csv[] = ['Profile Completion Rate (%)', $data['profile_metrics']['profile_completion_rate']];
                break;
                
            case 'completion':
                $csv[] = ['Field Category', 'Field Name', 'Completion Count', 'Completion Percentage'];
                foreach ($data['field_completion_rates'] as $category => $fields) {
                    foreach ($fields as $field => $stats) {
                        $csv[] = [$category, $field, $stats['count'], $stats['percentage']];
                    }
                }
                break;
                
            case 'demographics':
                $csv[] = ['Category', 'Item', 'Count'];
                foreach ($data['age_distribution'] as $range => $count) {
                    $csv[] = ['Age Distribution', $range, $count];
                }
                foreach ($data['gender_distribution'] as $gender => $count) {
                    $csv[] = ['Gender Distribution', $gender, $count];
                }
                break;
                
            case 'performance':
                $csv[] = ['Metric', 'Value'];
                $csv[] = ['Total Profile Views', $data['view_metrics']['total_profile_views']];
                $csv[] = ['Unique Profile Views', $data['view_metrics']['unique_profile_views']];
                $csv[] = ['Average Views per Profile', $data['view_metrics']['average_views_per_profile']];
                break;
        }
        
        // Convert array to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);
        
        return $csvString;
    }
}