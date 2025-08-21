<?php

namespace App\Services;

use App\Models\ProfileActivityLog;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfileAnalyticsService
{
    /**
     * Get comprehensive profile analytics for a user
     */
    public function getUserProfileAnalytics(User $user, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        return [
            'profile_views' => $this->getProfileViews($user, $startDate),
            'unique_viewers' => $this->getUniqueViewers($user, $startDate),
            'activity_breakdown' => $this->getActivityBreakdown($user, $startDate),
            'viewer_demographics' => $this->getViewerDemographics($user, $startDate),
            'engagement_trends' => $this->getEngagementTrends($user, $startDate),
            'profile_strength' => $this->calculateProfileStrength($user),
            'recommendations' => $this->getProfileRecommendations($user),
        ];
    }

    /**
     * Get total profile views for a user
     */
    public function getProfileViews(User $user, Carbon $startDate): int
    {
        return ProfileActivityLog::forUser($user->id)
            ->ofType('view')
            ->where('created_at', '>=', $startDate)
            ->count();
    }

    /**
     * Get unique viewers count
     */
    public function getUniqueViewers(User $user, Carbon $startDate): int
    {
        return ProfileActivityLog::forUser($user->id)
            ->ofType('view')
            ->where('created_at', '>=', $startDate)
            ->distinct('viewer_id')
            ->count();
    }

    /**
     * Get activity breakdown by type
     */
    public function getActivityBreakdown(User $user, Carbon $startDate): Collection
    {
        return ProfileActivityLog::forUser($user->id)
            ->where('created_at', '>=', $startDate)
            ->select('activity_type', DB::raw('COUNT(*) as count'))
            ->groupBy('activity_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->activity_type => $item->count];
            });
    }

    /**
     * Get viewer demographics
     */
    public function getViewerDemographics(User $user, Carbon $startDate): array
    {
        $viewers = ProfileActivityLog::forUser($user->id)
            ->ofType('view')
            ->where('created_at', '>=', $startDate)
            ->with(['viewer.profile'])
            ->get()
            ->pluck('viewer')
            ->filter()
            ->unique('id');

        $demographics = [
            'total_viewers' => $viewers->count(),
            'by_country' => [],
            'by_city' => [],
            'by_verification_status' => [
                'verified' => 0,
                'unverified' => 0,
            ],
        ];

        foreach ($viewers as $viewer) {
            if ($viewer->profile) {
                // Country demographics
                $country = $viewer->profile->country ?? 'Unknown';
                $demographics['by_country'][$country] = ($demographics['by_country'][$country] ?? 0) + 1;

                // City demographics
                $city = $viewer->profile->city ?? 'Unknown';
                $demographics['by_city'][$city] = ($demographics['by_city'][$city] ?? 0) + 1;

                // Verification status
                if ($viewer->profile->is_verified) {
                    $demographics['by_verification_status']['verified']++;
                } else {
                    $demographics['by_verification_status']['unverified']++;
                }
            }
        }

        // Sort by count
        arsort($demographics['by_country']);
        arsort($demographics['by_city']);

        // Limit to top 10
        $demographics['by_country'] = array_slice($demographics['by_country'], 0, 10, true);
        $demographics['by_city'] = array_slice($demographics['by_city'], 0, 10, true);

        return $demographics;
    }

    /**
     * Get engagement trends over time
     */
    public function getEngagementTrends(User $user, Carbon $startDate): Collection
    {
        return ProfileActivityLog::forUser($user->id)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_activities'),
                DB::raw('COUNT(CASE WHEN activity_type = "view" THEN 1 END) as views'),
                DB::raw('COUNT(CASE WHEN activity_type = "contact" THEN 1 END) as contacts'),
                DB::raw('COUNT(CASE WHEN activity_type = "endorse" THEN 1 END) as endorsements'),
                DB::raw('COUNT(CASE WHEN activity_type = "connect" THEN 1 END) as connections')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Calculate profile strength score
     */
    public function calculateProfileStrength(User $user): array
    {
        $profile = $user->profile;
        if (!$profile) {
            return [
                'score' => 0,
                'max_score' => 100,
                'percentage' => 0,
                'breakdown' => [],
                'recommendations' => ['Create your profile to get started'],
            ];
        }

        $score = 0;
        $maxScore = 100;
        $breakdown = [];

        // Basic information (20 points)
        $basicScore = 0;
        if ($profile->first_name && $profile->last_name) $basicScore += 5;
        if ($profile->bio) $basicScore += 5;
        if ($profile->phone) $basicScore += 3;
        if ($profile->city && $profile->country) $basicScore += 4;
        if ($profile->date_of_birth) $basicScore += 3;
        
        $breakdown['basic_info'] = ['score' => $basicScore, 'max' => 20];
        $score += $basicScore;

        // Profile image (10 points)
        $imageScore = $profile->profileImages()->where('is_primary', true)->exists() ? 10 : 0;
        $breakdown['profile_image'] = ['score' => $imageScore, 'max' => 10];
        $score += $imageScore;

        // Skills (15 points)
        $skillsCount = $user->skills()->count();
        $skillsScore = min($skillsCount * 3, 15);
        $breakdown['skills'] = ['score' => $skillsScore, 'max' => 15];
        $score += $skillsScore;

        // Volunteering history (20 points)
        $historyCount = $user->volunteeringHistory()->count();
        $historyScore = min($historyCount * 5, 20);
        $breakdown['volunteering_history'] = ['score' => $historyScore, 'max' => 20];
        $score += $historyScore;

        // Documents (15 points)
        $documentsCount = $user->documents()->count();
        $documentsScore = min($documentsCount * 3, 15);
        $breakdown['documents'] = ['score' => $documentsScore, 'max' => 15];
        $score += $documentsScore;

        // Interests (10 points)
        $interestsCount = $user->platformInterests()->count();
        $interestsScore = min($interestsCount * 2, 10);
        $breakdown['interests'] = ['score' => $interestsScore, 'max' => 10];
        $score += $interestsScore;

        // Verification (10 points)
        $verificationScore = $profile->is_verified ? 10 : 0;
        $breakdown['verification'] = ['score' => $verificationScore, 'max' => 10];
        $score += $verificationScore;

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'breakdown' => $breakdown,
            'recommendations' => $this->getProfileRecommendations($user),
        ];
    }

    /**
     * Get profile improvement recommendations
     */
    public function getProfileRecommendations(User $user): array
    {
        $recommendations = [];
        $profile = $user->profile;

        if (!$profile) {
            return ['Create your profile to get started with the platform'];
        }

        // Basic info recommendations
        if (!$profile->bio || strlen($profile->bio) < 50) {
            $recommendations[] = 'Add a detailed bio to help others understand your background and interests';
        }

        if (!$profile->phone) {
            $recommendations[] = 'Add your phone number to make it easier for organizations to contact you';
        }

        if (!$profile->city || !$profile->country) {
            $recommendations[] = 'Add your location to find local volunteering opportunities';
        }

        // Profile image
        if (!$profile->profileImages()->where('is_primary', true)->exists()) {
            $recommendations[] = 'Upload a profile picture to make your profile more personal and trustworthy';
        }

        // Skills
        $skillsCount = $user->skills()->count();
        if ($skillsCount < 3) {
            $recommendations[] = 'Add more skills to your profile to match with relevant opportunities';
        }

        // Volunteering history
        $historyCount = $user->volunteeringHistory()->count();
        if ($historyCount === 0) {
            $recommendations[] = 'Add your volunteering history to showcase your experience';
        }

        // Documents
        $documentsCount = $user->documents()->count();
        if ($documentsCount === 0) {
            $recommendations[] = 'Upload documents like your CV or certificates to strengthen your profile';
        }

        // Interests
        $interestsCount = $user->platformInterests()->count();
        if ($interestsCount < 3) {
            $recommendations[] = 'Select more interests to receive better opportunity recommendations';
        }

        // Verification
        if (!$profile->is_verified) {
            $recommendations[] = 'Complete profile verification to increase trust with organizations';
        }

        return $recommendations;
    }

    /**
     * Get profile comparison data
     */
    public function getProfileComparison(User $user): array
    {
        $userStrength = $this->calculateProfileStrength($user);
        
        // Get average profile completion for comparison
        $avgCompletion = UserProfile::avg('profile_completion_percentage') ?? 0;
        
        // Get user's ranking
        $betterProfiles = UserProfile::where('profile_completion_percentage', '>', $userStrength['percentage'])->count();
        $totalProfiles = UserProfile::count();
        $ranking = $betterProfiles + 1;
        $percentile = $totalProfiles > 0 ? round((($totalProfiles - $betterProfiles) / $totalProfiles) * 100) : 0;

        return [
            'user_completion' => $userStrength['percentage'],
            'average_completion' => round($avgCompletion),
            'ranking' => $ranking,
            'total_profiles' => $totalProfiles,
            'percentile' => $percentile,
            'above_average' => $userStrength['percentage'] > $avgCompletion,
        ];
    }

    /**
     * Get profile activity summary
     */
    public function getActivitySummary(User $user, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $activities = ProfileActivityLog::forUser($user->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        $summary = [
            'total_activities' => $activities->count(),
            'views' => $activities->where('activity_type', 'view')->count(),
            'contacts' => $activities->where('activity_type', 'contact')->count(),
            'endorsements' => $activities->where('activity_type', 'endorse')->count(),
            'connections' => $activities->where('activity_type', 'connect')->count(),
            'searches' => $activities->where('activity_type', 'search')->count(),
        ];

        // Calculate engagement rate
        $summary['engagement_rate'] = $summary['views'] > 0 
            ? round((($summary['contacts'] + $summary['endorsements'] + $summary['connections']) / $summary['views']) * 100, 2)
            : 0;

        return $summary;
    }

    /**
     * Track profile activity
     */
    public function trackActivity(User $profileUser, ?User $viewer, string $activityType, array $metadata = []): void
    {
        // Don't track self-activities for certain types
        if ($viewer && $profileUser->id === $viewer->id && in_array($activityType, ['view'])) {
            return;
        }

        ProfileActivityLog::create([
            'user_id' => $profileUser->id,
            'viewer_id' => $viewer?->id,
            'activity_type' => $activityType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'metadata' => array_merge([
                'timestamp' => now()->toISOString(),
                'route' => request()->route()?->getName(),
            ], $metadata),
        ]);
    }

    /**
     * Get popular profiles based on activity
     */
    public function getPopularProfiles(int $days = 30, int $limit = 10): Collection
    {
        $startDate = Carbon::now()->subDays($days);

        return ProfileActivityLog::select('user_id', DB::raw('COUNT(*) as activity_count'))
            ->where('created_at', '>=', $startDate)
            ->with(['user.profile'])
            ->groupBy('user_id')
            ->orderByDesc('activity_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate analytics report
     */
    public function generateReport(User $user, int $days = 30): array
    {
        $analytics = $this->getUserProfileAnalytics($user, $days);
        $comparison = $this->getProfileComparison($user);
        $activitySummary = $this->getActivitySummary($user, $days);

        return [
            'user' => $user->load('profile'),
            'period' => [
                'days' => $days,
                'start_date' => Carbon::now()->subDays($days)->toDateString(),
                'end_date' => Carbon::now()->toDateString(),
            ],
            'analytics' => $analytics,
            'comparison' => $comparison,
            'activity_summary' => $activitySummary,
            'generated_at' => now()->toISOString(),
        ];
    }
}