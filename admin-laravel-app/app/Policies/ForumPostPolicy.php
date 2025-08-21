<?php

namespace App\Policies;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumPostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any posts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the post.
     */
    public function view(User $user, ForumPost $post): bool
    {
        return $user->hasRole('admin') || $post->thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can create posts.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, ForumPost $post): bool
    {
        return $user->hasRole('admin') || $post->thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, ForumPost $post): bool
    {
        return $user->hasRole('admin') || $post->thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can restore the post.
     */
    public function restore(User $user, ForumPost $post): bool
    {
        return $user->hasRole('admin') || $post->thread->forum->canModerate($user);
    }

    /**
     * Determine whether the user can permanently delete the post.
     */
    public function forceDelete(User $user, ForumPost $post): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can mark the post as solution.
     */
    public function markAsSolution(User $user, ForumPost $post): bool
    {
        return $user->hasRole('admin') || $post->thread->forum->canModerate($user);
    }
}