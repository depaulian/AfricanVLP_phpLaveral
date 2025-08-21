<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProfilePrivacyService
{
    /**
     * Get default privacy settings for a new profile.
     */
    public function getDefaultPrivacySettings(): array
    {
        return config('profile_privacy.default_settings', []);
    }

    /**
     * Update privacy settings for a profile.
     */
    public function updatePrivacySettings(UserProfile $profile, array $settings): UserProfile
    {
        // Validate settings against allowed values
        $validatedSettings = $this->validatePrivacySettings($settings);
        
        // Merge with existing settings
        $currentSettings = $profile->privacy_settings ?? [];
        $newSettings = array_merge($currentSettings, $validatedSettings);
        
        // Update profile
        $profile->update(['privacy_settings' => $newSettings]);
        
        // Log the privacy change
        activity()
            ->causedBy(Auth::user())
            ->performedOn($profile)
            ->withProperties([
                'old_settings' => $currentSettings,
                'new_settings' => $newSettings,
                'changed_fields' => array_keys($validatedSettings),
            ])
            ->log('Privacy settings updated');
        
        return $profile->fresh();
    }

    /**
     * Validate privacy settings against allowed values.
     */
    protected function validatePrivacySettings(array $settings): array
    {
        $validated = [];
        $allowedLevels = array_keys(config('profile_privacy.privacy_levels', []));
        $allowedMessageRestrictions = array_keys(config('profile_privacy.message_restrictions', []));
        
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'profile_visibility':
                case 'contact_info':
                case 'skills':
                case 'volunteering_history':
                case 'volunteering_interests':
                case 'documents':
                case 'alumni_organizations':
                    if (in_array($value, $allowedLevels)) {
                        $validated[$key] = $value;
                    }
                    break;
                    
                case 'messages_from':
                    if (in_array($value, $allowedMessageRestrictions)) {
                        $validated[$key] = $value;
                    }
                    break;
                    
                case 'allow_messages':
                case 'show_online_status':
                case 'show_last_active':
                case 'allow_profile_indexing':
                case 'show_in_directory':
                case 'allow_data_export':
                    $validated[$key] = (bool) $value;
                    break;
            }
        }
        
        return $validated;
    }

    /**
     * Check if a user can view specific profile information.
     */
    public function canViewProfileSection(User $viewer, UserProfile $profile, string $section): bool
    {
        // Profile owner can always view their own information
        if ($viewer->id === $profile->user_id) {
            return true;
        }

        // Admins can view everything
        if ($viewer->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Get privacy setting for this section
        $settings = $profile->privacy_settings ?? [];
        $sectionPrivacy = $settings[$section] ?? $this->getDefaultPrivacySettings()[$section] ?? 'private';

        return $this->checkPrivacyLevel($viewer, $profile, $sectionPrivacy);
    }

    /**
     * Check if viewer meets privacy level requirements.
     */
    protected function checkPrivacyLevel(User $viewer, UserProfile $profile, string $privacyLevel): bool
    {
        switch ($privacyLevel) {
            case 'public':
                return true;
                
            case 'organization':
                return $this->shareOrganization($viewer, $profile->user);
                
            case 'private':
                return false;
                
            default:
                return false;
        }
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

    /**
     * Filter profile data based on privacy settings.
     */
    public function filterProfileData(User $viewer, UserProfile $profile, array $data): array
    {
        $filtered = [];
        
        foreach ($data as $section => $sectionData) {
            if ($this->canViewProfileSection($viewer, $profile, $section)) {
                $filtered[$section] = $sectionData;
            } else {
                // Provide limited information for restricted sections
                $filtered[$section] = $this->getRestrictedSectionData($section);
            }
        }
        
        return $filtered;
    }

    /**
     * Get limited data for restricted sections.
     */
    protected function getRestrictedSectionData(string $section): array
    {
        switch ($section) {
            case 'contact_info':
                return ['message' => 'Contact information is private'];
                
            case 'documents':
                return ['message' => 'Documents are private'];
                
            case 'skills':
                return ['message' => 'Skills information is private'];
                
            case 'volunteering_history':
                return ['message' => 'Volunteering history is private'];
                
            default:
                return ['message' => 'This information is private'];
        }
    }

    /**
     * Get privacy summary for a profile.
     */
    public function getPrivacySummary(UserProfile $profile): array
    {
        $settings = $profile->privacy_settings ?? $this->getDefaultPrivacySettings();
        $sections = config('profile_privacy.profile_sections', []);
        $levels = config('profile_privacy.privacy_levels', []);
        
        $summary = [];
        
        foreach ($sections as $sectionKey => $sectionInfo) {
            $privacyLevel = $settings[$sectionKey] ?? 'private';
            $summary[$sectionKey] = [
                'label' => $sectionInfo['label'],
                'privacy_level' => $privacyLevel,
                'privacy_label' => $levels[$privacyLevel]['label'] ?? 'Unknown',
                'is_required' => $sectionInfo['required'] ?? false,
            ];
        }
        
        return $summary;
    }

    /**
     * Check if profile meets verification requirements.
     */
    public function checkVerificationRequirements(UserProfile $profile): array
    {
        $requirements = [
            'email_verified' => $profile->user->hasVerifiedEmail(),
            'phone_verified' => !empty($profile->user->phone_verified_at),
            'profile_complete' => $this->isProfileComplete($profile),
            'documents_uploaded' => $profile->user->documents()->count() > 0,
            'organization_verified' => $profile->user->userAlumniOrganizations()
                ->where('is_verified', true)->exists(),
        ];
        
        $requirements['overall_score'] = array_sum($requirements) / count($requirements) * 100;
        $requirements['is_verified'] = $requirements['overall_score'] >= 80;
        
        return $requirements;
    }

    /**
     * Check if profile is complete.
     */
    protected function isProfileComplete(UserProfile $profile): bool
    {
        $requiredFields = ['bio', 'date_of_birth', 'city_id', 'country_id'];
        
        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }
        
        // Check if user has at least one skill
        if ($profile->user->skills()->count() === 0) {
            return false;
        }
        
        // Check if user has at least one volunteering interest
        if ($profile->user->volunteeringInterests()->count() === 0) {
            return false;
        }
        
        return true;
    }

    /**
     * Get recommended privacy settings based on user type.
     */
    public function getRecommendedSettings(User $user): array
    {
        $defaults = $this->getDefaultPrivacySettings();
        
        // For organization members, recommend more open settings
        if ($user->organizations()->count() > 0) {
            $defaults['contact_info'] = 'organization';
            $defaults['skills'] = 'public';
            $defaults['volunteering_history'] = 'public';
        }
        
        // For verified users, recommend public visibility
        if ($user->hasVerifiedEmail() && $user->profile?->is_verified) {
            $defaults['profile_visibility'] = 'public';
            $defaults['show_in_directory'] = true;
        }
        
        return $defaults;
    }

    /**
     * Export user profile data according to privacy settings.
     */
    public function exportProfileData(UserProfile $profile, string $format = 'json'): array
    {
        $data = [
            'profile_id' => $profile->id,
            'exported_at' => now()->toISOString(),
            'format' => $format,
            'user' => [
                'id' => $profile->user->id,
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'created_at' => $profile->user->created_at,
            ],
            'profile' => $profile->toArray(),
            'privacy_settings' => $profile->privacy_settings,
        ];
        
        // Include related data if privacy allows
        if (config('profile_privacy.export_options.include_documents', true)) {
            $data['documents'] = $profile->user->documents()->get()->toArray();
        }
        
        if (config('profile_privacy.export_options.include_analytics', true)) {
            $data['analytics'] = $this->getProfileAnalytics($profile);
        }
        
        // Log the export
        activity()
            ->causedBy(Auth::user())
            ->performedOn($profile)
            ->withProperties(['format' => $format])
            ->log('Profile data exported');
        
        return $data;
    }

    /**
     * Get basic profile analytics.
     */
    protected function getProfileAnalytics(UserProfile $profile): array
    {
        return [
            'profile_views' => activity()
                ->performedOn($profile)
                ->where('description', 'Profile view access')
                ->count(),
            'last_updated' => $profile->updated_at,
            'completion_score' => $this->calculateCompletionScore($profile),
        ];
    }

    /**
     * Calculate profile completion score.
     */
    protected function calculateCompletionScore(UserProfile $profile): int
    {
        $totalFields = 10; // Total number of profile fields
        $completedFields = 0;
        
        $fields = ['bio', 'date_of_birth', 'gender', 'phone_number', 'address', 'city_id', 'country_id'];
        
        foreach ($fields as $field) {
            if (!empty($profile->$field)) {
                $completedFields++;
            }
        }
        
        // Add points for related data
        if ($profile->user->skills()->count() > 0) $completedFields++;
        if ($profile->user->volunteeringInterests()->count() > 0) $completedFields++;
        if ($profile->user->documents()->count() > 0) $completedFields++;
        
        return (int) (($completedFields / $totalFields) * 100);
    }
}