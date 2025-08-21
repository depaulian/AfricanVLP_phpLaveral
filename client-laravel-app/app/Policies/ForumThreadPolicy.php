<?php

namespace App\Policies;

use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumThreadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any threads.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the thread.
     */
    public function view(User $user, ForumThread $thread): bool
    {
        // Must be able to view the forum
        return $user->can('view', $thread->forum);
    }

    /**
     * Determine whether the user can create threads.
     */
    public function create(User $user): bool
    {
        return true; // Handled by forum policy
    }

    /**
     * Determine whether the user can update the thread.
     */
    public function update(User $user, ForumThread $thread): bool
    {
        // Thread author, forum moderators, or admins can update
        return $thread->author_id === $user->id || 
               $thread->forum->canModerate($user) || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the thread.
     */
    public function delete(User $user, ForumThread $thread): bool
    {
        // Thread author (within time limit), forum moderators, or admins can delete
        $canAuthorDelete = $thread->author_id === $user->id && 
                          $thread->created_at->diffInHours(now()) < 24 &&
                          $thread->reply_count === 0;

        return $canAuthorDelete || 
               $thread->forum->canModerate($user) || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the thread.
     */
    public function restore(User $user, ForumThread $thread): bool
    {
        return $thread->forum->canModerate($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the thread.
     */
    public function forceDelete(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can reply to the thread.
     */
    public function reply(User $user, ForumThread $thread): bool
    {
        // Cannot reply to locked threads unless moderator
        if ($thread->is_locked && !$thread->forum->canModerate($user)) {
            return false;
        }

        // Must be able to view the thread
        return $this->view($user, $thread);
    }

    /**
     * Determine whether the user can pin/unpin the thread.
     */
    public function pin(User $user, ForumThread $thread): bool
    {
        return $thread->forum->canModerate($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can lock/unlock the thread.
     */
    public function lock(User $user, ForumThread $thread): bool
    {
        return $thread->forum->canModerate($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can mark posts as solution.
     */
    public function markSolution(User $user, ForumThread $thread): bool
    {
        // Thread author or forum moderators can mark solutions
        return $thread->author_id === $user->id || 
               $thread->forum->canModerate($user) || 
               $user->hasRole('admin');
    }
}