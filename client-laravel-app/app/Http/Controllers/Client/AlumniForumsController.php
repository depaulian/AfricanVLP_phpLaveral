<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\User;
use App\Services\ForumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlumniForumsController extends Controller
{
    public function __construct(
        private ForumService $forumService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display organization-specific alumni forums
     */
    public function index($orgId = null): View
    {
        try {
            $organization = Organization::findOrFail($orgId);
            $userId = Auth::id();

            // Check if user is alumni of this organization
            if (!$organization->alumni()->where('users.id', $userId)->exists()) {
                return redirect()->back()->with('error', 'You are not an alumni of this organization.');
            }

            // Get organization-specific forum threads
            $threads = ForumThread::whereHas('forum', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->with(['user', 'forum'])
            ->withCount(['posts as users_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT user_id)'));
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

            // Get threads user has joined (posted in)
            $joinedThreads = ForumThread::whereHas('forum', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->whereHas('posts', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['user', 'forum'])
            ->withCount(['posts as users_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT user_id)'));
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get();

            // Get user's own threads
            $myThreads = ForumThread::whereHas('forum', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->where('user_id', $userId)
            ->with(['user', 'forum'])
            ->withCount(['posts as users_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT user_id)'));
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get();

            return view('client.alumni-forums.index', compact(
                'organization', 
                'threads', 
                'joinedThreads', 
                'myThreads'
            ));

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Index Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'An error occurred while loading the forums.');
        }
    }

    /**
     * Show form to create new thread in organization forum
     */
    public function addNewThread($orgId = null): View|RedirectResponse
    {
        try {
            $organization = Organization::findOrFail($orgId);
            
            // Check if user is alumni of this organization
            if (!$organization->alumni()->where('users.id', Auth::id())->exists()) {
                return redirect()->back()->with('error', 'You are not an alumni of this organization.');
            }

            // Get or create organization forum
            $forum = Forum::firstOrCreate([
                'organization_id' => $organization->id,
                'category' => 'alumni'
            ], [
                'name' => $organization->name . ' Alumni Forum',
                'slug' => str($organization->name . '-alumni-forum')->slug(),
                'description' => 'Alumni discussion forum for ' . $organization->name,
                'is_private' => true,
                'status' => 'active'
            ]);

            return view('client.alumni-forums.create-thread', compact('organization', 'forum'));

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Add New Thread Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Store new thread in organization forum
     */
    public function storeThread($orgId, Request $request): RedirectResponse
    {
        try {
            $organization = Organization::findOrFail($orgId);
            
            // Check if user is alumni of this organization
            if (!$organization->alumni()->where('users.id', Auth::id())->exists()) {
                return redirect()->back()->with('error', 'You are not an alumni of this organization.');
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string|min:10',
            ]);

            // Get or create organization forum
            $forum = Forum::firstOrCreate([
                'organization_id' => $organization->id,
                'category' => 'alumni'
            ], [
                'name' => $organization->name . ' Alumni Forum',
                'slug' => str($organization->name . '-alumni-forum')->slug(),
                'description' => 'Alumni discussion forum for ' . $organization->name,
                'is_private' => true,
                'status' => 'active'
            ]);

            $thread = $this->forumService->createThread($forum, Auth::user(), $validated);

            return redirect()
                ->route('alumni-forums.thread', ['orgId' => $orgId, 'id' => $thread->id])
                ->with('success', 'Thread created successfully!');

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Store Thread Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'The thread could not be saved. Please try again.');
        }
    }

    /**
     * Display specific thread in organization forum
     */
    public function thread($orgId, $id = null): View|RedirectResponse
    {
        try {
            $organization = Organization::findOrFail($orgId);
            $thread = ForumThread::with(['forum', 'user'])->findOrFail($id);

            // Check if user is alumni of this organization
            if (!$organization->alumni()->where('users.id', Auth::id())->exists()) {
                return redirect()->back()->with('error', 'You are not an alumni of this organization.');
            }

            // Verify thread belongs to this organization
            if ($thread->forum->organization_id != $organization->id) {
                return redirect()->back()->with('error', 'Thread not found in this organization.');
            }

            // Increment view count
            $thread->incrementViewCount();

            // Get thread posts with pagination
            $posts = $thread->posts()
                ->with(['user', 'parentPost'])
                ->orderBy('created_at', 'asc')
                ->paginate(10);

            // Group posts by date for better display
            $postsByDate = $posts->getCollection()->groupBy(function ($post) {
                return $post->created_at->format('Y-m-d');
            });

            return view('client.alumni-forums.thread', compact(
                'organization', 
                'thread', 
                'posts',
                'postsByDate'
            ));

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Thread Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'An error occurred while loading the thread.');
        }
    }

    /**
     * Store new post in organization thread
     */
    public function storePost($orgId, $threadId, Request $request): RedirectResponse
    {
        try {
            $organization = Organization::findOrFail($orgId);
            $thread = ForumThread::findOrFail($threadId);

            // Check if user is alumni of this organization
            if (!$organization->alumni()->where('users.id', Auth::id())->exists()) {
                return redirect()->back()->with('error', 'You are not an alumni of this organization.');
            }

            $validated = $request->validate([
                'content' => 'required|string|min:5',
                'parent_post_id' => 'nullable|exists:forum_posts,id',
            ]);

            $post = $this->forumService->createPost($thread, Auth::user(), $validated);

            return redirect()
                ->route('alumni-forums.thread', ['orgId' => $orgId, 'id' => $threadId])
                ->with('success', 'Reply posted successfully!');

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Store Post Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'The comment could not be saved. Please try again.');
        }
    }

    /**
     * Display public forums (not organization-specific)
     */
    public function publicThreads(): View
    {
        try {
            $userId = Auth::id();

            // Get public threads (no organization)
            $threads = ForumThread::whereHas('forum', function ($query) {
                $query->whereNull('organization_id')
                      ->where('is_private', false);
            })
            ->with(['user', 'forum'])
            ->withCount(['posts as users_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT user_id)'));
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

            // Get threads user has joined (posted in)
            $joinedThreads = ForumThread::whereHas('forum', function ($query) {
                $query->whereNull('organization_id')
                      ->where('is_private', false);
            })
            ->whereHas('posts', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['user', 'forum'])
            ->withCount(['posts as users_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT user_id)'));
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get();

            // Get user's own threads
            $myThreads = ForumThread::whereHas('forum', function ($query) {
                $query->whereNull('organization_id')
                      ->where('is_private', false);
            })
            ->where('user_id', $userId)
            ->with(['user', 'forum'])
            ->withCount(['posts as users_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT user_id)'));
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get();

            return view('client.alumni-forums.public-threads', compact(
                'threads', 
                'joinedThreads', 
                'myThreads'
            ));

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Public Threads Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'An error occurred while loading the forums.');
        }
    }

    /**
     * Show form to create new public thread
     */
    public function addNewPublicThread(): View
    {
        // Get or create public forum
        $forum = Forum::firstOrCreate([
            'organization_id' => null,
            'category' => 'public',
            'slug' => 'public-forum'
        ], [
            'name' => 'Public Forum',
            'description' => 'General discussion forum for all users',
            'is_private' => false,
            'status' => 'active'
        ]);

        return view('client.alumni-forums.create-public-thread', compact('forum'));
    }

    /**
     * Store new public thread
     */
    public function storePublicThread(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string|min:10',
            ]);

            // Get or create public forum
            $forum = Forum::firstOrCreate([
                'organization_id' => null,
                'category' => 'public',
                'slug' => 'public-forum'
            ], [
                'name' => 'Public Forum',
                'description' => 'General discussion forum for all users',
                'is_private' => false,
                'status' => 'active'
            ]);

            $thread = $this->forumService->createThread($forum, Auth::user(), $validated);

            return redirect()
                ->route('alumni-forums.public-thread', ['id' => $thread->id])
                ->with('success', 'Thread created successfully!');

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Store Public Thread Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'The thread could not be saved. Please try again.');
        }
    }

    /**
     * Display specific public thread
     */
    public function publicThread($id = null): View|RedirectResponse
    {
        try {
            $thread = ForumThread::with(['forum', 'user'])->findOrFail($id);

            // Verify it's a public thread
            if ($thread->forum->organization_id !== null || $thread->forum->is_private) {
                return redirect()->back()->with('error', 'Thread not found.');
            }

            // Increment view count
            $thread->incrementViewCount();

            // Get thread posts with pagination
            $posts = $thread->posts()
                ->with(['user', 'parentPost'])
                ->orderBy('created_at', 'asc')
                ->paginate(10);

            // Group posts by date for better display
            $postsByDate = $posts->getCollection()->groupBy(function ($post) {
                return $post->created_at->format('Y-m-d');
            });

            return view('client.alumni-forums.public-thread', compact(
                'thread', 
                'posts',
                'postsByDate'
            ));

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Public Thread Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'An error occurred while loading the thread.');
        }
    }

    /**
     * Store new post in public thread
     */
    public function storePublicPost($threadId, Request $request): RedirectResponse
    {
        try {
            $thread = ForumThread::findOrFail($threadId);

            // Verify it's a public thread
            if ($thread->forum->organization_id !== null || $thread->forum->is_private) {
                return redirect()->back()->with('error', 'Thread not found.');
            }

            $validated = $request->validate([
                'content' => 'required|string|min:5',
                'parent_post_id' => 'nullable|exists:forum_posts,id',
            ]);

            $post = $this->forumService->createPost($thread, Auth::user(), $validated);

            return redirect()
                ->route('alumni-forums.public-thread', ['id' => $threadId])
                ->with('success', 'Reply posted successfully!');

        } catch (\Throwable $ex) {
            logger()->error('Alumni Forums Store Public Post Error: ' . $ex->getMessage());
            return redirect()->back()->with('error', 'The comment could not be saved. Please try again.');
        }
    }
}