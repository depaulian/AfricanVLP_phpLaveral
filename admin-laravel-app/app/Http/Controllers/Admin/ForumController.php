<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\Organization;
use App\Models\User;
use App\Services\ForumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ForumController extends Controller
{
    public function __construct(
        private ForumService $forumService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display a listing of all forums.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Forum::class);

        $forums = $this->forumService->getAllForums();
        
        return view('admin.forums.index', compact('forums'));
    }

    /**
     * Show the form for creating a new forum.
     */
    public function create(): View
    {
        $this->authorize('create', Forum::class);

        $organizations = Organization::orderBy('name')->get();
        $categories = $this->getForumCategories();

        return view('admin.forums.create', compact('organizations', 'categories'));
    }

    /**
     * Store a newly created forum.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Forum::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'organization_id' => 'nullable|exists:organizations,id',
            'category' => 'nullable|string|max:100',
            'is_private' => 'boolean',
            'moderator_ids' => 'nullable|array',
            'moderator_ids.*' => 'exists:users,id'
        ]);

        $forum = $this->forumService->createForum($validated);

        return redirect()
            ->route('admin.forums.show', $forum)
            ->with('success', 'Forum created successfully!');
    }

    /**
     * Display the specified forum.
     */
    public function show(Forum $forum): View
    {
        $this->authorize('view', $forum);

        $threads = $forum->threads()
            ->with(['author', 'lastReplyBy'])
            ->latestActivity()
            ->paginate(20);

        $forumStats = $this->forumService->getForumStatistics($forum);

        return view('admin.forums.show', compact('forum', 'threads', 'forumStats'));
    }

    /**
     * Show the form for editing the specified forum.
     */
    public function edit(Forum $forum): View
    {
        $this->authorize('update', $forum);

        $organizations = Organization::orderBy('name')->get();
        $categories = $this->getForumCategories();
        $users = User::orderBy('name')->get();

        return view('admin.forums.edit', compact('forum', 'organizations', 'categories', 'users'));
    }

    /**
     * Update the specified forum.
     */
    public function update(Request $request, Forum $forum): RedirectResponse
    {
        $this->authorize('update', $forum);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'organization_id' => 'nullable|exists:organizations,id',
            'category' => 'nullable|string|max:100',
            'is_private' => 'boolean',
            'status' => 'required|in:active,inactive',
            'moderator_ids' => 'nullable|array',
            'moderator_ids.*' => 'exists:users,id'
        ]);

        $forum->update($validated);

        return redirect()
            ->route('admin.forums.show', $forum)
            ->with('success', 'Forum updated successfully!');
    }

    /**
     * Remove the specified forum from storage.
     */
    public function destroy(Forum $forum): RedirectResponse
    {
        $this->authorize('delete', $forum);

        $forum->delete();

        return redirect()
            ->route('admin.forums.index')
            ->with('success', 'Forum deleted successfully!');
    }

    /**
     * Display forum moderation dashboard.
     */
    public function moderation(): View
    {
        $this->authorize('viewAny', Forum::class);

        // Get recent threads and posts that need moderation
        $recentThreads = ForumThread::with(['author', 'forum'])
            ->latest()
            ->limit(10)
            ->get();

        $recentPosts = ForumPost::with(['author', 'thread.forum'])
            ->latest()
            ->limit(15)
            ->get();

        // Get reported content (if reporting system exists)
        $reportedContent = collect(); // Placeholder for future reporting system

        return view('admin.forums.moderation', compact(
            'recentThreads',
            'recentPosts',
            'reportedContent'
        ));
    }

    /**
     * Bulk moderate threads.
     */
    public function bulkModerateThreads(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'thread_ids' => 'required|array',
            'thread_ids.*' => 'exists:forum_threads,id',
            'action' => 'required|in:pin,unpin,lock,unlock,delete,restore'
        ]);

        $threads = ForumThread::whereIn('id', $validated['thread_ids'])->get();
        $successCount = 0;

        foreach ($threads as $thread) {
            if (!$this->authorize('update', $thread, false)) {
                continue;
            }

            switch ($validated['action']) {
                case 'pin':
                    $thread->update(['is_pinned' => true]);
                    $successCount++;
                    break;
                case 'unpin':
                    $thread->update(['is_pinned' => false]);
                    $successCount++;
                    break;
                case 'lock':
                    $thread->update(['is_locked' => true]);
                    $successCount++;
                    break;
                case 'unlock':
                    $thread->update(['is_locked' => false]);
                    $successCount++;
                    break;
                case 'delete':
                    $thread->delete();
                    $successCount++;
                    break;
                case 'restore':
                    $thread->restore();
                    $successCount++;
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully processed {$successCount} threads."
        ]);
    }

    /**
     * Bulk moderate posts.
     */
    public function bulkModeratePosts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:forum_posts,id',
            'action' => 'required|in:delete,restore,mark_solution,unmark_solution'
        ]);

        $posts = ForumPost::whereIn('id', $validated['post_ids'])->get();
        $successCount = 0;

        foreach ($posts as $post) {
            if (!$this->authorize('update', $post, false)) {
                continue;
            }

            switch ($validated['action']) {
                case 'delete':
                    $post->delete();
                    $successCount++;
                    break;
                case 'restore':
                    $post->restore();
                    $successCount++;
                    break;
                case 'mark_solution':
                    $post->markAsSolution();
                    $successCount++;
                    break;
                case 'unmark_solution':
                    $post->unmarkAsSolution();
                    $successCount++;
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully processed {$successCount} posts."
        ]);
    }

    /**
     * Add moderator to forum.
     */
    public function addModerator(Forum $forum, Request $request): JsonResponse
    {
        $this->authorize('assignModerators', $forum);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::find($validated['user_id']);
        $success = $this->forumService->addModerator($forum, $user);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Moderator added successfully!' : 'User is already a moderator.'
        ]);
    }

    /**
     * Remove moderator from forum.
     */
    public function removeModerator(Forum $forum, User $user): JsonResponse
    {
        $this->authorize('assignModerators', $forum);

        $success = $this->forumService->removeModerator($forum, $user);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Moderator removed successfully!' : 'User is not a moderator.'
        ]);
    }

    /**
     * Get forum analytics data.
     */
    public function analytics(Forum $forum): JsonResponse
    {
        $this->authorize('view', $forum);

        $stats = $this->forumService->getForumStatistics($forum);
        
        // Add additional analytics data
        $analytics = [
            'basic_stats' => $stats,
            'activity_trend' => $this->getActivityTrend($forum),
            'top_threads' => $this->getTopThreads($forum),
            'user_engagement' => $this->getUserEngagement($forum)
        ];

        return response()->json($analytics);
    }

    /**
     * Get available forum categories.
     */
    private function getForumCategories(): array
    {
        return [
            'general' => 'General Discussion',
            'announcements' => 'Announcements',
            'support' => 'Support & Help',
            'feedback' => 'Feedback',
            'events' => 'Events',
            'volunteering' => 'Volunteering',
            'alumni' => 'Alumni',
            'resources' => 'Resources'
        ];
    }

    /**
     * Get activity trend for a forum.
     */
    private function getActivityTrend(Forum $forum): array
    {
        // Implementation for activity trend analytics
        return [
            'daily_posts' => [],
            'daily_threads' => [],
            'active_users' => []
        ];
    }

    /**
     * Get top threads for a forum.
     */
    private function getTopThreads(Forum $forum): array
    {
        return $forum->threads()
            ->active()
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get user engagement metrics for a forum.
     */
    private function getUserEngagement(Forum $forum): array
    {
        return [
            'total_participants' => $forum->threads()
                ->join('forum_posts', 'forum_threads.id', '=', 'forum_posts.thread_id')
                ->distinct('forum_posts.author_id')
                ->count(),
            'avg_posts_per_user' => 0, // Calculate based on actual data
            'most_active_users' => $this->forumService->getTopContributors($forum, 5)
        ];
    }
}