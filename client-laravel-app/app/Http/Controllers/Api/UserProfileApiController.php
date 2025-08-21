<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class UserProfileApiController extends Controller
{
    protected UserProfileService $profileService;

    public function __construct(UserProfileService $profileService)
    {
        $this->profileService = $profileService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user profile data.
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        $user->load([
            'profile',
            'skills',
            'volunteeringInterests.category',
            'userVolunteeringHistory',
            'documents',
            'userAlumniOrganizations',
            'platformInterests'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'statistics' => $this->profileService->getUserStatistics($user),
                'registration_progress' => $this->profileService->getRegistrationProgress($user)
            ]
        ]);
    }

    /**
     * Update user profile via API.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'linkedin_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            'is_public' => 'boolean',
        ]);

        try {
            $user = Auth::user();
            
            if ($user->profile) {
                $profile = $this->profileService->updateProfile($user->profile, $validated);
            } else {
                $profile = $this->profileService->createProfile($user, $validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'data' => $profile->load('user')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user skills via API.
     */
    public function skills(): JsonResponse
    {
        $user = Auth::user();
        $skills = $user->skills()->orderBy('skill_name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $skills
        ]);
    }

    /**
     * Add skill via API.
     */
    public function addSkill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'skill_name' => 'required|string|max:100',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
        ]);

        try {
            $user = Auth::user();
            $skill = $this->profileService->addSkill($user, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Skill added successfully!',
                'data' => $skill
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update skill via API.
     */
    public function updateSkill(Request $request, UserSkill $skill): JsonResponse
    {
        $this->authorize('update', $skill);
        
        $validated = $request->validate([
            'skill_name' => 'required|string|max:100',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
        ]);

        try {
            $updatedSkill = $this->profileService->updateSkill($skill, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Skill updated successfully!',
                'data' => $updatedSkill
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete skill via API.
     */
    public function deleteSkill(UserSkill $skill): JsonResponse
    {
        $this->authorize('delete', $skill);
        
        try {
            $this->profileService->removeSkill($skill);
            
            return response()->json([
                'success' => true,
                'message' => 'Skill removed successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get volunteering interests via API.
     */
    public function interests(): JsonResponse
    {
        $user = Auth::user();
        $interests = $user->volunteeringInterests()->with('category')->get();
        
        return response()->json([
            'success' => true,
            'data' => $interests
        ]);
    }

    /**
     * Add volunteering interest via API.
     */
    public function addInterest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:volunteering_categories,id',
            'interest_level' => 'required|in:low,medium,high',
        ]);

        try {
            $user = Auth::user();
            $interest = $this->profileService->addVolunteeringInterest(
                $user, 
                $validated['category_id'], 
                $validated['interest_level']
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Interest added successfully!',
                'data' => $interest->load('category')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete volunteering interest via API.
     */
    public function deleteInterest(UserVolunteeringInterest $interest): JsonResponse
    {
        $this->authorize('delete', $interest);
        
        try {
            $this->profileService->removeVolunteeringInterest($interest);
            
            return response()->json([
                'success' => true,
                'message' => 'Interest removed successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get volunteering history via API.
     */
    public function history(): JsonResponse
    {
        $user = Auth::user();
        $history = $user->userVolunteeringHistory()
            ->with('organization')
            ->orderByDesc('start_date')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Add volunteering history via API.
     */
    public function addHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'organization_name' => 'required_without:organization_id|string|max:255',
            'role_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'hours_contributed' => 'nullable|integer|min:0',
            'skills_gained' => 'nullable|array',
            'skills_gained.*' => 'string|max:100',
            'reference_contact' => 'nullable|string|max:255',
            'reference_email' => 'nullable|email|max:255',
            'reference_phone' => 'nullable|string|max:20',
            'is_current' => 'boolean',
        ]);

        try {
            $user = Auth::user();
            $history = $this->profileService->addVolunteeringHistory($user, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Volunteering history added successfully!',
                'data' => $history->load('organization')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update volunteering history via API.
     */
    public function updateHistory(Request $request, UserVolunteeringHistory $history): JsonResponse
    {
        $this->authorize('update', $history);
        
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'organization_name' => 'required_without:organization_id|string|max:255',
            'role_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'hours_contributed' => 'nullable|integer|min:0',
            'skills_gained' => 'nullable|array',
            'skills_gained.*' => 'string|max:100',
            'reference_contact' => 'nullable|string|max:255',
            'reference_email' => 'nullable|email|max:255',
            'reference_phone' => 'nullable|string|max:20',
            'is_current' => 'boolean',
        ]);

        try {
            $updatedHistory = $this->profileService->updateVolunteeringHistory($history, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Volunteering history updated successfully!',
                'data' => $updatedHistory->load('organization')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete volunteering history via API.
     */
    public function deleteHistory(UserVolunteeringHistory $history): JsonResponse
    {
        $this->authorize('delete', $history);
        
        try {
            $history->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Volunteering history deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get documents via API.
     */
    public function documents(): JsonResponse
    {
        $user = Auth::user();
        $documents = $user->documents()->orderByDesc('created_at')->get();
        
        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * Upload document via API.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'document_type' => 'required|in:resume,certificate,id,transcript,other',
        ]);

        try {
            $user = Auth::user();
            $document = $this->profileService->uploadDocument(
                $user, 
                $request->file('document'), 
                $validated['document_type']
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully!',
                'data' => $document
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete document via API.
     */
    public function deleteDocument(UserDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);
        
        try {
            // Delete file from storage
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }
            
            $document->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get alumni organizations via API.
     */
    public function alumni(): JsonResponse
    {
        $user = Auth::user();
        $alumni = $user->userAlumniOrganizations()
            ->with('organization')
            ->orderByDesc('graduation_year')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $alumni
        ]);
    }

    /**
     * Add alumni organization via API.
     */
    public function addAlumni(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'organization_name' => 'required_without:organization_id|string|max:255',
            'degree' => 'nullable|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'status' => 'required|in:student,graduate,faculty,staff',
        ]);

        try {
            $user = Auth::user();
            $alumni = $this->profileService->addAlumniOrganization($user, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Alumni organization added successfully!',
                'data' => $alumni->load('organization')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update alumni organization via API.
     */
    public function updateAlumni(Request $request, UserAlumniOrganization $alumni): JsonResponse
    {
        $this->authorize('update', $alumni);
        
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'organization_name' => 'required_without:organization_id|string|max:255',
            'degree' => 'nullable|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'status' => 'required|in:student,graduate,faculty,staff',
        ]);

        try {
            $updatedAlumni = $this->profileService->updateAlumniOrganization($alumni, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Alumni organization updated successfully!',
                'data' => $updatedAlumni->load('organization')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete alumni organization via API.
     */
    public function deleteAlumni(UserAlumniOrganization $alumni): JsonResponse
    {
        $this->authorize('delete', $alumni);
        
        try {
            $alumni->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Alumni organization removed successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get registration progress via API.
     */
    public function registrationProgress(): JsonResponse
    {
        $user = Auth::user();
        $progress = $this->profileService->getRegistrationProgress($user);
        
        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    /**
     * Complete registration step via API.
     */
    public function completeStep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'step_name' => 'required|in:basic_info,profile_details,interests,verification',
            'step_data' => 'nullable|array',
        ]);

        try {
            $user = Auth::user();
            $this->profileService->completeRegistrationStep(
                $user, 
                $validated['step_name'], 
                $validated['step_data'] ?? []
            );
            
            $progress = $this->profileService->getRegistrationProgress($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Step completed successfully!',
                'data' => $progress
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user statistics via API.
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        $statistics = $this->profileService->getUserStatistics($user);
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get matching opportunities via API.
     */
    public function matchingOpportunities(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $user = Auth::user();
        $opportunities = $this->profileService->getMatchingOpportunities($user, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $opportunities
        ]);
    }

    /**
     * Export user profile data via API.
     */
    public function export(): JsonResponse
    {
        try {
            $user = Auth::user();
            $profileData = $this->profileService->exportUserProfile($user);
            
            return response()->json([
                'success' => true,
                'data' => $profileData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users via API.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'filters' => 'nullable|array',
            'filters.skills' => 'nullable|array',
            'filters.interests' => 'nullable|array',
            'filters.location' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $results = $this->profileService->searchUsers(
                $validated['query'],
                $validated['filters'] ?? [],
                $validated['limit'] ?? 20
            );
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}