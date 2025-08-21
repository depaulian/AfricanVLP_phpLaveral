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
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the thread.
     */
    public function view(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin') || $thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can create threads.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the thread.
     */
    public function update(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin') || $thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can delete the thread.
     */
    public function delete(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin') || $thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can restore the thread.
     */
    public function restore(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin') || $thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can permanently delete the thread.
     */
    public function forceDelete(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can pin/unpin the thread.
     */
    public function pin(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin') || $thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can lock/unlock the thread.
     */
    public function lock(User $user, ForumThread $thread): bool
    {
        return $user->hasRole('admin') || $thread->forum->canModerate($user);
    }
}