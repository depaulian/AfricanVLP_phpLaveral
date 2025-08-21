<?php

namespace App\Services;

use App\Models\User;
use App\Models\ProfileAchievement;
use App\Models\ProfileScore;
use App\Notifications\ProfileAchievementEarned;
use Illuminate\Support\Collection;

class ProfileGamificationService
{
    /**
     * Calculate and update user's profile score.
     */
    public function calculateProfileScore(User $user): ProfileScore
    {
        $completionScore = $this->calculateCompletionScore($user);
        $qualityScore = $this->calculateQualityScore($user);
        $engagementScore = $this->calculateEngagementScore($user);
        $verificationScore = $this->calculateVerificationScore($user);
        
        $totalScore = round(($completionScore + $qualityScore + $engagementScore + $verificationScore) / 4);
        
        $profileScore = $user->profileScore()->updateOrCreate([], [
            'completion_score' => $completionScore,
            'quality_score' => $qualityScore,
            'engagement_score' => $engagementScore,
            'verification_score' => $verificationScore,
            'total_score' => $totalScore,
            'last_calculated_at' => now(),
        ]);

        // Update rank position
        $this->updateRankPosition($profileScore);
        
        // Check for new achievements
        $this->checkAndAwardAchievements($user, $profileScore);
        
        return $profileScore;
    }

    /**
     * Calculate completion score based on profile completeness.
     */
    private function calculateCompletionScore(User $user): int
    {
        $profile = $user->profile;
        if (!$profile) {
            return 0;
        }

        $score = 0;
        $maxScore = 100;

        // Basic profile fields (40 points)
        $basicFields = ['bio', 'date_of_birth', 'phone_number', 'address', 'city_id'];
        $completedBasic = 0;
        foreach ($basicFields as $field) {
            if (!empty($profile->$field)) {
                $completedBasic++;
            }
        }
        $score += ($completedBasic / count($basicFields)) * 40;

        // Profile image (10 points)
        if ($profile->profile_image_url) {
            $score += 10;
        }

        // Skills (20 points)
        $skillsCount = $user->skills()->count();
        if ($skillsCount > 0) {
            $score += min($skillsCount * 4, 20); // Max 20 points for skills
        }

        // Volunteering interests (15 points)
        $interestsCount = $user->volunteeringInterests()->count();
        if ($interestsCount > 0) {
            $score += min($interestsCount * 3, 15); // Max 15 points for interests
        }

        // Volunteering history (15 points)
        $historyCount = $user->volunteeringHistory()->count();
        if ($historyCount > 0) {
            $score += min($historyCount * 5, 15); // Max 15 points for history
        }

        return min($score, $maxScore);
    }

    /**
     * Calculate quality score based on content richness.
     */
    private function calculateQualityScore(User $user): int
    {
        $profile = $user->profile;
        if (!$profile) {
            return 0;
        }

        $score = 0;

        // Bio quality (30 points)
        if ($profile->bio) {
            $bioLength = strlen($profile->bio);
            if ($bioLength >= 200) {
                $score += 30;
            } elseif ($bioLength >= 100) {
                $score += 20;
            } elseif ($bioLength >= 50) {
                $score += 10;
            }
        }

        // Skills with descriptions (25 points)
        $skillsWithExperience = $user->skills()->whereNotNull('years_experience')->count();
        $score += min($skillsWithExperience * 5, 25);

        // Detailed volunteering history (25 points)
        $detailedHistory = $user->volunteeringHistory()
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->count();
        $score += min($detailedHistory * 8, 25);

        // Social links (10 points)
        $socialLinks = array_filter([
            $profile->linkedin_url,
            $profile->twitter_url,
            $profile->facebook_url,
            $profile->website_url
        ]);
        $score += min(count($socialLinks) * 2.5, 10);

        // Alumni organizations (10 points)
        $alumniCount = $user->alumniOrganizations()->count();
        $score += min($alumniCount * 5, 10);

        return min($score, 100);
    }

    /**
     * Calculate engagement score based on platform activity.
     */
    private function calculateEngagementScore(User $user): int
    {
        $score = 0;

        // Volunteer applications (30 points)
        $applicationsCount = $user->volunteerApplications()->count();
        $score += min($applicationsCount * 5, 30);

        // Profile views received (20 points)
        $profileViews = $user->profileActivityLogs()
            ->where('activity_type', 'profile_view')
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();
        $score += min($profileViews * 2, 20);

        // Time logs submitted (25 points)
        $timeLogsCount = $user->volunteerTimeLogs()->count();
        $score += min($timeLogsCount * 3, 25);

        // Document uploads (15 points)
        $documentsCount = $user->documents()->count();
        $score += min($documentsCount * 3, 15);

        // Recent activity (10 points)
        $recentActivity = $user->profileActivityLogs()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $score += min($recentActivity, 10);

        return min($score, 100);
    }

    /**
     * Calculate verification score based on verified information.
     */
    private function calculateVerificationScore(User $user): int
    {
        $score = 0;

        // Verified skills (40 points)
        $verifiedSkills = $user->skills()->where('verified', true)->count();
        $totalSkills = $user->skills()->count();
        if ($totalSkills > 0) {
            $score += ($verifiedSkills / $totalSkills) * 40;
        }

        // Verified documents (35 points)
        $verifiedDocs = $user->documents()->where('verification_status', 'verified')->count();
        $totalDocs = $user->documents()->count();
        if ($totalDocs > 0) {
            $score += ($verifiedDocs / $totalDocs) * 35;
        }

        // Verified alumni organizations (15 points)
        $verifiedAlumni = $user->alumniOrganizations()->where('is_verified', true)->count();
        $totalAlumni = $user->alumniOrganizations()->count();
        if ($totalAlumni > 0) {
            $score += ($verifiedAlumni / $totalAlumni) * 15;
        }

        // Email verification (10 points)
        if ($user->hasVerifiedEmail()) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Update user's rank position based on total score.
     */
    private function updateRankPosition(ProfileScore $profileScore): void
    {
        $higherScores = ProfileScore::where('total_score', '>', $profileScore->total_score)->count();
        $profileScore->update(['rank_position' => $higherScores + 1]);
    }

    /**
     * Check and award achievements based on profile score and activities.
     */
    private function checkAndAwardAchievements(User $user, ProfileScore $profileScore): void
    {
        $achievements = $this->getAvailableAchievements($user, $profileScore);
        
        foreach ($achievements as $achievement) {
            $existing = $user->profileAchievements()
                ->where('achievement_type', $achievement['type'])
                ->where('achievement_name', $achievement['name'])
                ->first();
                
            if (!$existing) {
                $newAchievement = $user->profileAchievements()->create([
                    'achievement_type' => $achievement['type'],
                    'achievement_name' => $achievement['name'],
                    'achievement_description' => $achievement['description'],
                    'badge_icon' => $achievement['icon'],
                    'badge_color' => $achievement['color'],
                    'points_awarded' => $achievement['points'],
                    'earned_at' => now(),
                    'is_featured' => $achievement['featured'] ?? false,
                ]);
                
                // Send notification
                $user->notify(new ProfileAchievementEarned($newAchievement));
            }
        }
    }

    /**
     * Get available achievements for a user.
     */
    private function getAvailableAchievements(User $user, ProfileScore $profileScore): array
    {
        $achievements = [];
        $profile = $user->profile;

        // Profile completion achievements
        if ($profileScore->completion_score >= 100) {
            $achievements[] = [
                'type' => 'profile_completion',
                'name' => 'Profile Master',
                'description' => 'Completed 100% of profile information',
                'icon' => 'fas fa-star',
                'color' => 'gold',
                'points' => 100,
                'featured' => true,
            ];
        } elseif ($profileScore->completion_score >= 80) {
            $achievements[] = [
                'type' => 'profile_completion',
                'name' => 'Profile Expert',
                'description' => 'Completed 80% of profile information',
                'icon' => 'fas fa-medal',
                'color' => 'silver',
                'points' => 50,
            ];
        } elseif ($profileScore->completion_score >= 50) {
            $achievements[] = [
                'type' => 'profile_completion',
                'name' => 'Profile Builder',
                'description' => 'Completed 50% of profile information',
                'icon' => 'fas fa-hammer',
                'color' => 'bronze',
                'points' => 25,
            ];
        }

        // Skill verification achievements
        $verifiedSkills = $user->skills()->where('verified', true)->count();
        if ($verifiedSkills >= 10) {
            $achievements[] = [
                'type' => 'skill_verification',
                'name' => 'Skill Master',
                'description' => 'Verified 10+ skills',
                'icon' => 'fas fa-certificate',
                'color' => 'purple',
                'points' => 75,
                'featured' => true,
            ];
        } elseif ($verifiedSkills >= 5) {
            $achievements[] = [
                'type' => 'skill_verification',
                'name' => 'Skill Expert',
                'description' => 'Verified 5+ skills',
                'icon' => 'fas fa-award',
                'color' => 'blue',
                'points' => 40,
            ];
        } elseif ($verifiedSkills >= 1) {
            $achievements[] = [
                'type' => 'skill_verification',
                'name' => 'First Skill Verified',
                'description' => 'Verified your first skill',
                'icon' => 'fas fa-check-circle',
                'color' => 'green',
                'points' => 15,
            ];
        }

        // Document upload achievements
        $documentsCount = $user->documents()->count();
        if ($documentsCount >= 5) {
            $achievements[] = [
                'type' => 'document_upload',
                'name' => 'Document Collector',
                'description' => 'Uploaded 5+ documents',
                'icon' => 'fas fa-folder',
                'color' => 'orange',
                'points' => 30,
            ];
        } elseif ($documentsCount >= 1) {
            $achievements[] = [
                'type' => 'document_upload',
                'name' => 'First Document',
                'description' => 'Uploaded your first document',
                'icon' => 'fas fa-file-upload',
                'color' => 'teal',
                'points' => 10,
            ];
        }

        // Volunteering history achievements
        $historyCount = $user->volunteeringHistory()->count();
        $totalHours = $user->volunteeringHistory()->sum('hours_contributed');
        
        if ($totalHours >= 500) {
            $achievements[] = [
                'type' => 'volunteering_history',
                'name' => 'Volunteer Champion',
                'description' => 'Contributed 500+ volunteer hours',
                'icon' => 'fas fa-trophy',
                'color' => 'gold',
                'points' => 200,
                'featured' => true,
            ];
        } elseif ($totalHours >= 100) {
            $achievements[] = [
                'type' => 'volunteering_history',
                'name' => 'Volunteer Hero',
                'description' => 'Contributed 100+ volunteer hours',
                'icon' => 'fas fa-heart',
                'color' => 'red',
                'points' => 75,
            ];
        } elseif ($historyCount >= 1) {
            $achievements[] = [
                'type' => 'volunteering_history',
                'name' => 'First Volunteer Experience',
                'description' => 'Added your first volunteer experience',
                'icon' => 'fas fa-hands-helping',
                'color' => 'green',
                'points' => 20,
            ];
        }

        // Platform engagement achievements
        if ($profileScore->total_score >= 90) {
            $achievements[] = [
                'type' => 'platform_engagement',
                'name' => 'Platform Elite',
                'description' => 'Achieved 90+ overall profile score',
                'icon' => 'fas fa-crown',
                'color' => 'gold',
                'points' => 150,
                'featured' => true,
            ];
        } elseif ($profileScore->total_score >= 75) {
            $achievements[] = [
                'type' => 'platform_engagement',
                'name' => 'Platform Pro',
                'description' => 'Achieved 75+ overall profile score',
                'icon' => 'fas fa-gem',
                'color' => 'blue',
                'points' => 75,
            ];
        }

        return $achievements;
    }

    /**
     * Get user's achievement statistics.
     */
    public function getAchievementStats(User $user): array
    {
        $achievements = $user->profileAchievements;
        
        return [
            'total_achievements' => $achievements->count(),
            'total_points' => $achievements->sum('points_awarded'),
            'featured_achievements' => $achievements->where('is_featured', true)->count(),
            'recent_achievements' => $achievements->where('earned_at', '>=', now()->subDays(30))->count(),
            'achievements_by_type' => $achievements->groupBy('achievement_type')->map->count(),
            'latest_achievement' => $achievements->sortByDesc('earned_at')->first(),
        ];
    }

    /**
     * Get leaderboard data.
     */
    public function getLeaderboard(int $limit = 10): Collection
    {
        return ProfileScore::with('user.profile')
            ->orderByDesc('total_score')
            ->limit($limit)
            ->get()
            ->map(function ($score, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $score->user,
                    'score' => $score->total_score,
                    'strength_level' => $score->getStrengthLevel(),
                    'achievements_count' => $score->user->profileAchievements()->count(),
                ];
            });
    }

    /**
     * Get profile completion suggestions.
     */
    public function getCompletionSuggestions(User $user): array
    {
        $suggestions = [];
        $profile = $user->profile;

        if (!$profile) {
            return [
                [
                    'title' => 'Create Your Profile',
                    'description' => 'Start by creating your basic profile information.',
                    'action' => 'Create Profile',
                    'url' => route('profile.edit'),
                    'priority' => 'high',
                ]
            ];
        }

        // Basic profile fields
        if (empty($profile->bio)) {
            $suggestions[] = [
                'title' => 'Add Your Bio',
                'description' => 'Tell others about yourself and your interests.',
                'action' => 'Add Bio',
                'url' => route('profile.edit'),
                'priority' => 'high',
            ];
        }

        if (empty($profile->profile_image_url)) {
            $suggestions[] = [
                'title' => 'Upload Profile Photo',
                'description' => 'Add a profile photo to make your profile more personal.',
                'action' => 'Upload Photo',
                'url' => route('profile.edit'),
                'priority' => 'medium',
            ];
        }

        if ($user->skills()->count() === 0) {
            $suggestions[] = [
                'title' => 'Add Your Skills',
                'description' => 'Showcase your abilities and expertise.',
                'action' => 'Add Skills',
                'url' => route('profile.skills'),
                'priority' => 'high',
            ];
        }

        if ($user->volunteeringInterests()->count() === 0) {
            $suggestions[] = [
                'title' => 'Select Volunteering Interests',
                'description' => 'Choose areas where you\'d like to volunteer.',
                'action' => 'Select Interests',
                'url' => route('profile.interests'),
                'priority' => 'high',
            ];
        }

        if ($user->volunteeringHistory()->count() === 0) {
            $suggestions[] = [
                'title' => 'Add Volunteering Experience',
                'description' => 'Share your past volunteering experiences.',
                'action' => 'Add Experience',
                'url' => route('profile.history'),
                'priority' => 'medium',
            ];
        }

        if ($user->documents()->count() === 0) {
            $suggestions[] = [
                'title' => 'Upload Documents',
                'description' => 'Upload certificates or other relevant documents.',
                'action' => 'Upload Documents',
                'url' => route('profile.documents'),
                'priority' => 'low',
            ];
        }

        return $suggestions;
    }
}