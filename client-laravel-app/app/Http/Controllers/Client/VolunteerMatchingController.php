<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\VolunteerMatchingService;
use App\Services\VolunteerNotificationService;
use App\Models\VolunteeringOpportunity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class VolunteerMatchingController extends Controller
{
    public function __construct(
        private VolunteerMatchingService $matchingService,
        private VolunteerNotificationService $notificationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Get recommended opportunities for the authenticated user
     */
    public function recommendations(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $user = auth()->user();

        $recommendations = $this->matchingService->getRecommendedOpportunities($user, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'opportunities' => $recommendations,
                'total' => $recommendations->count(),
                'profile_completion' => $this->calculateProfileCompletion($user),
            ]
        ]);
    }

    /**
     * Get match explanation for a specific opportunity
     */
    public function matchExplanation(VolunteeringOpportunity $opportunity): JsonResponse
    {
        $user = auth()->user();
        $explanation = $this->matchingService->getMatchExplanation($user, $opportunity);

        return response()->json([
            'success' => true,
            'data' => $explanation
        ]);
    }

    /**
     * Get trending opportunities
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $trending = $this->matchingService->getTrendingOpportunities($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'opportunities' => $trending,
                'total' => $trending->count(),
            ]
        ]);
    }

    /**
     * Get similar volunteers for an opportunity (for organizations)
     */
    public function similarVolunteers(VolunteeringOpportunity $opportunity, Request $request): JsonResponse
    {
        // Check if user can view this opportunity's data
        $this->authorize('view', $opportunity);

        $limit = $request->integer('limit', 10);
        $similarVolunteers = $this->matchingService->findSimilarVolunteers($opportunity, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'volunteers' => $similarVolunteers,
                'total' => $similarVolunteers->count(),
            ]
        ]);
    }

    /**
     * Update user notification preferences
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $request->validate([
            'volunteer_notifications_enabled' => 'boolean',
            'trending_notifications_enabled' => 'boolean',
            'digest_notifications_enabled' => 'boolean',
            'immediate_notifications_enabled' => 'boolean',
        ]);

        $user = auth()->user();
        $this->notificationService->updateNotificationPreferences($user, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully'
        ]);
    }

    /**
     * Get user notification preferences
     */
    public function notificationPreferences(): JsonResponse
    {
        $user = auth()->user();
        $preferences = $this->notificationService->getNotificationPreferences($user);

        return response()->json([
            'success' => true,
            'data' => $preferences
        ]);
    }

    /**
     * Trigger personalized recommendations (for testing or manual trigger)
     */
    public function triggerRecommendations(): JsonResponse
    {
        $user = auth()->user();
        $this->notificationService->sendProfileBasedRecommendations($user);

        return response()->json([
            'success' => true,
            'message' => 'Personalized recommendations sent'
        ]);
    }

    /**
     * Get profile completion status and suggestions
     */
    public function profileCompletion(): JsonResponse
    {
        $user = auth()->user();
        $completion = $this->calculateProfileCompletion($user);
        $suggestions = $this->getProfileCompletionSuggestions($user);

        return response()->json([
            'success' => true,
            'data' => [
                'completion_percentage' => $completion,
                'suggestions' => $suggestions,
                'is_complete' => $completion >= 80,
            ]
        ]);
    }

    /**
     * Clear user's recommendation cache
     */
    public function clearCache(): JsonResponse
    {
        $user = auth()->user();
        $this->matchingService->clearUserRecommendations($user);

        return response()->json([
            'success' => true,
            'message' => 'Recommendation cache cleared'
        ]);
    }

    /**
     * Show matching preferences page
     */
    public function preferences(): View
    {
        $user = auth()->user();
        $preferences = $this->notificationService->getNotificationPreferences($user);
        $profileCompletion = $this->calculateProfileCompletion($user);
        $suggestions = $this->getProfileCompletionSuggestions($user);

        return view('client.volunteering.matching-preferences', compact(
            'preferences',
            'profileCompletion',
            'suggestions'
        ));
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($user): int
    {
        $score = 0;
        
        if ($user->name) $score += 15;
        if ($user->email) $score += 15;
        if ($user->bio) $score += 10;
        if ($user->city_id) $score += 10;
        if ($user->country_id) $score += 10;
        if ($user->volunteeringInterests()->count() > 0) $score += 20;
        if ($user->skills()->count() > 0) $score += 20;
        
        return $score;
    }

    /**
     * Get profile completion suggestions
     */
    private function getProfileCompletionSuggestions($user): array
    {
        $suggestions = [];
        
        if (!$user->bio) {
            $suggestions[] = [
                'field' => 'bio',
                'message' => 'Add a bio to help organizations understand your background',
                'points' => 10,
                'action' => route('client.profile.edit')
            ];
        }
        
        if (!$user->city_id || !$user->country_id) {
            $suggestions[] = [
                'field' => 'location',
                'message' => 'Add your location to find local volunteer opportunities',
                'points' => 20,
                'action' => route('client.profile.edit')
            ];
        }
        
        if ($user->volunteeringInterests()->count() === 0) {
            $suggestions[] = [
                'field' => 'interests',
                'message' => 'Add your volunteering interests to get better recommendations',
                'points' => 20,
                'action' => route('client.profile.interests')
            ];
        }
        
        if ($user->skills()->count() === 0) {
            $suggestions[] = [
                'field' => 'skills',
                'message' => 'Add your skills to match with opportunities that need your expertise',
                'points' => 20,
                'action' => route('client.profile.skills')
            ];
        }
        
        return $suggestions;
    }
}