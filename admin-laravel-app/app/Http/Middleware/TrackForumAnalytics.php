<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ForumAnalyticsService;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use Symfony\Component\HttpFoundation\Response;

class TrackForumAnalytics
{
    protected ForumAnalyticsService $analyticsService;

    public function __construct(ForumAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests to avoid tracking form submissions, etc.
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $this->trackPageView($request);
        }

        return $response;
    }

    /**
     * Track page view based on route
     */
    protected function trackPageView(Request $request): void
    {
        $route = $request->route();
        if (!$route) return;

        $routeName = $route->getName();
        $user = $request->user();

        try {
            switch ($routeName) {
                case 'admin.forums.index':
                    $this->analyticsService->track('admin_forum_index_view', null, $user);
                    break;

                case 'admin.forums.show':
                    $forum = $route->parameter('forum');
                    if ($forum instanceof Forum) {
                        $this->analyticsService->track('admin_forum_view', $forum, $user);
                    }
                    break;

                case 'admin.forums.analytics.index':
                    $this->analyticsService->track('admin_analytics_view', null, $user);
                    break;
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the user experience
            \Log::warning('Failed to track forum analytics', [
                'route' => $routeName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}