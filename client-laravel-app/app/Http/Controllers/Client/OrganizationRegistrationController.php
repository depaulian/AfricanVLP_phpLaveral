<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\OrganizationRegistrationService;
use App\Models\OrganizationRegistrationStep;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class OrganizationRegistrationController extends Controller
{
    public function __construct(
        private OrganizationRegistrationService $registrationService
    ) {
        // No auth middleware - this is for public registration
    }

    /**
     * Show registration type selection page.
     */
    public function index(): View
    {
        return view('client.registration.index');
    }

    /**
     * Start organization registration process.
     */
    public function start(): RedirectResponse
    {
        $sessionId = Session::getId();
        
        // Initialize registration if not started
        if (!$this->hasExistingRegistration($sessionId)) {
            $this->registrationService->initializeRegistration($sessionId);
        }

        $nextStep = $this->registrationService->getNextStep($sessionId);
        
        return redirect()->route('organization.registration.step', $nextStep ?: 'organization_details');
    }

    /**
     * Show a specific registration step.
     */
    public function step(string $stepName): View|RedirectResponse
    {
        $sessionId = Session::getId();
        
        // Initialize registration if not started
        if (!$this->hasExistingRegistration($sessionId)) {
            $this->registrationService->initializeRegistration($sessionId);
        }
        
        // Validate step name
        $stepConfig = $this->registrationService->getStepConfiguration($stepName);
        if (!$stepConfig) {
            abort(404);
        }

        // Check if registration is already complete
        if ($this->registrationService->isRegistrationComplete($sessionId)) {
            return redirect()->route('organization.registration.success')
                ->with('info', 'Registration already completed!');
        }

        $progress = $this->registrationService->getRegistrationProgress($sessionId);
        $stepData = OrganizationRegistrationStep::where('session_id', $sessionId)
            ->where('step_name', $stepName)
            ->first();

        // Load additional data based on step
        $additionalData = $this->getStepData($stepName);

        return view("client.registration.organization.{$stepName}", array_merge([
            'progress' => $progress,
            'stepData' => $stepData,
            'stepName' => $stepName,
            'stepConfig' => $stepConfig,
            'sessionId' => $sessionId
        ], $additionalData));
    }

    /**
     * Process a registration step.
     */
    public function processStep(Request $request, string $stepName): RedirectResponse
    {
        $sessionId = Session::getId();
        
        // Validate step name
        $stepConfig = $this->registrationService->getStepConfiguration($stepName);
        if (!$stepConfig) {
            abort(404);
        }

        try {
            DB::beginTransaction();
            
            $validatedData = $this->validateStepData($request, $stepName);
            
            // Handle file uploads if present
            $validatedData = $this->handleFileUploads($request, $validatedData, $stepName);
            
            // Complete the step using OrganizationRegistrationService
            $success = $this->registrationService->completeStep($sessionId, $stepName, $validatedData);
            
            if (!$success) {
                throw new Exception('Failed to complete registration step');
            }

            DB::commit();

            // Determine next step
            $nextStep = $this->registrationService->getNextStep($sessionId);
            
            if ($nextStep) {
                return redirect()->route('organization.registration.step', $nextStep)
                    ->with('success', 'Step completed successfully!');
            } else {
                return redirect()->route('organization.registration.success')
                    ->with('success', 'Organization registration completed successfully!');
            }
            
        } catch (Exception $e) {
            DB::rollBack();
            
            return back()
                ->withErrors(['error' => 'An error occurred while processing your registration. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show registration success page.
     */
    public function success(): View
    {
        $sessionId = Session::getId();
        
        // Verify registration is complete
        if (!$this->registrationService->isRegistrationComplete($sessionId)) {
            return redirect()->route('organization.registration.start')
                ->with('error', 'Please complete the registration process first.');
        }

        $progress = $this->registrationService->getRegistrationProgress($sessionId);
        
        return view('client.registration.organization.success', compact('progress'));
    }

    /**
     * Get registration progress (AJAX).
     */
    public function progress(): JsonResponse
    {
        $sessionId = Session::getId();
        $progress = $this->registrationService->getRegistrationProgress($sessionId);
        
        return response()->json([
            'status' => 'success',
            'data' => $progress
        ]);
    }

    /**
     * Auto-save step progress (AJAX).
     */
    public function autoSave(Request $request): JsonResponse
    {
        try {
            $sessionId = Session::getId();
            $stepName = $request->input('step_name');
            $stepData = $request->input('step_data', []);

            // Validate step name
            if (!$this->registrationService->getStepConfiguration($stepName)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid step name'
                ], 400);
            }

            $success = $this->registrationService->saveStepProgress($sessionId, $stepName, $stepData);

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Progress saved automatically'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save progress'
                ], 500);
            }
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while saving progress'
            ], 500);
        }
    }

    /**
     * Get cities by country (AJAX).
     */
    public function getCities(Request $request): JsonResponse
    {
        $countryId = $request->input('country_id');
        
        if (!$countryId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Country ID is required'
            ], 400);
        }

        $cities = $this->registrationService->getCitiesByCountry($countryId);
        
        return response()->json([
            'status' => 'success',
            'data' => $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name
                ];
            })
        ]);
    }

    /**
     * Get registration analytics (Admin only).
     */
    public function analytics(Request $request): JsonResponse
    {
        // This would typically require admin authentication
        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date')) : null;

        $analytics = $this->registrationService->getRegistrationAnalytics($startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * Check if session has existing registration.
     */
    private function hasExistingRegistration(string $sessionId): bool
    {
        return OrganizationRegistrationStep::where('session_id', $sessionId)->exists();
    }

    /**
     * Get additional data for specific steps.
     */
    private function getStepData(string $stepName): array
    {
        return match ($stepName) {
            'organization_details' => [
                'countries' => $this->registrationService->getCountries(),
                'organizationTypes' => $this->registrationService->getOrganizationTypes(),
            ],
            'document_upload' => [
                'organizationTypes' => $this->registrationService->getOrganizationTypes(),
            ],
            'admin_user' => [
                'platformInterests' => $this->registrationService->getPlatformInterests(),
                'languages' => config('app.available_languages', [
                    'en' => 'English',
                    'fr' => 'French',
                    'ar' => 'Arabic',
                    'pt' => 'Portuguese'
                ]),
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
            'organization_details' => $request->validate([
                'name' => 'required|string|max:255',
                'about' => 'required|string|max:1000',
                'address' => 'required|string|max:500',
                'country_id' => 'required|exists:countries,id',
                'city_id' => 'required|exists:cities,id',
                'phone_number' => 'required|string|max:50',
                'website' => 'nullable|url|max:255',
                'organization_type_id' => 'required|exists:organization_types,id',
                'date_of_establishment' => 'nullable|date|before:today',
                'lat' => 'nullable|numeric|between:-90,90',
                'lng' => 'nullable|numeric|between:-180,180',
            ]),
            'document_upload' => $request->validate([
                'registration_document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
                'volunteering_policy' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
                'organization_sector' => 'nullable|string|max:255',
            ]),
            'admin_user' => $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => 'required|string|min:8|confirmed',
                'gender' => 'required|in:male,female,other,prefer_not_to_say',
                'date_of_birth' => 'required|date|before:today',
                'preferred_language' => 'required|string|in:en,fr,ar,pt',
                'platform_interests' => 'nullable|array',
                'platform_interests.*' => 'exists:platform_interests,id',
            ]),
            'verification' => $request->validate([
                'terms_accepted' => 'required|accepted',
                'privacy_accepted' => 'required|accepted',
                'email_verified' => 'nullable|boolean',
            ]),
            default => [],
        };
    }

    /**
     * Handle file uploads for the step.
     */
    private function handleFileUploads(Request $request, array $validatedData, string $stepName): array
    {
        if ($stepName === 'document_upload') {
            if ($request->hasFile('registration_document')) {
                $uploadedPath = $this->registrationService->handleFileUpload(
                    $request->file('registration_document'),
                    'registration'
                );
                if ($uploadedPath) {
                    $validatedData['registration_document'] = $uploadedPath;
                }
            }

            if ($request->hasFile('volunteering_policy')) {
                $uploadedPath = $this->registrationService->handleFileUpload(
                    $request->file('volunteering_policy'),
                    'policy'
                );
                if ($uploadedPath) {
                    $validatedData['volunteering_policy'] = $uploadedPath;
                }
            }
        }

        return $validatedData;
    }
}
