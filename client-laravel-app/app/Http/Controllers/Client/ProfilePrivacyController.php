<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ProfilePrivacyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Exception;

class ProfilePrivacyController extends Controller
{
    protected ProfilePrivacyService $privacyService;

    public function __construct(ProfilePrivacyService $privacyService)
    {
        $this->privacyService = $privacyService;
        $this->middleware('auth');
    }

    /**
     * Display privacy settings page.
     */
    public function index(): View
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return redirect()->route('profile.create')
                ->with('error', 'Please create your profile first.');
        }

        $currentSettings = $profile->privacy_settings ?? $this->privacyService->getDefaultPrivacySettings();
        $privacyLevels = config('profile_privacy.privacy_levels', []);
        $messageRestrictions = config('profile_privacy.message_restrictions', []);
        $profileSections = config('profile_privacy.profile_sections', []);
        $privacySummary = $this->privacyService->getPrivacySummary($profile);
        $recommendedSettings = $this->privacyService->getRecommendedSettings($user);
        $verificationStatus = $this->privacyService->checkVerificationRequirements($profile);

        return view('client.profile.privacy', compact(
            'profile',
            'currentSettings',
            'privacyLevels',
            'messageRestrictions',
            'profileSections',
            'privacySummary',
            'recommendedSettings',
            'verificationStatus'
        ));
    }

    /**
     * Update privacy settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return redirect()->route('profile.create')
                ->with('error', 'Please create your profile first.');
        }

        $validated = $request->validate([
            'profile_visibility' => 'required|in:public,organization,private',
            'contact_info' => 'required|in:public,organization,private',
            'skills' => 'required|in:public,organization,private',
            'volunteering_history' => 'required|in:public,organization,private',
            'volunteering_interests' => 'required|in:public,organization,private',
            'documents' => 'required|in:public,organization,private',
            'alumni_organizations' => 'required|in:public,organization,private',
            'allow_messages' => 'boolean',
            'messages_from' => 'required|in:anyone,verified,organization',
            'show_online_status' => 'boolean',
            'show_last_active' => 'boolean',
            'allow_profile_indexing' => 'boolean',
            'show_in_directory' => 'boolean',
            'allow_data_export' => 'boolean',
        ]);

        try {
            $this->privacyService->updatePrivacySettings($profile, $validated);

            return redirect()->route('profile.privacy')
                ->with('success', 'Privacy settings updated successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update privacy settings: ' . $e->getMessage());
        }
    }

    /**
     * Get privacy settings via API.
     */
    public function getSettings(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $settings = $profile->privacy_settings ?? $this->privacyService->getDefaultPrivacySettings();
        $summary = $this->privacyService->getPrivacySummary($profile);

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'summary' => $summary,
            ]
        ]);
    }

    /**
     * Update privacy settings via API.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $validated = $request->validate([
            'profile_visibility' => 'sometimes|in:public,organization,private',
            'contact_info' => 'sometimes|in:public,organization,private',
            'skills' => 'sometimes|in:public,organization,private',
            'volunteering_history' => 'sometimes|in:public,organization,private',
            'volunteering_interests' => 'sometimes|in:public,organization,private',
            'documents' => 'sometimes|in:public,organization,private',
            'alumni_organizations' => 'sometimes|in:public,organization,private',
            'allow_messages' => 'sometimes|boolean',
            'messages_from' => 'sometimes|in:anyone,verified,organization',
            'show_online_status' => 'sometimes|boolean',
            'show_last_active' => 'sometimes|boolean',
            'allow_profile_indexing' => 'sometimes|boolean',
            'show_in_directory' => 'sometimes|boolean',
            'allow_data_export' => 'sometimes|boolean',
        ]);

        try {
            $updatedProfile = $this->privacyService->updatePrivacySettings($profile, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully!',
                'data' => [
                    'settings' => $updatedProfile->privacy_settings,
                    'summary' => $this->privacyService->getPrivacySummary($updatedProfile),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get recommended privacy settings.
     */
    public function getRecommendedSettings(): JsonResponse
    {
        $user = Auth::user();
        $recommendedSettings = $this->privacyService->getRecommendedSettings($user);

        return response()->json([
            'success' => true,
            'data' => $recommendedSettings
        ]);
    }

    /**
     * Apply recommended privacy settings.
     */
    public function applyRecommendedSettings(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        try {
            $recommendedSettings = $this->privacyService->getRecommendedSettings($user);
            $updatedProfile = $this->privacyService->updatePrivacySettings($profile, $recommendedSettings);

            return response()->json([
                'success' => true,
                'message' => 'Recommended privacy settings applied successfully!',
                'data' => [
                    'settings' => $updatedProfile->privacy_settings,
                    'summary' => $this->privacyService->getPrivacySummary($updatedProfile),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get verification status.
     */
    public function getVerificationStatus(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $verificationStatus = $this->privacyService->checkVerificationRequirements($profile);

        return response()->json([
            'success' => true,
            'data' => $verificationStatus
        ]);
    }

    /**
     * Export profile data.
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        // Check if data export is allowed
        $settings = $profile->privacy_settings ?? [];
        if (!($settings['allow_data_export'] ?? true)) {
            return response()->json([
                'success' => false,
                'message' => 'Data export is disabled in your privacy settings'
            ], 403);
        }

        $format = $request->input('format', 'json');
        
        if (!in_array($format, config('profile_privacy.export_options.formats', ['json']))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid export format'
            ], 400);
        }

        try {
            $exportData = $this->privacyService->exportProfileData($profile, $format);

            return response()->json([
                'success' => true,
                'message' => 'Profile data exported successfully!',
                'data' => $exportData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get privacy configuration for frontend.
     */
    public function getPrivacyConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'privacy_levels' => config('profile_privacy.privacy_levels', []),
                'message_restrictions' => config('profile_privacy.message_restrictions', []),
                'profile_sections' => config('profile_privacy.profile_sections', []),
                'verification_benefits' => config('profile_privacy.verification_benefits', []),
                'export_options' => config('profile_privacy.export_options', []),
            ]
        ]);
    }
}