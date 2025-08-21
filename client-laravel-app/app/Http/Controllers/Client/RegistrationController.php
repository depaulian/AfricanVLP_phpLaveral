<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use App\Services\RegistrationService;
use App\Models\City;
use App\Models\Country;
use App\Models\VolunteeringCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class RegistrationController extends Controller
{
    public function __construct(
        private UserProfileService $profileService,
        private RegistrationService $registrationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show the multi-step registration wizard.
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Initialize registration if not started
        if (!$user->registrationSteps()->exists()) {
            $this->registrationService->initializeRegistration($user);
        }
        
        // Check if registration is already complete
        if ($this->registrationService->isRegistrationComplete($user)) {
            return redirect()->route('profile.show')
                ->with('info', 'Registration already completed!');
        }

        $progress = $this->registrationService->getRegistrationProgress($user);
        $nextStep = $this->registrationService->getNextStep($user);

        return view('client.registration.index', compact('progress', 'nextStep'));
    }

    /**
     * Show a specific registration step.
     */
    public function step(string $stepName): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Initialize registration if not started
        if (!$user->registrationSteps()->exists()) {
            $this->registrationService->initializeRegistration($user);
        }
        
        // Validate step name
        $stepConfig = $this->registrationService->getStepConfiguration($stepName);
        if (!$stepConfig) {
            abort(404);
        }

        // Check if registration is already complete
        if ($this->registrationService->isRegistrationComplete($user)) {
            return redirect()->route('profile.show')
                ->with('info', 'Registration already completed!');
        }

        $progress = $this->registrationService->getRegistrationProgress($user);
        $stepData = $user->registrationSteps()->where('step_name', $stepName)->first();

        // Load additional data based on step
        $additionalData = $this->getStepData($stepName);

        return view("client.registration.steps.{$stepName}", array_merge([
            'user' => $user,
            'progress' => $progress,
            'stepData' => $stepData,
            'stepName' => $stepName,
            'stepConfig' => $stepConfig
        ], $additionalData));
    }

    /**
     * Process a registration step.
     */
    public function processStep(Request $request, string $stepName): RedirectResponse
    {
        $user = Auth::user();
        
        // Validate step name
        $stepConfig = $this->registrationService->getStepConfiguration($stepName);
        if (!$stepConfig) {
            abort(404);
        }

        try {
            DB::beginTransaction();
            
            $validatedData = $this->validateStepData($request, $stepName);
            
            // Process the step data
            $this->processStepData($user, $stepName, $validatedData);
            
            // Mark step as completed using RegistrationService
            $this->registrationService->completeStep($user, $stepName, $validatedData);

            DB::commit();

            // Determine next step
            $nextStep = $this->registrationService->getNextStep($user->fresh());
            
            if ($nextStep) {
                return redirect()->route('registration.step', $nextStep)
                    ->with('success', 'Step completed successfully!');
            } else {
                // Registration complete - redirect to onboarding or profile
                return redirect()->route('profile.show')
                    ->with('success', 'Registration completed successfully! Welcome to the platform!');
            }
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to process step: ' . $e->getMessage());
        }
    }

    /**
     * Get registration progress (AJAX).
     */
    public function progress(): JsonResponse
    {
        try {
            $progress = $this->registrationService->getRegistrationProgress(Auth::user());
            
            return response()->json([
                'success' => true,
                'progress' => $progress
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load progress'
            ], 500);
        }
    }

    /**
     * Skip a registration step (if allowed).
     */
    public function skipStep(string $stepName): RedirectResponse
    {
        $user = Auth::user();
        
        // Only allow skipping certain steps
        $skippableSteps = ['interests'];
        if (!in_array($stepName, $skippableSteps)) {
            return redirect()->back()
                ->with('error', 'This step cannot be skipped.');
        }

        try {
            // Mark step as completed with skipped flag
            $this->registrationService->completeStep($user, $stepName, ['skipped' => true]);

            $nextStep = $this->registrationService->getNextStep($user->fresh());
            
            if ($nextStep) {
                return redirect()->route('registration.step', $nextStep)
                    ->with('info', 'Step skipped successfully.');
            } else {
                return redirect()->route('profile.show')
                    ->with('success', 'Registration completed successfully!');
            }
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to skip step. Please try again.');
        }
    }

    /**
     * Auto-save step progress (AJAX).
     */
    public function autoSave(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $stepName = $request->input('step_name');
            $stepData = $request->except(['_token', 'step_name', 'auto_save']);

            // Validate step name
            $stepConfig = $this->registrationService->getStepConfiguration($stepName);
            if (!$stepConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid step name'
                ], 400);
            }

            // Save progress
            $this->registrationService->saveStepProgress($user, $stepName, $stepData);

            return response()->json([
                'success' => true,
                'message' => 'Progress saved automatically'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save progress'
            ], 500);
        }
    }

    /**
     * Get registration analytics (Admin only).
     */
    public function analytics(Request $request): JsonResponse
    {
        // This would typically be in an admin controller, but including here for completeness
        if (!Auth::user()->hasRole('admin')) {
            abort(403);
        }

        try {
            $startDate = $request->input('start_date') ? 
                \Carbon\Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? 
                \Carbon\Carbon::parse($request->input('end_date')) : null;

            $analytics = $this->registrationService->getRegistrationAnalytics($startDate, $endDate);
            $funnel = $this->registrationService->getRegistrationFunnel($startDate, $endDate);

            return response()->json([
                'success' => true,
                'analytics' => $analytics,
                'funnel' => $funnel
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics'
            ], 500);
        }
    }

    /**
     * Get additional data for specific steps.
     */
    private function getStepData(string $stepName): array
    {
        return match ($stepName) {
            'profile_details' => [
                'cities' => City::orderBy('name')->get(),
                'countries' => Country::orderBy('name')->get(),
            ],
            'interests' => [
                'categories' => VolunteeringCategory::active()->get(),
            ],
            default => [],
        };
    }

    /**
     * Validate step data based on step name.
     */
    private function validateStepData(Request $request, string $stepName): array
    {
        return match ($stepName) {
            'basic_info' => $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . Auth::id(),
            ]),
            'profile_details' => $request->validate([
                'bio' => 'nullable|string|max:1000',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'phone_number' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:500',
                'city_id' => 'nullable|exists:cities,id',
                'country_id' => 'nullable|exists:countries,id',
            ]),
            'interests' => $request->validate([
                'volunteering_interests' => 'nullable|array',
                'volunteering_interests.*' => 'exists:volunteering_categories,id',
                'interest_levels' => 'nullable|array',
                'interest_levels.*' => 'in:low,medium,high',
                'skills' => 'nullable|array',
                'skills.*.name' => 'required|string|max:255',
                'skills.*.proficiency' => 'required|in:beginner,intermediate,advanced,expert',
                'skills.*.years_experience' => 'nullable|integer|min:0|max:50',
            ]),
            'verification' => $request->validate([
                'email_verified' => 'required|boolean',
                'terms_accepted' => 'required|accepted',
            ]),
            default => [],
        };
    }

    /**
     * Process step data and update user information.
     */
    private function processStepData(User $user, string $stepName, array $data): void
    {
        switch ($stepName) {
            case 'basic_info':
                $user->update([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                ]);
                break;

            case 'profile_details':
                if ($user->profile) {
                    $this->profileService->updateProfile($user->profile, $data);
                } else {
                    $this->profileService->createProfile($user, $data);
                }
                break;

            case 'interests':
                // Add volunteering interests
                if (!empty($data['volunteering_interests'])) {
                    foreach ($data['volunteering_interests'] as $index => $categoryId) {
                        $level = $data['interest_levels'][$index] ?? 'medium';
                        $this->profileService->addVolunteeringInterest($user, $categoryId, $level);
                    }
                }

                // Add skills
                if (!empty($data['skills'])) {
                    foreach ($data['skills'] as $skillData) {
                        $this->profileService->addSkill($user, [
                            'skill_name' => $skillData['name'],
                            'proficiency_level' => $skillData['proficiency'],
                            'years_experience' => $skillData['years_experience'] ?? null,
                        ]);
                    }
                }
                break;

            case 'verification':
                if ($data['email_verified'] && !$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
                break;
        }
    }
}