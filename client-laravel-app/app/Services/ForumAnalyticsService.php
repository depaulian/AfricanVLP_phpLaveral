<?php

namespace App\Services;

use App\Models\ForumAnalytic;
use App\Models\ForumMetric;
use App\Models\User;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ForumAnalyticsService
{
    /**
     * Track a forum event
     */
    public function track(
        string $eventType,
        $trackable = null,
        ?User $user = null,
        array $metadata = []
    ): ForumAnalytic {
        return ForumAnalytic::track(
            $eventType,
            $trackable,
            $user,
            $metadata,
            request()->ip(),
            request()->userAgent(),
            session()->getId()
        );
    }

    /**
     * Get forum overview statistics
     */
    public function getOverviewStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        return Cache::remember("forum_overview_stats_{$days}", 300, function () use ($startDate, $endDate) {
            return [
                'total_forums' => Forum::count(),
                'total_threads' => ForumThread::count(),
                'total_posts' => ForumPost::count(),
                'active_users' => ForumAnalytic::where('created_at', '>=', $startDate)
                    ->distinct('user_id')
                    ->whereNotNull('user_id')
                    ->count(),
                'daily_active_users' => ForumMetric::getTotal('daily_active_users', $startDate, $endDate),
                'threads_created' => ForumMetric::getTotal('threads_created', $startDate, $endDate),
                'posts_created' => ForumMetric::getTotal('posts_created', $startDate, $endDate),
                'votes_cast' => ForumMetric::getTotal('votes_cast', $startDate, $endDate),
                'searches_performed' => ForumMetric::getTotal('searches_performed', $startDate, $endDate),
                'forum_views' => ForumMetric::getTotal('forum_views', $startDate, $endDate),
                'thread_views' => ForumMetric::getTotal('thread_views', $startDate, $endDate),
            ];
        });
    }

    /**
     * Get user engagement metrics
     */
    public function getUserEngagementMetrics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return Cache::remember("user_engagement_metrics_{$days}", 300, function () use ($startDate, $days) {
            $totalUsers = User::where('created_at', '>=', $startDate)->count();
            $activeUsers = ForumAnalytic::where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->whereNotNull('user_id')
                ->count();

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'engagement_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
                'avg_sessions_per_user' => $this->getAverageSessionsPerUser($days),
                'avg_posts_per_user' => $this->getAveragePostsPerUser($days),
                'top_contributors' => $this->getTopContributors($days),
                'user_retention' => $this->getUserRetentionRate($days),
            ];
        });
    }

    /**
     * Get content performance metrics
     */
    public function getContentPerformanceMetrics(int $days = 30): array
    {
        return Cache::remember("content_performance_metrics_{$days}", 300, function () use ($days) {
            return [
                'popular_forums' => $this->getPopularForums($days),
                'popular_threads' => $this->getPopularThreads($days),
                'trending_topics' => $this->getTrendingTopics($days),
                'content_quality_score' => $this->getContentQualityScore($days),
                'solution_rate' => $this->getSolutionRate($days),
                'response_time' => $this->getAverageResponseTime($days),
            ];
        });
    }

    /**
     * Get activity trends over time
     */
    public function getActivityTrends(string $metricType, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        return ForumMetric::getTrend($metricType, $startDate, $endDate);
    }

    /**
     * Get hourly activity pattern
     */
    public function getHourlyActivityPattern(string $eventType, int $days = 7): array
    {
        $pattern = [];
        for ($i = 0; $i < 24; $i++) {
            $pattern[$i] = 0;
        }

        $startDate = now()->subDays($days);
        $endDate = now();

        $hourlyData = ForumAnalytic::ofType($eventType)
            ->dateRange($startDate, $endDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        return array_merge($pattern, $hourlyData);
    }

    /**
     * Get forum health dashboard data
     */
    public function getForumHealthDashboard(): array
    {
        return Cache::remember('forum_health_dashboard', 300, function () {
            $today = now()->toDateString();
            $yesterday = now()->subDay()->toDateString();
            $lastWeek = now()->subWeek()->toDateString();

            return [
                'health_score' => $this->calculateForumHealthScore(),
                'daily_metrics' => [
                    'today' => $this->getDailyMetrics($today),
                    'yesterday' => $this->getDailyMetrics($yesterday),
                    'growth' => $this->calculateDailyGrowth($today, $yesterday),
                ],
                'weekly_trends' => $this->getWeeklyTrends(),
                'alerts' => $this->getHealthAlerts(),
                'recommendations' => $this->getHealthRecommendations(),
            ];
        });
    }

    /**
     * Generate analytics report
     */
    public function generateReport(array $options = []): array
    {
        $days = $options['days'] ?? 30;
        $includeCharts = $options['include_charts'] ?? true;
        $format = $options['format'] ?? 'array';

        $report = [
            'period' => [
                'start_date' => now()->subDays($days)->toDateString(),
                'end_date' => now()->toDateString(),
                'days' => $days,
            ],
            'overview' => $this->getOverviewStats($days),
            'user_engagement' => $this->getUserEngagementMetrics($days),
            'content_performance' => $this->getContentPerformanceMetrics($days),
            'moderation_stats' => $this->getModerationStats($days),
        ];

        if ($includeCharts) {
            $report['charts'] = [
                'daily_activity' => $this->getActivityTrends('daily_active_users', $days),
                'content_creation' => $this->getActivityTrends('posts_created', $days),
                'hourly_pattern' => $this->getHourlyActivityPattern('thread_view', 7),
            ];
        }

        return $report;
    }

    /**
     * Export analytics data
     */
    public function exportData(string $format, array $filters = []): string
    {
        $data = $this->generateReport($filters);

        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->convertToCSV($data);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Get popular forums
     */
    protected function getPopularForums(int $days = 30, int $limit = 10): array
    {
        $popular = ForumAnalytic::getPopularContent('forum_view', Forum::class, $limit, $days);
        
        return $popular->map(function ($item) {
            $forum = Forum::find($item->trackable_id);
            return [
                'id' => $item->trackable_id,
                'name' => $forum?->name ?? 'Unknown',
                'views' => $item->event_count,
            ];
        })->toArray();
    }

    /**
     * Get popular threads
     */
    protected function getPopularThreads(int $days = 30, int $limit = 10): array
    {
        $popular = ForumAnalytic::getPopularContent('thread_view', ForumThread::class, $limit, $days);
        
        return $popular->map(function ($item) {
            $thread = ForumThread::find($item->trackable_id);
            return [
                'id' => $item->trackable_id,
                'title' => $thread?->title ?? 'Unknown',
                'views' => $item->event_count,
                'forum' => $thread?->forum?->name ?? 'Unknown',
            ];
        })->toArray();
    }

    /**
     * Get trending topics
     */
    protected function getTrendingTopics(int $days = 7): array
    {
        // This would analyze thread titles and content for trending keywords
        // For now, return popular threads from the last week
        return $this->getPopularThreads($days, 5);
    }

    /**
     * Calculate content quality score
     */
    protected function getContentQualityScore(int $days = 30): float
    {
        $totalPosts = ForumPost::where('created_at', '>=', now()->subDays($days))->count();
        $postsWithSolutions = ForumPost::where('created_at', '>=', now()->subDays($days))
            ->where('is_solution', true)->count();
        $postsWithVotes = ForumPost::where('created_at', '>=', now()->subDays($days))
            ->whereHas('votes')->count();

        if ($totalPosts == 0) return 0;

        $solutionRate = ($postsWithSolutions / $totalPosts) * 100;
        $engagementRate = ($postsWithVotes / $totalPosts) * 100;

        return round(($solutionRate * 0.6) + ($engagementRate * 0.4), 2);
    }

    /**
     * Get solution rate
     */
    protected function getSolutionRate(int $days = 30): float
    {
        $totalThreads = ForumThread::where('created_at', '>=', now()->subDays($days))->count();
        $solvedThreads = ForumThread::where('created_at', '>=', now()->subDays($days))
            ->whereHas('posts', function ($query) {
                $query->where('is_solution', true);
            })->count();

        return $totalThreads > 0 ? round(($solvedThreads / $totalThreads) * 100, 2) : 0;
    }

    /**
     * Get average response time
     */
    protected function getAverageResponseTime(int $days = 30): float
    {
        $threads = ForumThread::where('created_at', '>=', now()->subDays($days))
            ->with(['posts' => function ($query) {
                $query->orderBy('created_at');
            }])
            ->get();

        $responseTimes = [];
        foreach ($threads as $thread) {
            if ($thread->posts->count() > 1) {
                $firstPost = $thread->posts->first();
                $secondPost = $thread->posts->skip(1)->first();
                $responseTimes[] = $secondPost->created_at->diffInMinutes($firstPost->created_at);
            }
        }

        return count($responseTimes) > 0 ? round(array_sum($responseTimes) / count($responseTimes), 2) : 0;
    }

    /**
     * Get average sessions per user
     */
    protected function getAverageSessionsPerUser(int $days = 30): float
    {
        $startDate = now()->subDays($days);
        
        $sessionData = ForumAnalytic::where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(DISTINCT session_id) as session_count')
            ->groupBy('user_id')
            ->get();

        return $sessionData->count() > 0 ? round($sessionData->avg('session_count'), 2) : 0;
    }

    /**
     * Get average posts per user
     */
    protected function getAveragePostsPerUser(int $days = 30): float
    {
        $startDate = now()->subDays($days);
        
        $activeUsers = ForumAnalytic::where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->whereNotNull('user_id')
            ->count();
        
        $totalPosts = ForumPost::where('created_at', '>=', $startDate)->count();

        return $activeUsers > 0 ? round($totalPosts / $activeUsers, 2) : 0;
    }

    /**
     * Get top contributors
     */
    protected function getTopContributors(int $days = 30, int $limit = 5): array
    {
        return ForumAnalytic::getTopUsers('post_create', $limit, $days);
    }

    /**
     * Get user retention rate
     */
    protected function getUserRetentionRate(int $days = 30): float
    {
        $startDate = now()->subDays($days);
        $midDate = now()->subDays($days / 2);
        
        $earlyUsers = ForumAnalytic::dateRange($startDate, $midDate)
            ->distinct('user_id')
            ->whereNotNull('user_id')
            ->pluck('user_id');
        
        $returningUsers = ForumAnalytic::dateRange($midDate, now())
            ->distinct('user_id')
            ->whereNotNull('user_id')
            ->whereIn('user_id', $earlyUsers)
            ->count();

        return $earlyUsers->count() > 0 ? round(($returningUsers / $earlyUsers->count()) * 100, 2) : 0;
    }

    /**
     * Get moderation statistics
     */
    protected function getModerationStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'reports_submitted' => ForumMetric::getTotal('reports_submitted', $startDate, now()),
            'moderation_actions' => ForumMetric::getTotal('moderation_actions', $startDate, now()),
            'warnings_issued' => \App\Models\ForumWarning::where('created_at', '>=', $startDate)->count(),
            'suspensions_issued' => \App\Models\ForumSuspension::where('created_at', '>=', $startDate)->count(),
            'bans_issued' => \App\Models\ForumBan::where('created_at', '>=', $startDate)->count(),
        ];
    }

    /**
     * Calculate forum health score
     */
    protected function calculateForumHealthScore(): float
    {
        $metrics = [
            'activity_score' => $this->calculateActivityScore(),
            'engagement_score' => $this->calculateEngagementScore(),
            'quality_score' => $this->getContentQualityScore(7),
            'moderation_score' => $this->calculateModerationScore(),
        ];

        return round(array_sum($metrics) / count($metrics), 2);
    }

    /**
     * Calculate activity score
     */
    protected function calculateActivityScore(): float
    {
        $today = ForumMetric::getTotal('daily_active_users', now()->toDateString(), now()->toDateString());
        $lastWeek = ForumMetric::getAverage('daily_active_users', now()->subWeek(), now());
        
        if ($lastWeek == 0) return $today > 0 ? 100 : 0;
        
        $score = ($today / $lastWeek) * 100;
        return min($score, 100);
    }

    /**
     * Calculate engagement score
     */
    protected function calculateEngagementScore(): float
    {
        $posts = ForumMetric::getTotal('posts_created', now()->subDay(), now());
        $views = ForumMetric::getTotal('thread_views', now()->subDay(), now());
        
        if ($views == 0) return 0;
        
        $engagementRate = ($posts / $views) * 100;
        return min($engagementRate * 10, 100); // Scale to 0-100
    }

    /**
     * Calculate moderation score
     */
    protected function calculateModerationScore(): float
    {
        $reports = ForumMetric::getTotal('reports_submitted', now()->subWeek(), now());
        $actions = ForumMetric::getTotal('moderation_actions', now()->subWeek(), now());
        
        if ($reports == 0) return 100; // No reports is good
        if ($actions == 0) return 0; // Reports but no actions is bad
        
        $responseRate = ($actions / $reports) * 100;
        return min($responseRate, 100);
    }

    /**
     * Get daily metrics
     */
    protected function getDailyMetrics(string $date): array
    {
        return [
            'active_users' => ForumMetric::getTotal('daily_active_users', $date, $date),
            'posts_created' => ForumMetric::getTotal('posts_created', $date, $date),
            'threads_created' => ForumMetric::getTotal('threads_created', $date, $date),
            'votes_cast' => ForumMetric::getTotal('votes_cast', $date, $date),
        ];
    }

    /**
     * Calculate daily growth
     */
    protected function calculateDailyGrowth(string $today, string $yesterday): array
    {
        $todayMetrics = $this->getDailyMetrics($today);
        $yesterdayMetrics = $this->getDailyMetrics($yesterday);
        
        $growth = [];
        foreach ($todayMetrics as $key => $value) {
            $previousValue = $yesterdayMetrics[$key] ?? 0;
            if ($previousValue == 0) {
                $growth[$key] = $value > 0 ? 100 : 0;
            } else {
                $growth[$key] = round((($value - $previousValue) / $previousValue) * 100, 2);
            }
        }
        
        return $growth;
    }

    /**
     * Get weekly trends
     */
    protected function getWeeklyTrends(): array
    {
        return [
            'active_users' => $this->getActivityTrends('daily_active_users', 7),
            'posts_created' => $this->getActivityTrends('posts_created', 7),
            'thread_views' => $this->getActivityTrends('thread_views', 7),
        ];
    }

    /**
     * Get health alerts
     */
    protected function getHealthAlerts(): array
    {
        $alerts = [];
        
        // Check for low activity
        $todayActivity = ForumMetric::getTotal('daily_active_users', now()->toDateString(), now()->toDateString());
        $avgActivity = ForumMetric::getAverage('daily_active_users', now()->subWeek(), now());
        
        if ($todayActivity < $avgActivity * 0.5) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Daily active users are significantly below average',
                'metric' => 'daily_active_users',
                'current' => $todayActivity,
                'expected' => round($avgActivity),
            ];
        }
        
        return $alerts;
    }

    /**
     * Get health recommendations
     */
    protected function getHealthRecommendations(): array
    {
        $recommendations = [];
        
        $solutionRate = $this->getSolutionRate(7);
        if ($solutionRate < 30) {
            $recommendations[] = [
                'type' => 'improvement',
                'message' => 'Consider encouraging users to mark helpful posts as solutions',
                'action' => 'Add solution prompts and gamification',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Convert data to CSV format
     */
    protected function convertToCSV(array $data): string
    {
        // Simple CSV conversion - in a real implementation, you'd want more sophisticated handling
        $csv = "Metric,Value\n";
        
        foreach ($data['overview'] as $key => $value) {
            $csv .= "{$key},{$value}\n";
        }
        
        return $csv;
    }
}