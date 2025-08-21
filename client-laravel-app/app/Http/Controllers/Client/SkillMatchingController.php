<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillEndorsement;
use App\Services\SkillsMatchingService;
use App\Services\InterestsMatchingService;
use App\Services\SkillVerificationService;
use App\Services\SkillImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SkillMatchingController extends Controller
{
    public function __construct(
        private SkillsMatchingService $skillsMatchingService,
        private InterestsMatchingService $interestsMatchingService,
        private SkillVerificationService $skillVerificationService,
        private SkillImportService $skillImportService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show skill matching dashboard
     */
    public function index(): View
    {
        $user = auth()->user();
        
        // Get matching opportunities
        $matchingOpportunities = $this->skillsMatchingService->findMatchingOpportunities($user, [
            'limit' => 10,
            'min_score' => 60
        ]);

        // Get skill gap analysis
        $skillGaps = $this->skillsMatchingService->analyzeSkillGaps($user);

        // Get trending skills
        $trendingSkills = $this->skillsMatchingService->getTrendingSkills(10);

        // Get content recommendations
        $contentRecommendations = $this->interestsMatchingService->getContentRecommendations($user, [
            'limit' => 15,
            'types' => ['opportunities', 'resources']
        ]);

        // Get skill suggestions
        $skillSuggestions = $this->skillImportService->suggestSkills($user, 10);

        return view('client.skills.matching.index', compact(
            'matchingOpportunities',
            'skillGaps',
            'trendingSkills',
            'contentRecommendations',
            'skillSuggestions'
        ));
    }

    /**
     * Get matching opportunities for user's skills
     */
    public function getMatchingOpportunities(Request $request): JsonResponse
    {
        $user = auth()->user();
        $options = [
            'limit' => $request->input('limit', 20),
            'min_score' => $request->input('min_score', 60),
            'include_partial' => $request->boolean('include_partial', true)
        ];

        $opportunities = $this->skillsMatchingService->findMatchingOpportunities($user, $options);

        return response()->json([
            'success' => true,
            'opportunities' => $opportunities->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'title' => $opportunity->title,
                    'organization' => $opportunity->organization->name,
                    'category' => $opportunity->category->name,
                    'location' => $opportunity->formatted_location,
                    'skill_match_score' => $opportunity->skill_match_score,
                    'matched_skills' => $opportunity->matched_skills,
                    'missing_skills' => $opportunity->missing_skills,
                    'url' => route('volunteering.show', $opportunity)
                ];
            })
        ]);
    }

    /**
     * Get skill gap analysis
     */
    public function getSkillGaps(): JsonResponse
    {
        $user = auth()->user();
        $analysis = $this->skillsMatchingService->analyzeSkillGaps($user);

        return response()->json([
            'success' => true,
            'analysis' => $analysis
        ]);
    }

    /**
     * Get trending skills
     */
    public function getTrendingSkills(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $skills = $this->skillsMatchingService->getTrendingSkills($limit);

        return response()->json([
            'success' => true,
            'skills' => $skills
        ]);
    }

    /**
     * Get skill suggestions for user
     */
    public function getSkillSuggestions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->input('limit', 20);
        
        $suggestions = $this->skillImportService->suggestSkills($user, $limit);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Get content recommendations based on interests
     */
    public function getContentRecommendations(Request $request): JsonResponse
    {
        $user = auth()->user();
        $options = [
            'limit' => $request->input('limit', 20),
            'types' => $request->input('types', ['opportunities', 'events', 'resources'])
        ];

        $recommendations = $this->interestsMatchingService->getContentRecommendations($user, $options);

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations
        ]);
    }

    /**
     * Find users with similar interests
     */
    public function getSimilarUsers(Request $request): JsonResponse
    {
        $user = auth()->user();
        $options = [
            'limit' => $request->input('limit', 20),
            'min_similarity' => $request->input('min_similarity', 0.3)
        ];

        $similarUsers = $this->interestsMatchingService->findUsersWithSimilarInterests($user, $options);

        return response()->json([
            'success' => true,
            'users' => $similarUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile_image' => $user->profile?->profile_image_url,
                    'similarity_score' => $user->interest_similarity,
                    'common_interests' => $user->common_interests,
                    'profile_url' => route('profile.show', $user)
                ];
            })
        ]);
    }

    /**
     * Request skill endorsement
     */
    public function requestEndorsement(Request $request, UserSkill $skill): JsonResponse
    {
        $request->validate([
            'endorser_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500'
        ]);

        try {
            $endorser = User::findOrFail($request->endorser_id);
            
            $endorsement = $this->skillVerificationService->requestEndorsement(
                $skill,
                $endorser,
                $request->message
            );

            return response()->json([
                'success' => true,
                'message' => 'Endorsement request sent successfully',
                'endorsement' => $endorsement
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get potential endorsers for a skill
     */
    public function getPotentialEndorsers(UserSkill $skill): JsonResponse
    {
        $endorsers = $this->skillVerificationService->getPotentialEndorsers($skill, 20);

        return response()->json([
            'success' => true,
            'endorsers' => $endorsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile_image' => $user->profile?->profile_image_url,
                    'endorser_score' => $user->endorser_score,
                    'relevant_skill' => $user->skills->first(),
                    'profile_url' => route('profile.show', $user)
                ];
            })
        ]);
    }

    /**
     * Respond to endorsement request
     */
    public function respondToEndorsement(Request $request, SkillEndorsement $endorsement): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comment' => 'nullable|string|max:500',
            'reason' => 'required_if:action,reject|string|max:500'
        ]);

        try {
            if ($request->action === 'approve') {
                $this->skillVerificationService->approveEndorsement($endorsement, $request->comment);
                $message = 'Endorsement approved successfully';
            } else {
                $this->skillVerificationService->rejectEndorsement($endorsement, $request->reason);
                $message = 'Endorsement rejected';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get endorsement statistics for a skill
     */
    public function getEndorsementStats(UserSkill $skill): JsonResponse
    {
        $stats = $this->skillVerificationService->getEndorsementStats($skill);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Import skills from external platforms
     */
    public function importSkills(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|in:linkedin,resume,structured',
            'data' => 'required'
        ]);

        $user = auth()->user();

        try {
            switch ($request->source) {
                case 'linkedin':
                    $result = $this->skillImportService->importFromLinkedIn($user, $request->data);
                    break;
                case 'resume':
                    $result = $this->skillImportService->importFromResumeText($user, $request->data);
                    break;
                case 'structured':
                    $result = $this->skillImportService->importFromStructuredData($user, $request->data);
                    break;
                default:
                    throw new \Exception('Invalid import source');
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get match explanation for debugging
     */
    public function getMatchExplanation(Request $request): JsonResponse
    {
        $request->validate([
            'opportunity_id' => 'required|exists:volunteering_opportunities,id'
        ]);

        $user = auth()->user();
        $opportunity = \App\Models\VolunteeringOpportunity::findOrFail($request->opportunity_id);

        // This would use the existing VolunteerMatchingService
        $matchingService = app(\App\Services\VolunteerMatchingService::class);
        $explanation = $matchingService->getMatchExplanation($user, $opportunity);

        return response()->json([
            'success' => true,
            'explanation' => $explanation
        ]);
    }
}