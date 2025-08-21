<?php

namespace App\Policies;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any forums.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view forums
    }

    /**
     * Determine whether the user can view the forum.
     */
    public function view(User $user, Forum $forum): bool
    {
        // Public forums are accessible to all authenticated users
        if (!$forum->is_private) {
            return true;
        }

        // Private forums require organization membership
        return $forum->organization && 
               $forum->organization->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create forums.
     */
    public function create(User $user): bool
    {
        // Only admins can create forums
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the forum.
     */
    public function update(User $user, Forum $forum): bool
    {
        // Admins and forum moderators can update forums
        return $user->hasRole('admin') || $forum->canModerate($user);
    }

    /**
     * Determine whether the user can delete the forum.
     */
    public function delete(User $user, Forum $forum): bool
    {
        // Only admins can delete forums
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the forum.
     */
    public function restore(User $user, Forum $forum): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the forum.
     */
    public function forceDelete(User $user, Forum $forum): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create threads in the forum.
     */
    public function createThread(User $user, Forum $forum): bool
    {
        // Must be able to view the forum first
        if (!$this->view($user, $forum)) {
            return false;
        }

        // Forum must be active
        return $forum->status === 'active';
    }

    /**
     * Determine whether the user can moderate the forum.
     */
    public function moderate(User $user, Forum $forum): bool
    {
        return $user->hasRole('admin') || $forum->canModerate($user);
    }

    /**
     * Determine whether the user can manage forum settings.
     */
    public function manageSettings(User $user, Forum $forum): bool
    {
        return $user->hasRole('admin') || $forum->canModerate($user);
    }

    /**
     * Determine whether the user can assign moderators.
     */
    public function assignModerators(User $user, Forum $forum): bool
    {
        return $user->hasRole('admin');
    }
}