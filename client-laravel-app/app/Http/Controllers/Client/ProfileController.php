<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use App\Models\User;
use App\Models\City;
use App\Models\Country;
use App\Models\VolunteeringCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProfileController extends Controller
{
    public function __construct(
        private UserProfileService $profileService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the user's profile.
     */
    public function show(User $user = null): View
    {
        $user = $user ?? Auth::user();
        
        // Load all profile-related data
        $user->load([
            'profile',
            'skills',
            'volunteeringInterests.category',
            'userVolunteeringHistory',
            'documents',
            'userAlumniOrganizations',
            'platformInterests'
        ]);

        $statistics = $this->profileService->getUserStatistics($user);
        $matchingOpportunities = $this->profileService->getMatchingOpportunities($user);
        $timeline = $this->profileService->getProfileTimeline($user);

        return view('client.profile.show', compact(
            'user',
            'statistics',
            'matchingOpportunities',
            'timeline'
        ));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $user->load([
            'profile',
            'skills',
            'volunteeringInterests',
            'userVolunteeringHistory',
            'userAlumniOrganizations'
        ]);

        $cities = City::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();
        $categories = VolunteeringCategory::active()->get();

        return view('client.profile.edit', compact(
            'user',
            'cities',
            'countries',
            'categories'
        ));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'linkedin_url' => 'nullable|url|max:500',
            'twitter_url' => 'nullable|url|max:500',
            'facebook_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'is_public' => 'boolean',
        ]);

        try {
            $user = Auth::user();
            
            if ($user->profile) {
                $this->profileService->updateProfile($user->profile, $request->validated());
            } else {
                $this->profileService->createProfile($user, $request->validated());
            }

            return redirect()->route('profile.show')
                ->with('success', 'Profile updated successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update profile. Please try again.');
        }
    }

    /**
     * Upload profile image.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ]);

        try {
            $path = $this->profileService->uploadProfileImage(
                Auth::user(),
                $request->file('image')
            );

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully!',
                'path' => $path,
                'url' => Storage::url($path)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get profile statistics (AJAX).
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->profileService->getUserStatistics(Auth::user());
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Get matching opportunities (AJAX).
     */
    public function matchingOpportunities(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $opportunities = $this->profileService->getMatchingOpportunities(Auth::user(), $limit);
            
            return response()->json([
                'success' => true,
                'opportunities' => $opportunities
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load opportunities'
            ], 500);
        }
    }

    /**
     * Get profile timeline (AJAX).
     */
    public function timeline(): JsonResponse
    {
        try {
            $timeline = $this->profileService->getProfileTimeline(Auth::user());
            
            return response()->json([
                'success' => true,
                'timeline' => $timeline
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load timeline'
            ], 500);
        }
    }

    /**
     * Export user profile data.
     */
    public function export(): JsonResponse
    {
        try {
            $profileData = $this->profileService->exportUserProfile(Auth::user());
            
            return response()->json([
                'success' => true,
                'data' => $profileData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export profile data'
            ], 500);
        }
    }

    /**
     * Initialize default profile data for new users.
     */
    public function initializeDefaults(): JsonResponse
    {
        try {
            $this->profileService->initializeUserDefaults(Auth::user());
            
            return response()->json([
                'success' => true,
                'message' => 'Profile defaults initialized successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize defaults'
            ], 500);
        }
    }
}