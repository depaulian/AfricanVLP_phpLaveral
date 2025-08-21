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
        return true;
    }

    /**
     * Determine whether the user can view the post.
     */
    public function view(User $user, ForumPost $post): bool
    {
        // Must be able to view the thread
        return $user->can('view', $post->thread);
    }

    /**
     * Determine whether the user can create posts.
     */
    public function create(User $user): bool
    {
        return true; // Handled by thread policy
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, ForumPost $post): bool
    {
        // Post author (within time limit), forum moderators, or admins can update
        $canAuthorEdit = $post->author_id === $user->id && 
                        $post->created_at->diffInHours(now()) < 24;

        return $canAuthorEdit || 
               $post->thread->forum->canModerate($user) || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, ForumPost $post): bool
    {
        // Post author (within time limit), forum moderators, or admins can delete
        $canAuthorDelete = $post->author_id === $user->id && 
                          $post->created_at->diffInHours(now()) < 24 &&
                          $post->replies()->count() === 0;

        return $canAuthorDelete || 
               $post->thread->forum->canModerate($user) || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the post.
     */
    public function restore(User $user, ForumPost $post): bool
    {
        return $post->thread->forum->canModerate($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the post.
     */
    public function forceDelete(User $user, ForumPost $post): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can vote on the post.
     */
    public function vote(User $user, ForumPost $post): bool
    {
        // Cannot vote on own posts
        if ($post->author_id === $user->id) {
            return false;
        }

        // Must be able to view the post
        return $this->view($user, $post);
    }

    /**
     * Determine whether the user can mark the post as solution.
     */
    public function markAsSolution(User $user, ForumPost $post): bool
    {
        // Thread author or forum moderators can mark solutions
        return $post->thread->author_id === $user->id || 
               $post->thread->forum->canModerate($user) || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can attach files to the post.
     */
    public function attachFiles(User $user, ForumPost $post): bool
    {
        // Post author can attach files during creation/editing
        return $post->author_id === $user->id;
    }
}