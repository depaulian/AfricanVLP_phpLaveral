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
        return $user->hasRole('admin'); // Only admins can view all forums in admin panel
    }

    /**
     * Determine whether the user can view the forum.
     */
    public function view(User $user, Forum $forum): bool
    {
        // Admins can view all forums, moderators can view forums they moderate
        return $user->hasRole('admin') || $forum->canModerate($user);
    }

    /**
     * Determine whether the user can create forums.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the forum.
     */
    public function update(User $user, Forum $forum): bool
    {
        return $user->hasRole('admin') || $forum->canModerate($user);
    }

    /**
     * Determine whether the user can delete the forum.
     */
    public function delete(User $user, Forum $forum): bool
    {
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