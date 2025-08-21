<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ProfileGamificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ProfileGamificationController extends Controller
{
    public function __construct(
        private ProfileGamificationService $gamificationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the profile gamification dashboard.
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        $user->load([
            'profile',
            'profileScore',
            'profileAchievements' => function ($query) {
                $query->orderByDesc('earned_at');
            },
            'skills',
            'volunteeringInterests',
            'volunteeringHistory',
            'documents'
        ]);

        // Calculate or get profile score
        $profileScore = $user->profileScore;
        if (!$profileScore || $profileScore->needsRecalculation()) {
            $profileScore = $this->gamificationService->calculateProfileScore($user);
        }

        // Get achievement statistics
        $achievementStats = $this->gamificationService->getAchievementStats($user);

        // Get completion suggestions
        $suggestions = $this->gamificationService->getCompletionSuggestions($user);

        // Get leaderboard position
        $leaderboard = $this->gamificationService->getLeaderboard(100);
        $userRank = $leaderboard->search(function ($item) use ($user) {
            return $item['user']->id === $user->id;
        });
        $userRank = $userRank !== false ? $userRank + 1 : null;

        return view('client.profile.gamification.dashboard', compact(
            'user',
            'profileScore',
            'achievementStats',
            'suggestions',
            'userRank',
            'leaderboard'
        ));
    }

    /**
     * Display user achievements.
     */
    public function achievements(): View
    {
        $user = auth()->user();
        $achievements = $user->profileAchievements()
            ->orderByDesc('earned_at')
            ->paginate(20);

        $achievementStats = $this->gamificationService->getAchievementStats($user);

        return view('client.profile.gamification.achievements', compact(
            'user',
            'achievements',
            'achievementStats'
        ));
    }

    /**
     * Display the leaderboard.
     */
    public function leaderboard(): View
    {
        $leaderboard = $this->gamificationService->getLeaderboard(50);
        $user = auth()->user();
        
        // Find user's position in extended leaderboard
        $userRank = null;
        if ($user->profileScore) {
            $userRank = $user->profileScore->rank_position;
        }

        return view('client.profile.gamification.leaderboard', compact(
            'leaderboard',
            'user',
            'userRank'
        ));
    }

    /**
     * Recalculate user's profile score.
     */
    public function recalculateScore(): JsonResponse
    {
        $user = auth()->user();
        $profileScore = $this->gamificationService->calculateProfileScore($user);

        return response()->json([
            'success' => true,
            'message' => 'Profile score updated successfully!',
            'data' => [
                'total_score' => $profileScore->total_score,
                'completion_score' => $profileScore->completion_score,
                'quality_score' => $profileScore->quality_score,
                'engagement_score' => $profileScore->engagement_score,
                'verification_score' => $profileScore->verification_score,
                'strength_level' => $profileScore->getStrengthLevel(),
                'strength_color' => $profileScore->getStrengthColor(),
                'rank_position' => $profileScore->rank_position,
                'recommendations' => $profileScore->getRecommendations(),
            ]
        ]);
    }

    /**
     * Get profile completion suggestions.
     */
    public function suggestions(): JsonResponse
    {
        $user = auth()->user();
        $suggestions = $this->gamificationService->getCompletionSuggestions($user);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Get user's achievement statistics.
     */
    public function achievementStats(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->gamificationService->getAchievementStats($user);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Display profile strength analysis.
     */
    public function strengthAnalysis(): View
    {
        $user = auth()->user();
        $user->load(['profile', 'profileScore']);

        $profileScore = $user->profileScore;
        if (!$profileScore || $profileScore->needsRecalculation()) {
            $profileScore = $this->gamificationService->calculateProfileScore($user);
        }

        $recommendations = $profileScore->getRecommendations();
        $suggestions = $this->gamificationService->getCompletionSuggestions($user);

        return view('client.profile.gamification.strength-analysis', compact(
            'user',
            'profileScore',
            'recommendations',
            'suggestions'
        ));
    }

    /**
     * Get profile completion progress data.
     */
    public function completionProgress(): JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $completionData = [
            'overall_percentage' => $profile->profile_completion_percentage,
            'sections' => [
                'basic_info' => [
                    'percentage' => $this->calculateBasicInfoCompletion($user),
                    'label' => 'Basic Information',
                    'missing_fields' => $this->getMissingBasicFields($user)
                ],
                'skills' => [
                    'percentage' => $user->skills()->count() > 0 ? 100 : 0,
                    'label' => 'Skills',
                    'count' => $user->skills()->count(),
                    'verified_count' => $user->skills()->where('verified', true)->count()
                ],
                'interests' => [
                    'percentage' => $user->volunteeringInterests()->count() > 0 ? 100 : 0,
                    'label' => 'Volunteering Interests',
                    'count' => $user->volunteeringInterests()->count()
                ],
                'history' => [
                    'percentage' => $user->volunteeringHistory()->count() > 0 ? 100 : 0,
                    'label' => 'Volunteering History',
                    'count' => $user->volunteeringHistory()->count()
                ],
                'documents' => [
                    'percentage' => $user->documents()->count() > 0 ? 100 : 0,
                    'label' => 'Documents',
                    'count' => $user->documents()->count(),
                    'verified_count' => $user->documents()->where('verification_status', 'verified')->count()
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $completionData
        ]);
    }

    /**
     * Calculate basic information completion percentage.
     */
    private function calculateBasicInfoCompletion($user): int
    {
        $profile = $user->profile;
        if (!$profile) {
            return 0;
        }

        $fields = ['bio', 'date_of_birth', 'phone_number', 'address', 'city_id', 'profile_image_url'];
        $completed = 0;

        foreach ($fields as $field) {
            if (!empty($profile->$field)) {
                $completed++;
            }
        }

        return round(($completed / count($fields)) * 100);
    }

    /**
     * Get missing basic information fields.
     */
    private function getMissingBasicFields($user): array
    {
        $profile = $user->profile;
        if (!$profile) {
            return ['Profile not created'];
        }

        return $profile->getMissingFields();
    }
}