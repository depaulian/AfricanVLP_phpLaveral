<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
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
     * Display a listing of accessible forums.
     */
    public function index(): View
    {
        $forums = $this->forumService->getAccessibleForums(auth()->user());
        
        // Group forums by category
        $forumsByCategory = $forums->groupBy('category');
        
        return view('client.forums.index', compact('forums', 'forumsByCategory'));
    }

    /**
     * Show a specific forum with its threads.
     */
    public function show(Forum $forum): View
    {
        $this->authorize('view', $forum);

        $threads = $forum->threads()
            ->active()
            ->with(['author', 'lastReplyBy'])
            ->latestActivity()
            ->paginate(20);

        $forumStats = $this->forumService->getForumStatistics($forum);

        return view('client.forums.show', compact('forum', 'threads', 'forumStats'));
    }

    /**
     * Show the form for creating a new thread.
     */
    public function createThread(Forum $forum): View
    {
        $this->authorize('createThread', $forum);

        return view('client.forums.create-thread', compact('forum'));
    }

    /**
     * Store a newly created thread.
     */
    public function storeThread(Forum $forum, Request $request): RedirectResponse
    {
        $this->authorize('createThread', $forum);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
        ]);

        $thread = $this->forumService->createThread($forum, auth()->user(), $validated);

        return redirect()
            ->route('forums.threads.show', [$forum, $thread])
            ->with('success', 'Thread created successfully!');
    }

    /**
     * Display a specific thread with its posts.
     */
    public function showThread(Forum $forum, ForumThread $thread): View
    {
        $this->authorize('view', $thread);

        // Increment view count
        $thread->incrementViewCount();

        $threadData = $this->forumService->getThreadWithPosts(
            $thread, 
            request('page', 1), 
            10
        );

        return view('client.forums.thread', [
            'forum' => $forum,
            'thread' => $threadData['thread'],
            'posts' => $threadData['posts']
        ]);
    }

    /**
     * Store a new post in a thread.
     */
    public function storePost(Forum $forum, ForumThread $thread, Request $request): RedirectResponse
    {
        $this->authorize('reply', $thread);

        $validated = $request->validate([
            'content' => 'required|string|min:5',
            'parent_post_id' => 'nullable|exists:forum_posts,id',
        ]);

        $post = $this->forumService->createPost($thread, auth()->user(), $validated);

        // Handle file attachments if present
        if ($request->hasFile('attachments')) {
            try {
                $this->handleAttachments($post, $request->file('attachments'));
            } catch (\Exception $e) {
                return redirect()
                    ->route('forums.threads.show', [$forum, $thread])
                    ->with('warning', 'Reply posted successfully, but some attachments failed to upload: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('forums.threads.show', [$forum, $thread])
            ->with('success', 'Reply posted successfully!');
    }

    /**
     * Vote on a post.
     */
    public function vote(ForumPost $post, Request $request): JsonResponse
    {
        $this->authorize('vote', $post);

        $validated = $request->validate([
            'vote_type' => 'required|in:up,down'
        ]);

        $result = $this->forumService->voteOnPost(
            $post,
            auth()->user(),
            $validated['vote_type']
        );

        return response()->json($result);
    }

    /**
     * Mark a post as solution.
     */
    public function markSolution(ForumPost $post): JsonResponse
    {
        $this->authorize('markSolution', $post->thread);

        $success = $this->forumService->markPostAsSolution($post, auth()->user());

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Post marked as solution!' : 'Unable to mark as solution.'
        ]);
    }

    /**
     * Search forums and threads.
     */
    public function search(Request $request): View
    {
        $searchService = app(\App\Services\ForumSearchService::class);
        
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'category' => 'nullable|string',
            'organization_id' => 'nullable|exists:organizations,id',
            'forum_id' => 'nullable|exists:forums,id',
            'author_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort' => 'nullable|in:relevance,newest,oldest,most_replies,most_views,most_votes,recent_activity',
            'solutions_only' => 'nullable|boolean'
        ]);

        $query = $validated['q'] ?? '';
        $filters = array_filter([
            'category' => $validated['category'] ?? null,
            'organization_id' => $validated['organization_id'] ?? null,
            'forum_id' => $validated['forum_id'] ?? null,
            'author_id' => $validated['author_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'sort' => $validated['sort'] ?? 'relevance',
            'solutions_only' => $validated['solutions_only'] ?? false
        ]);

        $results = $searchService->search(auth()->user(), $query, $filters);
        $searchFilters = $searchService->getSearchFilters(auth()->user());

        // Log the search for analytics
        $searchService->logSearch(auth()->user(), $query, $filters, $results['total_results']);

        return view('client.forums.search', [
            'query' => $query,
            'filters' => $filters,
            'results' => $results,
            'searchFilters' => $searchFilters,
            'forums' => $results['forums'],
            'threads' => $results['threads'],
            'posts' => $results['posts']
        ]);
    }

    /**
     * Get search suggestions (AJAX)
     */
    public function searchSuggestions(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100'
        ]);

        $searchService = app(\App\Services\ForumSearchService::class);
        $suggestions = $searchService->getSearchSuggestions(auth()->user(), $validated['q']);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Get advanced search filters (AJAX)
     */
    public function getSearchFilters(Request $request): \Illuminate\Http\JsonResponse
    {
        $searchService = app(\App\Services\ForumSearchService::class);
        $filters = $searchService->getSearchFilters(auth()->user());

        return response()->json([
            'success' => true,
            'filters' => $filters
        ]);
    }

    /**
     * Pin or unpin a thread.
     */
    public function togglePin(Forum $forum, ForumThread $thread): JsonResponse
    {
        $this->authorize('pin', $thread);

        $success = $this->forumService->toggleThreadPin($thread, auth()->user());

        return response()->json([
            'success' => $success,
            'is_pinned' => $thread->fresh()->is_pinned,
            'message' => $success ? 'Thread pin status updated!' : 'Unable to update pin status.'
        ]);
    }

    /**
     * Lock or unlock a thread.
     */
    public function toggleLock(Forum $forum, ForumThread $thread): JsonResponse
    {
        $this->authorize('lock', $thread);

        $success = $this->forumService->toggleThreadLock($thread, auth()->user());

        return response()->json([
            'success' => $success,
            'is_locked' => $thread->fresh()->is_locked,
            'message' => $success ? 'Thread lock status updated!' : 'Unable to update lock status.'
        ]);
    }

    /**
     * Show edit form for a thread.
     */
    public function editThread(Forum $forum, ForumThread $thread): View
    {
        $this->authorize('update', $thread);

        return view('client.forums.edit-thread', compact('forum', 'thread'));
    }

    /**
     * Update a thread.
     */
    public function updateThread(Forum $forum, ForumThread $thread, Request $request): RedirectResponse
    {
        $this->authorize('update', $thread);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
        ]);

        $thread->update($validated);

        return redirect()
            ->route('forums.threads.show', [$forum, $thread])
            ->with('success', 'Thread updated successfully!');
    }

    /**
     * Show edit form for a post.
     */
    public function editPost(ForumPost $post): View
    {
        $this->authorize('update', $post);

        return view('client.forums.edit-post', compact('post'));
    }

    /**
     * Update a post.
     */
    public function updatePost(ForumPost $post, Request $request): RedirectResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'content' => 'required|string|min:5',
        ]);

        $post->update($validated);

        return redirect()
            ->route('forums.threads.show', [$post->thread->forum, $post->thread])
            ->with('success', 'Post updated successfully!');
    }

    /**
     * Delete a thread.
     */
    public function deleteThread(Forum $forum, ForumThread $thread): RedirectResponse
    {
        $this->authorize('delete', $thread);

        $thread->delete();

        return redirect()
            ->route('forums.show', $forum)
            ->with('success', 'Thread deleted successfully!');
    }

    /**
     * Delete a post.
     */
    public function deletePost(ForumPost $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $thread = $post->thread;
        $forum = $thread->forum;
        
        $post->delete();

        return redirect()
            ->route('forums.threads.show', [$forum, $thread])
            ->with('success', 'Post deleted successfully!');
    }

    /**
     * Handle file attachments for a post.
     */
    private function handleAttachments(ForumPost $post, array $files): void
    {
        try {
            $attachmentService = app(\App\Services\ForumAttachmentService::class);
            $attachmentService->uploadAttachments($post, $files);
        } catch (\Exception $e) {
            // Log error and throw to be handled by calling method
            logger()->error('Failed to upload forum attachments: ' . $e->getMessage(), [
                'post_id' => $post->id,
                'files_count' => count($files)
            ]);
            throw $e;
        }
    }
}