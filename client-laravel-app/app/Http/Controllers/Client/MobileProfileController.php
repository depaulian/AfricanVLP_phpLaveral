<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadImageRequest;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\UserDocument;
use App\Models\VolunteeringCategory;
use App\Services\UserProfileService;
use App\Services\DocumentManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class MobileProfileController extends Controller
{
    public function __construct(
        private UserProfileService $profileService,
        private DocumentManagementService $documentService
    ) {
        $this->middleware('auth');
        $this->middleware('mobile')->except(['uploadImage', 'uploadDocument']);
    }

    /**
     * Show mobile profile dashboard
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        $user->load([
            'profile', 
            'skills', 
            'volunteeringInterests.category', 
            'volunteeringHistory' => function($query) {
                $query->latest()->take(5);
            }
        ]);

        $statistics = $this->profileService->getUserStatistics($user);
        $matchingOpportunities = $this->profileService->getMatchingOpportunities($user, 5);

        return view('client.profile.mobile.dashboard', compact(
            'user', 
            'statistics', 
            'matchingOpportunities'
        ));
    }

    /**
     * Show mobile profile edit form
     */
    public function edit(): View
    {
        $user = auth()->user();
        $user->load(['profile']);

        $cities = City::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('client.profile.mobile.edit', compact(
            'user', 
            'cities', 
            'countries'
        ));
    }

    /**
     * Update profile via mobile interface
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        
        // Update user basic info
        $user->update($request->only(['name', 'email']));

        // Update or create profile
        $profileData = $request->except(['name', 'email']);
        
        if ($user->profile) {
            $this->profileService->updateProfile($user->profile, $profileData);
        } else {
            $this->profileService->createProfile($user, $profileData);
        }

        return redirect()->route('profile.mobile.dashboard')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Upload profile image via mobile
     */
    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $file = $request->file('image');

            // Validate file size for mobile (max 5MB)
            if ($file->getSize() > 5 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image size must be less than 5MB'
                ], 422);
            }

            // Process and upload image
            $path = $this->profileService->uploadProfileImage($user, $file);
            
            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::url($path),
                'message' => 'Profile image updated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image. Please try again.'
            ], 500);
        }
    }

    /**
     * Show mobile documents interface
     */
    public function documents(): View
    {
        $user = auth()->user();
        $user->load(['documents' => function($query) {
            $query->latest();
        }]);

        return view('client.profile.mobile.documents', compact('user'));
    }

    /**
     * Upload document via mobile
     */
    public function uploadDocument(UploadDocumentRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $file = $request->file('document');
            $documentType = $request->input('document_type');

            // Validate file size for mobile (max 10MB)
            if ($file->getSize() > 10 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document size must be less than 10MB'
                ], 422);
            }

            // Upload document
            $document = $this->documentService->uploadDocument($user, $file, $documentType);
            
            return response()->json([
                'success' => true,
                'document' => [
                    'id' => $document->id,
                    'name' => $document->file_name,
                    'type' => $document->document_type,
                    'size' => $document->file_size_human,
                    'status' => $document->verification_status
                ],
                'message' => 'Document uploaded successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document. Please try again.'
            ], 500);
        }
    }

    /**
     * View document (mobile-optimized)
     */
    public function viewDocument(UserDocument $document): View
    {
        $this->authorize('view', $document);

        return view('client.profile.mobile.document-viewer', compact('document'));
    }

    /**
     * Download document
     */
    public function downloadDocument(UserDocument $document)
    {
        $this->authorize('view', $document);

        if (!Storage::exists($document->file_path)) {
            abort(404, 'Document not found');
        }

        return Storage::download($document->file_path, $document->file_name);
    }

    /**
     * Delete document
     */
    public function deleteDocument(UserDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);

        try {
            $this->documentService->deleteDocument($document);
            
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document'
            ], 500);
        }
    }

    /**
     * Show mobile skills management
     */
    public function skills(): View
    {
        $user = auth()->user();
        $user->load(['skills' => function($query) {
            $query->orderBy('skill_name');
        }]);

        return view('client.profile.mobile.skills', compact('user'));
    }

    /**
     * Add skill via mobile
     */
    public function addSkill(Request $request): JsonResponse
    {
        $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50'
        ]);

        try {
            $user = auth()->user();
            
            // Check if skill already exists
            if ($user->skills()->where('skill_name', $request->skill_name)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have this skill in your profile'
                ], 422);
            }

            $skill = $this->profileService->addSkill($user, $request->validated());
            
            return response()->json([
                'success' => true,
                'skill' => [
                    'id' => $skill->id,
                    'name' => $skill->skill_name,
                    'proficiency' => $skill->proficiency_level,
                    'experience' => $skill->years_experience,
                    'verified' => $skill->verified
                ],
                'message' => 'Skill added successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add skill'
            ], 500);
        }
    }

    /**
     * Show mobile volunteering history
     */
    public function history(): View
    {
        $user = auth()->user();
        $user->load(['volunteeringHistory' => function($query) {
            $query->orderByDesc('start_date');
        }]);

        return view('client.profile.mobile.history', compact('user'));
    }

    /**
     * Show mobile profile sharing interface
     */
    public function share(): View
    {
        $user = auth()->user();
        $user->load(['profile', 'skills', 'volunteeringHistory']);

        $shareUrl = route('profile.public', $user);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($shareUrl);

        return view('client.profile.mobile.share', compact('user', 'shareUrl', 'qrCodeUrl'));
    }

    /**
     * Get profile data for offline sync
     */
    public function syncData(): JsonResponse
    {
        $user = auth()->user();
        $user->load([
            'profile',
            'skills',
            'volunteeringInterests.category',
            'volunteeringHistory',
            'documents' => function($query) {
                $query->select('id', 'user_id', 'document_type', 'file_name', 'verification_status', 'created_at');
            }
        ]);

        $statistics = $this->profileService->getUserStatistics($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $user->profile,
                'skills' => $user->skills,
                'interests' => $user->volunteeringInterests,
                'history' => $user->volunteeringHistory,
                'documents' => $user->documents,
                'statistics' => $statistics
            ],
            'last_sync' => now()->toISOString()
        ]);
    }

    /**
     * Handle mobile notifications preferences
     */
    public function notifications(): View
    {
        $user = auth()->user();
        $user->load(['profile']);

        return view('client.profile.mobile.notifications', compact('user'));
    }

    /**
     * Update mobile notification preferences
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'push_notifications' => 'boolean',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'opportunity_alerts' => 'boolean',
            'profile_views' => 'boolean'
        ]);

        try {
            $user = auth()->user();
            
            $settings = $user->profile?->settings ?? [];
            $settings['mobile_notifications'] = $request->validated();
            
            if ($user->profile) {
                $user->profile->update(['settings' => $settings]);
            } else {
                $this->profileService->createProfile($user, ['settings' => $settings]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences'
            ], 500);
        }
    }

    /**
     * Get mobile app configuration
     */
    public function appConfig(): JsonResponse
    {
        return response()->json([
            'features' => [
                'offline_mode' => true,
                'camera_upload' => true,
                'push_notifications' => true,
                'biometric_auth' => true,
                'dark_mode' => true
            ],
            'limits' => [
                'max_image_size' => 5 * 1024 * 1024, // 5MB
                'max_document_size' => 10 * 1024 * 1024, // 10MB
                'max_documents' => 20,
                'max_skills' => 50
            ],
            'supported_formats' => [
                'images' => ['jpg', 'jpeg', 'png', 'gif'],
                'documents' => ['pdf', 'doc', 'docx']
            ]
        ]);
    }
}