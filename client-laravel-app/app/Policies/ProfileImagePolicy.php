<?php

namespace App\Policies;

use App\Models\ProfileImage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProfileImagePolicy
{
    /**
     * Determine whether the user can view any profile images.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the profile image.
     */
    public function view(User $user, ProfileImage $profileImage): bool
    {
        // Users can view their own images
        if ($user->id === $profileImage->user_id) {
            return true;
        }
        
        // Admins can view all images
        if ($user->isAdmin()) {
            return true;
        }
        
        // Others can only view approved images
        return $profileImage->isApproved();
    }

    /**
     * Determine whether the user can create profile images.
     */
    public function create(User $user): bool
    {
        // Check if user has reached the maximum number of images
        $maxImages = config('profile.max_images_per_user', 10);
        $currentCount = $user->profileImages()->count();
        
        return $currentCount < $maxImages;
    }

    /**
     * Determine whether the user can update the profile image.
     */
    public function update(User $user, ProfileImage $profileImage): bool
    {
        // Users can only update their own images
        if ($user->id !== $profileImage->user_id) {
            return false;
        }
        
        // Can't update rejected images
        if ($profileImage->isRejected()) {
            return false;
        }
        
        return true;
    }

    /**
     * Determine whether the user can delete the profile image.
     */
    public function delete(User $user, ProfileImage $profileImage): bool
    {
        // Users can delete their own images
        if ($user->id === $profileImage->user_id) {
            return true;
        }
        
        // Admins can delete any image
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the profile image.
     */
    public function restore(User $user, ProfileImage $profileImage): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the profile image.
     */
    public function forceDelete(User $user, ProfileImage $profileImage): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can moderate profile images.
     */
    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can set an image as current.
     */
    public function setCurrent(User $user, ProfileImage $profileImage): bool
    {
        // Users can only set their own approved images as current
        return $user->id === $profileImage->user_id && $profileImage->isApproved();
    }

    /**
     * Determine whether the user can crop the image.
     */
    public function crop(User $user, ProfileImage $profileImage): bool
    {
        // Users can crop their own images that aren't rejected
        return $user->id === $profileImage->user_id && !$profileImage->isRejected();
    }

    /**
     * Determine whether the user can download the original image.
     */
    public function download(User $user, ProfileImage $profileImage): bool
    {
        // Users can download their own images
        if ($user->id === $profileImage->user_id) {
            return true;
        }
        
        // Admins can download any image
        return $user->isAdmin();
    }
}