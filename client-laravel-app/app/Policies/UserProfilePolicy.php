<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any profiles.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the profile.
     */
    public function view(User $user, UserProfile $profile): bool
    {
        // Users can view their own profile
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Check if profile is public
        if ($profile->is_public) {
            return true;
        }

        // Organization members can view profiles of other members
        if ($this->shareOrganization($user, $profile->user)) {
            return true;
        }

        // Admins can view any profile
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can create profiles.
     */
    public function create(User $user): bool
    {
        // Users can only create their own profile
        return !$user->profile()->exists();
    }

    /**
     * Determine whether the user can update the profile.
     */
    public function update(User $user, UserProfile $profile): bool
    {
        // Users can only update their own profile
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Admins can update any profile
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the profile.
     */
    public function delete(User $user, UserProfile $profile): bool
    {
        // Users can delete their own profile
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Only super admins can delete other profiles
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can restore the profile.
     */
    public function restore(User $user, UserProfile $profile): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the profile.
     */
    public function forceDelete(User $user, UserProfile $profile): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view profile analytics.
     */
    public function viewAnalytics(User $user, UserProfile $profile): bool
    {
        // Users can view their own profile analytics
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Admins can view any profile analytics
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can export profile data.
     */
    public function export(User $user, UserProfile $profile): bool
    {
        // Users can export their own profile data
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Admins can export any profile data
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can manage profile privacy settings.
     */
    public function managePrivacy(User $user, UserProfile $profile): bool
    {
        // Users can manage their own privacy settings
        return $user->id === $profile->user_id;
    }

    /**
     * Determine whether the user can view profile contact information.
     */
    public function viewContactInfo(User $user, UserProfile $profile): bool
    {
        // Users can view their own contact info
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Check privacy settings
        $settings = $profile->privacy_settings ?? [];
        
        // If contact info is public
        if (($settings['contact_info'] ?? 'private') === 'public') {
            return true;
        }

        // If contact info is visible to organization members
        if (($settings['contact_info'] ?? 'private') === 'organization' && 
            $this->shareOrganization($user, $profile->user)) {
            return true;
        }

        // Admins can view any contact info
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view profile documents.
     */
    public function viewDocuments(User $user, UserProfile $profile): bool
    {
        // Users can view their own documents
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Check privacy settings
        $settings = $profile->privacy_settings ?? [];
        
        // If documents are public
        if (($settings['documents'] ?? 'private') === 'public') {
            return true;
        }

        // If documents are visible to organization members
        if (($settings['documents'] ?? 'private') === 'organization' && 
            $this->shareOrganization($user, $profile->user)) {
            return true;
        }

        // Document verifiers and admins can view documents
        return $user->hasRole(['admin', 'super_admin', 'document_verifier']);
    }

    /**
     * Determine whether the user can view profile volunteering history.
     */
    public function viewVolunteeringHistory(User $user, UserProfile $profile): bool
    {
        // Users can view their own history
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Check privacy settings
        $settings = $profile->privacy_settings ?? [];
        
        // If history is public
        if (($settings['volunteering_history'] ?? 'public') === 'public') {
            return true;
        }

        // If history is visible to organization members
        if (($settings['volunteering_history'] ?? 'public') === 'organization' && 
            $this->shareOrganization($user, $profile->user)) {
            return true;
        }

        // Admins can view any history
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view profile skills.
     */
    public function viewSkills(User $user, UserProfile $profile): bool
    {
        // Users can view their own skills
        if ($user->id === $profile->user_id) {
            return true;
        }

        // Check privacy settings
        $settings = $profile->privacy_settings ?? [];
        
        // If skills are public
        if (($settings['skills'] ?? 'public') === 'public') {
            return true;
        }

        // If skills are visible to organization members
        if (($settings['skills'] ?? 'public') === 'organization' && 
            $this->shareOrganization($user, $profile->user)) {
            return true;
        }

        // Admins can view any skills
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can moderate the profile.
     */
    public function moderate(User $user, UserProfile $profile): bool
    {
        return $user->hasRole(['admin', 'super_admin', 'moderator']);
    }

    /**
     * Determine whether the user can verify profile information.
     */
    public function verify(User $user, UserProfile $profile): bool
    {
        return $user->hasRole(['admin', 'super_admin', 'profile_verifier']);
    }

    /**
     * Determine whether the user can feature the profile.
     */
    public function feature(User $user, UserProfile $profile): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can send messages to the profile owner.
     */
    public function sendMessage(User $user, UserProfile $profile): bool
    {
        // Can't message yourself
        if ($user->id === $profile->user_id) {
            return false;
        }

        // Check if profile allows messages
        $settings = $profile->privacy_settings ?? [];
        
        // If messages are disabled
        if (($settings['allow_messages'] ?? true) === false) {
            return false;
        }

        // If messages are restricted to organization members
        if (($settings['messages_from'] ?? 'anyone') === 'organization') {
            return $this->shareOrganization($user, $profile->user);
        }

        // If messages are restricted to verified users
        if (($settings['messages_from'] ?? 'anyone') === 'verified') {
            return $user->hasVerifiedEmail() && $user->profile?->is_verified;
        }

        return true;
    }

    /**
     * Check if two users share an organization.
     */
    protected function shareOrganization(User $user1, User $user2): bool
    {
        if (!$user1->organizations || !$user2->organizations) {
            return false;
        }

        $user1OrgIds = $user1->organizations->pluck('id')->toArray();
        $user2OrgIds = $user2->organizations->pluck('id')->toArray();

        return !empty(array_intersect($user1OrgIds, $user2OrgIds));
    }
}