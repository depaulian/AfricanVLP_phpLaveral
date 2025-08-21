<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ForumAnalyticsService;
use App\Models\ForumAnalytic;
use App\Models\ForumMetric;
use App\Models\Forum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;

class ForumAnalyticsController extends Controller
{
    protected ForumAnalyticsService $analyticsService;

    public function __construct(ForumAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display analytics dashboard
     */
    public function index(Request $request): View
    {
        $days = $request->get('days', 30);
        
        $overviewStats = $this->analyticsService->getOverviewStats($days);
        $userEngagement = $this->analyticsService->getUserEngagementMetrics($days);
        $contentPerformance = $this->analyticsService->getContentPerformanceMetrics($days);
        $healthDashboard = $this->analyticsService->getForumHealthDashboard();

        return view('admin.forums.analytics.index', compact(
            'overviewStats',
            'userEngagement', 
            'contentPerformance',
            'healthDashboard',
            'days'
        ));
    }

    /**
     * Get overview statistics (AJAX)
     */
    public function getOverviewStats(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $stats = $this->analyticsService->getOverviewStats($days);
        
        return response()->json($stats);
    }

    /**
     * Get user engagement metrics (AJAX)
     */
    public function getUserEngagement(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $metrics = $this->analyticsService->getUserEngagementMetrics($days);
        
        return response()->json($metrics);
    }

    /**
     * Get content performance metrics (AJAX)
     */
    public function getContentPerformance(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $metrics = $this->analyticsService->getContentPerformanceMetrics($days);
        
        return response()->json($metrics);
    }

    /**
     * Get activity trends (AJAX)
     */
    public function getActivityTrends(Request $request): JsonResponse
    {
        $request->validate([
            'metric_type' => 'required|string',
            'days' => 'integer|min:1|max:365',
        ]);

        $metricType = $request->get('metric_type');
        $days = $request->get('days', 30);
        
        $trends = $this->analyticsService->getActivityTrends($metricType, $days);
        
        return response()->json([
            'metric_type' => $metricType,
            'period' => $days,
            'data' => $trends,
        ]);
    }

    /**
     * Get hourly activity pattern (AJAX)
     */
    public function getHourlyPattern(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => 'required|string',
            'days' => 'integer|min:1|max:30',
        ]);

        $eventType = $request->get('event_type');
        $days = $request->get('days', 7);
        
        $pattern = $this->analyticsService->getHourlyActivityPattern($eventType, $days);
        
        return response()->json([
            'event_type' => $eventType,
            'period' => $days,
            'data' => $pattern,
        ]);
    }

    /**
     * Get forum health dashboard (AJAX)
     */
    public function getHealthDashboard(Request $request): JsonResponse
    {
        $dashboard = $this->analyticsService->getForumHealthDashboard();
        
        return response()->json($dashboard);
    }

    /**
     * Display user analytics
     */
    public function userAnalytics(Request $request): View
    {
        $days = $request->get('days', 30);
        $userId = $request->get('user_id');
        
        $userEngagement = $this->analyticsService->getUserEngagementMetrics($days);
        
        $selectedUser = null;
        $userActivity = null;
        
        if ($userId) {
            $selectedUser = User::find($userId);
            if ($selectedUser) {
                $userActivity = ForumAnalytic::getUserActivitySummary($selectedUser, $days);
            }
        }

        return view('admin.forums.analytics.users', compact(
            'userEngagement',
            'selectedUser',
            'userActivity',
            'days'
        ));
    }

    /**
     * Display content analytics
     */
    public function contentAnalytics(Request $request): View
    {
        $days = $request->get('days', 30);
        $forumId = $request->get('forum_id');
        
        $contentPerformance = $this->analyticsService->getContentPerformanceMetrics($days);
        
        $selectedForum = null;
        $forumMetrics = null;
        
        if ($forumId) {
            $selectedForum = Forum::find($forumId);
            if ($selectedForum) {
                // Get forum-specific metrics
                $forumMetrics = [
                    'views' => ForumMetric::getTotal('forum_views', now()->subDays($days), now(), $selectedForum),
                    'threads' => ForumMetric::getTotal('threads_created', now()->subDays($days), now(), $selectedForum),
                    'posts' => ForumMetric::getTotal('posts_created', now()->subDays($days), now(), $selectedForum),
                    'engagement_score' => ForumMetric::calculateEngagementScore($selectedForum),
                ];
            }
        }

        return view('admin.forums.analytics.content', compact(
            'contentPerformance',
            'selectedForum',
            'forumMetrics',
            'days'
        ));
    }

    /**
     * Display moderation analytics
     */
    public function moderationAnalytics(Request $request): View
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);
        
        $moderationStats = [
            'reports_submitted' => ForumMetric::getTotal('reports_submitted', $startDate, now()),
            'moderation_actions' => ForumMetric::getTotal('moderation_actions', $startDate, now()),
            'warnings_issued' => \App\Models\ForumWarning::where('created_at', '>=', $startDate)->count(),
            'suspensions_issued' => \App\Models\ForumSuspension::where('created_at', '>=', $startDate)->count(),
            'bans_issued' => \App\Models\ForumBan::where('created_at', '>=', $startDate)->count(),
            'response_time' => $this->calculateModerationResponseTime($days),
        ];

        $moderationTrends = [
            'reports' => $this->analyticsService->getActivityTrends('reports_submitted', $days),
            'actions' => $this->analyticsService->getActivityTrends('moderation_actions', $days),
        ];

        return view('admin.forums.analytics.moderation', compact(
            'moderationStats',
            'moderationTrends',
            'days'
        ));
    }

    /**
     * Generate and download analytics report
     */
    public function generateReport(Request $request): Response
    {
        $request->validate([
            'format' => 'required|in:json,csv',
            'days' => 'integer|min:1|max:365',
            'include_charts' => 'boolean',
        ]);

        $format = $request->get('format');
        $days = $request->get('days', 30);
        $includeCharts = $request->get('include_charts', false);

        $options = [
            'days' => $days,
            'include_charts' => $includeCharts,
            'format' => $format,
        ];

        $reportData = $this->analyticsService->exportData($format, $options);
        
        $filename = "forum_analytics_report_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        
        $headers = [
            'Content-Type' => $format === 'json' ? 'application/json' : 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response($reportData, 200, $headers);
    }

    /**
     * Get real-time analytics data (AJAX)
     */
    public function getRealTimeData(Request $request): JsonResponse
    {
        $data = [
            'timestamp' => now()->toISOString(),
            'active_users_now' => $this->getActiveUsersNow(),
            'recent_activity' => $this->getRecentActivity(),
            'current_metrics' => [
                'threads_today' => ForumMetric::getTotal('threads_created', now()->toDateString(), now()->toDateString()),
                'posts_today' => ForumMetric::getTotal('posts_created', now()->toDateString(), now()->toDateString()),
                'votes_today' => ForumMetric::getTotal('votes_cast', now()->toDateString(), now()->toDateString()),
            ],
        ];

        return response()->json($data);
    }

    /**
     * Get analytics comparison between periods
     */
    public function getComparison(Request $request): JsonResponse
    {
        $request->validate([
            'metric_type' => 'required|string',
            'current_start' => 'required|date',
            'current_end' => 'required|date',
            'previous_start' => 'required|date',
            'previous_end' => 'required|date',
        ]);

        $metricType = $request->get('metric_type');
        $currentStart = $request->get('current_start');
        $currentEnd = $request->get('current_end');
        $previousStart = $request->get('previous_start');
        $previousEnd = $request->get('previous_end');

        $growthRate = ForumMetric::getGrowthRate(
            $metricType,
            $currentStart,
            $currentEnd,
            $previousStart,
            $previousEnd
        );

        $currentTotal = ForumMetric::getTotal($metricType, $currentStart, $currentEnd);
        $previousTotal = ForumMetric::getTotal($metricType, $previousStart, $previousEnd);

        return response()->json([
            'metric_type' => $metricType,
            'current_period' => [
                'start' => $currentStart,
                'end' => $currentEnd,
                'total' => $currentTotal,
            ],
            'previous_period' => [
                'start' => $previousStart,
                'end' => $previousEnd,
                'total' => $previousTotal,
            ],
            'growth_rate' => $growthRate,
            'trend' => $growthRate > 0 ? 'up' : ($growthRate < 0 ? 'down' : 'stable'),
        ]);
    }

    /**
     * Get top performers for a metric
     */
    public function getTopPerformers(Request $request): JsonResponse
    {
        $request->validate([
            'metric_type' => 'required|string',
            'entity_type' => 'required|string',
            'days' => 'integer|min:1|max:365',
            'limit' => 'integer|min:1|max:50',
        ]);

        $metricType = $request->get('metric_type');
        $entityType = $request->get('entity_type');
        $days = $request->get('days', 30);
        $limit = $request->get('limit', 10);

        $startDate = now()->subDays($days);
        $endDate = now();

        $performers = ForumMetric::getTopPerformers($metricType, $entityType, $startDate, $endDate, $limit);

        return response()->json([
            'metric_type' => $metricType,
            'entity_type' => $entityType,
            'period' => $days,
            'performers' => $performers,
        ]);
    }

    /**
     * Get custom analytics query results
     */
    public function customQuery(Request $request): JsonResponse
    {
        $request->validate([
            'query_type' => 'required|in:events,metrics',
            'filters' => 'array',
            'group_by' => 'string',
            'date_range' => 'array',
        ]);

        $queryType = $request->get('query_type');
        $filters = $request->get('filters', []);
        $groupBy = $request->get('group_by');
        $dateRange = $request->get('date_range');

        if ($queryType === 'events') {
            $query = ForumAnalytic::query();
        } else {
            $query = ForumMetric::query();
        }

        // Apply filters
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        // Apply date range
        if ($dateRange && isset($dateRange['start']) && isset($dateRange['end'])) {
            $dateField = $queryType === 'events' ? 'created_at' : 'date';
            $query->whereBetween($dateField, [$dateRange['start'], $dateRange['end']]);
        }

        // Apply grouping
        if ($groupBy) {
            $query->selectRaw("{$groupBy}, COUNT(*) as count")
                  ->groupBy($groupBy)
                  ->orderByDesc('count');
        }

        $results = $query->limit(100)->get();

        return response()->json([
            'query_type' => $queryType,
            'filters' => $filters,
            'group_by' => $groupBy,
            'results' => $results,
        ]);
    }

    /**
     * Get active users right now
     */
    protected function getActiveUsersNow(): int
    {
        // Users active in the last 5 minutes
        return ForumAnalytic::where('created_at', '>=', now()->subMinutes(5))
            ->distinct('user_id')
            ->whereNotNull('user_id')
            ->count();
    }

    /**
     * Get recent activity
     */
    protected function getRecentActivity(): array
    {
        return ForumAnalytic::with(['user:id,name', 'trackable'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'event_type' => $activity->event_type,
                    'user_name' => $activity->user?->name ?? 'Anonymous',
                    'trackable_type' => class_basename($activity->trackable_type ?? ''),
                    'created_at' => $activity->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate moderation response time
     */
    protected function calculateModerationResponseTime(int $days): float
    {
        $startDate = now()->subDays($days);
        
        // This would calculate the average time between report submission and moderation action
        // For now, return a placeholder value
        return 2.5; // hours
    }
}