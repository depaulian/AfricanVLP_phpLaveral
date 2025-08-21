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
                case 'forums.index':
                    $this->analyticsService->track('forum_index_view', null, $user);
                    break;

                case 'forums.show':
                    $forum = $route->parameter('forum');
                    if ($forum instanceof Forum) {
                        $this->analyticsService->track('forum_view', $forum, $user);
                    }
                    break;

                case 'forums.threads.show':
                    $thread = $route->parameter('thread');
                    if ($thread instanceof ForumThread) {
                        $this->analyticsService->track('thread_view', $thread, $user, [
                            'forum_id' => $thread->forum_id,
                            'forum_name' => $thread->forum->name ?? null,
                        ]);
                    }
                    break;

                case 'forums.search':
                    $query = $request->get('q');
                    if ($query) {
                        $this->analyticsService->track('search_performed', null, $user, [
                            'query' => $query,
                            'results_count' => $this->getSearchResultsCount($request),
                        ]);
                    }
                    break;

                case 'forums.attachments.download':
                    $attachment = $route->parameter('attachment');
                    if ($attachment) {
                        $this->analyticsService->track('attachment_download', $attachment, $user, [
                            'filename' => $attachment->filename ?? null,
                            'file_size' => $attachment->file_size ?? null,
                        ]);
                    }
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

    /**
     * Get search results count from the request
     */
    protected function getSearchResultsCount(Request $request): ?int
    {
        // This would need to be implemented based on how search results are passed to the view
        // For now, return null
        return null;
    }
}