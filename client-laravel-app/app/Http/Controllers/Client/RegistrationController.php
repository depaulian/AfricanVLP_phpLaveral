<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use App\Services\RegistrationService;
use App\Http\Requests\VolunteerRegistrationRequest;
use App\Models\OrganizationCategory;
use App\Models\VolunteeringCategory;
use App\Models\Country;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function __construct(
        private UserProfileService $profileService,
        private RegistrationService $registrationService
    ) {}

    /**
     * Show the registration form
     */
    public function showRegistrationForm()
    {
        $volunteer_modes = User::VOLUNTEER_MODES;
        $time_commitments = User::TIME_COMMITMENTS;
        $languages = User::LANGUAGES;
        
        $countries = $this->registrationService->getCountries();
        
        $organisation_categories = OrganizationCategory::orderBy('name')->get(['id', 'name']);
        
        $volunteeringCategories = VolunteeringCategory::active()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return view('client.registration.volunteer.register', compact(
            'volunteeringCategories', 
            'volunteer_modes', 
            'time_commitments', 
            'languages', 
            'organisation_categories',
            'countries'
        ));
    }

    /**
     * Handle registration form submission
     */
    public function register(VolunteerRegistrationRequest $request)
    {
        try {
            // Handle AJAX request
            if ($request->expectsJson()) {
                $result = $this->registrationService->registerVolunteer($request);
                
                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'redirect_url' => $result['redirect_url']
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'errors' => $result['error'] ?? null
                    ], 422);
                }
            }

            // Handle regular form submission
            $result = $this->registrationService->registerVolunteer($request);
            
            if ($result['success']) {
                return redirect()->route('verification.notice')
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Registration controller error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again.'
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again.');
        }
    }

    /**
     * Check if email is available
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = strtolower(trim($request->email));
        $available = $this->registrationService->isEmailAvailable($email);

        return response()->json([
            'available' => $available,
            'message' => $available 
                ? 'Email is available' 
                : 'This email is already registered'
        ]);
    }

    /**
     * Get cities by country (AJAX endpoint)
     */
    public function getCitiesByCountry(Request $request, int $countryId): JsonResponse
    {
        try {
            $cities = $this->registrationService->getCitiesByCountry($countryId);
            
            return response()->json($cities);
        } catch (\Exception $e) {
            Log::error('Failed to fetch cities', [
                'country_id' => $countryId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to load cities'
            ], 500);
        }
    }

    /**
     * Auto-save registration progress (for authenticated users)
     */
    public function autoSave(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        try {
            $this->registrationService->autoSaveProgress(Auth::user(), $request->all());
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Auto-save failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get registration progress (for authenticated users)
     */
    public function getProgress(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        try {
            $progress = $this->registrationService->getRegistrationProgress(Auth::user());
            
            return response()->json($progress);
        } catch (\Exception $e) {
            Log::error('Failed to get registration progress', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Failed to load progress'], 500);
        }
    }

    /**
     * Get volunteering categories (AJAX endpoint)
     */
    public function getVolunteeringCategories(): JsonResponse
    {
        try {
            $categories = VolunteeringCategory::active()
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'icon']);
            
            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Failed to fetch volunteering categories', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to load categories'
            ], 500);
        }
    }

    /**
     * Get organization categories (AJAX endpoint)
     */
    public function getOrganizationCategories(): JsonResponse
    {
        try {
            $categories = OrganizationCategory::orderBy('name')
                ->get(['id', 'name', 'description']);
            
            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Failed to fetch organization categories', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to load categories'
            ], 500);
        }
    }

    /**
     * Registration dashboard (for incomplete registrations)
     */
    public function index()
    {
        $user = Auth::user();
        
        // Redirect if registration is already completed
        if ($user->hasCompletedRegistration()) {
            return redirect()->route('dashboard');
        }

        $progress = $this->registrationService->getRegistrationProgress($user);
        
        return view('client.registration.dashboard', compact('progress'));
    }

    /**
     * Show specific registration step
     */
    public function step(Request $request, string $stepName)
    {
        $user = Auth::user();
        $validSteps = ['basic-info', 'profile-details', 'interests-preferences'];
        
        if (!in_array($stepName, $validSteps)) {
            abort(404);
        }

        // Check if user can access this step
        $currentStep = $user->registration_step ?? 1;
        $stepNumber = array_search($stepName, $validSteps) + 1;
        
        if ($stepNumber > $currentStep + 1) {
            return redirect()->route('registration.volunteer.step', $validSteps[$currentStep - 1]);
        }

        $data = $this->getStepData($stepName);
        
        return view("client.registration.volunteer.steps.{$stepName}", array_merge([
            'user' => $user,
            'currentStep' => $stepNumber,
            'totalSteps' => count($validSteps)
        ], $data));
    }

    /**
     * Process registration step
     */
    public function processStep(Request $request, string $stepName)
    {
        $user = Auth::user();
        
        try {
            switch ($stepName) {
                case 'basic-info':
                    $this->processBasicInfo($request, $user);
                    break;
                case 'profile-details':
                    $this->processProfileDetails($request, $user);
                    break;
                case 'interests-preferences':
                    $this->processInterests($request, $user);
                    // Complete registration on final step
                    $this->registrationService->completeRegistration($user);
                    break;
                default:
                    abort(404);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Step completed successfully',
                    'next_url' => $this->getNextStepUrl($stepName)
                ]);
            }

            return redirect($this->getNextStepUrl($stepName));
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Skip optional registration step
     */
    public function skipStep(Request $request, string $stepName)
    {
        $user = Auth::user();
        
        // Only allow skipping certain steps
        $skippableSteps = ['profile-details'];
        
        if (!in_array($stepName, $skippableSteps)) {
            return response()->json(['error' => 'Cannot skip this step'], 400);
        }

        // Move to next step
        $nextStepNumber = $user->registration_step + 1;
        $user->update(['registration_step' => $nextStepNumber]);

        return response()->json([
            'success' => true,
            'next_url' => $this->getNextStepUrl($stepName)
        ]);
    }

    /**
     * Get data for specific step
     */
    private function getStepData(string $stepName): array
    {
        switch ($stepName) {
            case 'basic-info':
                return [
                    'countries' => $this->registrationService->getCountries(),
                    'languages' => User::LANGUAGES
                ];
                
            case 'profile-details':
                return [
                    'countries' => $this->registrationService->getCountries(),
                    'volunteer_modes' => User::VOLUNTEER_MODES,
                    'time_commitments' => User::TIME_COMMITMENTS
                ];
                
            case 'interests-preferences':
                return [
                    'volunteeringCategories' => VolunteeringCategory::active()->orderBy('name')->get(),
                    'organizationCategories' => OrganizationCategory::orderBy('name')->get(),
                    'volunteer_modes' => User::VOLUNTEER_MODES,
                    'time_commitments' => User::TIME_COMMITMENTS
                ];
                
            default:
                return [];
        }
    }

    /**
     * Process basic info step
     */
    private function processBasicInfo(Request $request, User $user): void
    {
        $request->validate([
            'first_name' => 'required|string|max:45',
            'last_name' => 'required|string|max:45',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'preferred_language' => 'required|in:' . implode(',', User::LANGUAGES)
        ]);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'preferred_language' => $request->preferred_language,
            'registration_step' => 2
        ]);
    }

    /**
     * Process profile details step
     */
    private function processProfileDetails(Request $request, User $user): void
    {
        $request->validate([
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'nullable|string',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120'
        ]);

        $updateData = [
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'registration_step' => 3
        ];

        // Handle CV upload
        if ($request->hasFile('cv')) {
            $this->registrationService->handleCvUpload($user, $request->file('cv'));
        }

        $user->update($updateData);
    }

    /**
     * Process interests and preferences step
     */
    private function processInterests(Request $request, User $user): void
    {
        $request->validate([
            'volunteer_mode' => 'required|in:' . implode(',', User::VOLUNTEER_MODES),
            'time_commitment' => 'required|in:' . implode(',', User::TIME_COMMITMENTS),
            'volunteering_interests' => 'nullable|array',
            'volunteering_interests.*' => 'exists:volunteering_categories,id',
            'organization_interests' => 'nullable|array',
            'organization_interests.*' => 'exists:organization_categories,id'
        ]);

        $user->update([
            'volunteer_mode' => $request->volunteer_mode,
            'time_commitment' => $request->time_commitment,
            'registration_step' => 4
        ]);

        // Handle interests
        $this->registrationService->handleUserInterests($user, $request);
    }

    /**
     * Get next step URL
     */
    private function getNextStepUrl(string $currentStep): string
    {
        $steps = ['basic-info', 'profile-details', 'interests-preferences'];
        $currentIndex = array_search($currentStep, $steps);
        
        if ($currentIndex === false || $currentIndex === count($steps) - 1) {
            return route('dashboard'); // Registration completed
        }
        
        return route('registration.volunteer.step', $steps[$currentIndex + 1]);
    }
}