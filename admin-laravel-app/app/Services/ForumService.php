<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ForumService
{
    /**
     * Create a new forum.
     */
    public function createForum(array $data): Forum
    {
        return DB::transaction(function () use ($data) {
            $forum = Forum::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'organization_id' => $data['organization_id'] ?? null,
                'category' => $data['category'] ?? null,
                'is_private' => $data['is_private'] ?? false,
                'moderator_ids' => $data['moderator_ids'] ?? [],
                'status' => 'active',
                'settings' => $data['settings'] ?? []
            ]);

            return $forum;
        });
    }

    /**
     * Get all forums for admin management.
     */
    public function getAllForums(): Collection
    {
        return Forum::with(['organization', 'threads' => function ($query) {
            $query->active()->latestActivity()->limit(3);
        }])
        ->orderBy('name')
        ->get();
    }

    /**
     * Get forums accessible by a user.
     */
    public function getAccessibleForums(User $user): Collection
    {
        return Forum::accessibleBy($user)
            ->active()
            ->with([
                'organization',
                'threads' => function ($query) {
                    $query->active()
                          ->latestActivity()
                          ->limit(5)
                          ->with(['author', 'lastReplyBy']);
                }
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get forums by category.
     */
    public function getForumsByCategory(User $user, string $category): Collection
    {
        return Forum::accessibleBy($user)
            ->active()
            ->byCategory($category)
            ->with(['organization', 'threads' => function ($query) {
                $query->active()->latestActivity()->limit(3);
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new thread in a forum.
     */
    public function createThread(Forum $forum, User $user, array $data): ForumThread
    {
        return DB::transaction(function () use ($forum, $user, $data) {
            $thread = $forum->threads()->create([
                'title' => $data['title'],
                'slug' => $this->generateUniqueSlug($data['title'], $forum->id),
                'content' => $data['content'],
                'author_id' => $user->id,
                'status' => 'active'
            ]);

            // Update forum statistics
            $forum->increment('thread_count');
            $forum->update(['last_activity_at' => now()]);

            return $thread->load(['author', 'forum']);
        });
    }

    /**
     * Create a new post in a thread.
     */
    public function createPost(ForumThread $thread, User $user, array $data): ForumPost
    {
        return DB::transaction(function () use ($thread, $user, $data) {
            $post = $thread->posts()->create([
                'content' => $data['content'],
                'author_id' => $user->id,
                'parent_post_id' => $data['parent_post_id'] ?? null,
                'status' => 'active'
            ]);

            // Update thread statistics
            $thread->increment('reply_count');
            $thread->update([
                'last_reply_at' => now(),
                'last_reply_by' => $user->id
            ]);

            // Update forum statistics
            $thread->forum->increment('post_count');
            $thread->forum->update(['last_activity_at' => now()]);

            return $post->load(['author', 'thread']);
        });
    }

    /**
     * Vote on a post.
     */
    public function voteOnPost(ForumPost $post, User $user, string $voteType): array
    {
        return DB::transaction(function () use ($post, $user, $voteType) {
            $existingVote = $post->votes()->where('user_id', $user->id)->first();

            if ($existingVote) {
                if ($existingVote->vote_type === $voteType) {
                    // Remove vote if same type
                    $existingVote->delete();
                    $this->updateVoteCount($post, $voteType, -1);
                } else {
                    // Change vote type
                    $this->updateVoteCount($post, $existingVote->vote_type, -1);
                    $existingVote->update(['vote_type' => $voteType]);
                    $this->updateVoteCount($post, $voteType, 1);
                }
            } else {
                // Create new vote
                $post->votes()->create([
                    'user_id' => $user->id,
                    'vote_type' => $voteType
                ]);
                $this->updateVoteCount($post, $voteType, 1);
            }

            $post->refresh();

            return [
                'upvotes' => $post->upvotes,
                'downvotes' => $post->downvotes,
                'score' => $post->vote_score,
                'user_vote' => $post->getUserVoteType($user)
            ];
        });
    }

    /**
     * Mark a post as solution.
     */
    public function markPostAsSolution(ForumPost $post, User $user): bool
    {
        // Check if user is thread author or moderator
        if ($post->thread->author_id !== $user->id && !$post->thread->forum->canModerate($user)) {
            return false;
        }

        return DB::transaction(function () use ($post) {
            $post->markAsSolution();
            return true;
        });
    }

    /**
     * Pin or unpin a thread.
     */
    public function toggleThreadPin(ForumThread $thread, User $user): bool
    {
        if (!$thread->forum->canModerate($user)) {
            return false;
        }

        $thread->update(['is_pinned' => !$thread->is_pinned]);
        return true;
    }

    /**
     * Lock or unlock a thread.
     */
    public function toggleThreadLock(ForumThread $thread, User $user): bool
    {
        if (!$thread->forum->canModerate($user)) {
            return false;
        }

        $thread->update(['is_locked' => !$thread->is_locked]);
        return true;
    }

    /**
     * Get thread with posts and pagination.
     */
    public function getThreadWithPosts(ForumThread $thread, int $page = 1, int $perPage = 10): array
    {
        $posts = $thread->posts()
            ->active()
            ->with(['author', 'attachments', 'votes'])
            ->orderBy('is_solution', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'thread' => $thread->load(['author', 'forum', 'lastReplyBy']),
            'posts' => $posts
        ];
    }

    /**
     * Search forums and threads.
     */
    public function search(User $user, string $query, array $filters = []): array
    {
        $forumsQuery = Forum::accessibleBy($user)->active();
        $threadsQuery = ForumThread::whereHas('forum', function ($q) use ($user) {
            $q->accessibleBy($user)->active();
        })->active();

        // Apply search query
        if (!empty($query)) {
            $forumsQuery->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });

            $threadsQuery->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            });
        }

        // Apply filters
        if (!empty($filters['category'])) {
            $forumsQuery->byCategory($filters['category']);
        }

        if (!empty($filters['organization_id'])) {
            $forumsQuery->where('organization_id', $filters['organization_id']);
            $threadsQuery->whereHas('forum', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        return [
            'forums' => $forumsQuery->limit(10)->get(),
            'threads' => $threadsQuery->with(['author', 'forum'])
                                    ->latestActivity()
                                    ->limit(20)
                                    ->get()
        ];
    }

    /**
     * Get forum statistics.
     */
    public function getForumStatistics(Forum $forum): array
    {
        return [
            'total_threads' => $forum->thread_count,
            'total_posts' => $forum->post_count,
            'active_threads' => $forum->threads()->active()->count(),
            'pinned_threads' => $forum->threads()->active()->pinned()->count(),
            'latest_activity' => $forum->latest_activity,
            'top_contributors' => $this->getTopContributors($forum, 5)
        ];
    }

    /**
     * Get top contributors for a forum.
     */
    public function getTopContributors(Forum $forum, int $limit = 10): Collection
    {
        return User::select('users.*')
            ->selectRaw('COUNT(forum_posts.id) as post_count')
            ->join('forum_posts', 'users.id', '=', 'forum_posts.author_id')
            ->join('forum_threads', 'forum_posts.thread_id', '=', 'forum_threads.id')
            ->where('forum_threads.forum_id', $forum->id)
            ->where('forum_posts.status', 'active')
            ->groupBy('users.id')
            ->orderBy('post_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Update forum settings.
     */
    public function updateForumSettings(Forum $forum, array $settings): Forum
    {
        $forum->update(['settings' => array_merge($forum->settings ?? [], $settings)]);
        return $forum->fresh();
    }

    /**
     * Add moderator to forum.
     */
    public function addModerator(Forum $forum, User $user): bool
    {
        $moderatorIds = $forum->moderator_ids ?? [];
        
        if (!in_array($user->id, $moderatorIds)) {
            $moderatorIds[] = $user->id;
            $forum->update(['moderator_ids' => $moderatorIds]);
            return true;
        }
        
        return false;
    }

    /**
     * Remove moderator from forum.
     */
    public function removeModerator(Forum $forum, User $user): bool
    {
        $moderatorIds = $forum->moderator_ids ?? [];
        $key = array_search($user->id, $moderatorIds);
        
        if ($key !== false) {
            unset($moderatorIds[$key]);
            $forum->update(['moderator_ids' => array_values($moderatorIds)]);
            return true;
        }
        
        return false;
    }

    /**
     * Update vote count on a post.
     */
    private function updateVoteCount(ForumPost $post, string $voteType, int $increment): void
    {
        if ($voteType === 'up') {
            $post->increment('upvotes', $increment);
        } else {
            $post->increment('downvotes', $increment);
        }
    }

    /**
     * Generate a unique slug for a thread.
     */
    private function generateUniqueSlug(string $title, int $forumId): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (ForumThread::where('slug', $slug)
                          ->whereHas('forum', function ($q) use ($forumId) {
                              $q->where('id', $forumId);
                          })
                          ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if user can access forum.
     */
    public function canUserAccessForum(User $user, Forum $forum): bool
    {
        if (!$forum->is_private) {
            return true;
        }

        return $forum->organization && 
               $forum->organization->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if user can create thread in forum.
     */
    public function canUserCreateThread(User $user, Forum $forum): bool
    {
        return $this->canUserAccessForum($user, $forum);
    }

    /**
     * Check if user can reply to thread.
     */
    public function canUserReplyToThread(User $user, ForumThread $thread): bool
    {
        if ($thread->is_locked && !$thread->forum->canModerate($user)) {
            return false;
        }

        return $this->canUserAccessForum($user, $thread->forum);
    }
}