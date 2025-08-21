<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\OrganizationRegistrationStep;
use App\Models\Country;
use App\Models\City;
use App\Models\OrganizationType;
use App\Models\PlatformInterest;
use App\Notifications\OrganizationRegistrationWelcome;
use App\Notifications\OrganizationRegistrationAbandonmentReminder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class OrganizationRegistrationService
{
    private const REQUIRED_STEPS = [
        'organization_details' => [
            'title' => 'Organization Details',
            'description' => 'Tell us about your organization',
            'required_fields' => ['name', 'about', 'address', 'country_id', 'city_id', 'phone_number', 'organization_type_id'],
            'optional_fields' => ['website', 'date_of_establishment', 'lat', 'lng'],
            'weight' => 30
        ],
        'document_upload' => [
            'title' => 'Document Upload',
            'description' => 'Upload required documents',
            'required_fields' => ['registration_document'],
            'optional_fields' => ['volunteering_policy', 'organization_sector'],
            'weight' => 20
        ],
        'admin_user' => [
            'title' => 'Admin User',
            'description' => 'Create admin account for your organization',
            'required_fields' => ['first_name', 'last_name', 'email', 'password', 'gender', 'date_of_birth', 'preferred_language'],
            'optional_fields' => ['platform_interests'],
            'weight' => 30
        ],
        'verification' => [
            'title' => 'Verification',
            'description' => 'Verify email and complete registration',
            'required_fields' => ['email_verified', 'terms_accepted'],
            'optional_fields' => [],
            'weight' => 20
        ]
    ];

    public function __construct(
        private UserProfileService $profileService
    ) {}

    /**
     * Get registration step configuration
     */
    public function getStepConfiguration(string $stepName): ?array
    {
        return self::REQUIRED_STEPS[$stepName] ?? null;
    }

    /**
     * Get all registration steps
     */
    public function getAllSteps(): array
    {
        return self::REQUIRED_STEPS;
    }

    /**
     * Initialize organization registration for a session
     */
    public function initializeRegistration(string $sessionId): void
    {
        foreach (array_keys(self::REQUIRED_STEPS) as $stepName) {
            OrganizationRegistrationStep::updateOrCreate([
                'session_id' => $sessionId,
                'step_name' => $stepName
            ], [
                'step_data' => [],
                'is_completed' => false
            ]);
        }

        // Track registration start
        $this->trackRegistrationEvent($sessionId, 'organization_registration_started');
    }

    /**
     * Get detailed registration progress for a session
     */
    public function getRegistrationProgress(string $sessionId): array
    {
        $steps = [];
        $totalWeight = 0;
        $completedWeight = 0;

        foreach (self::REQUIRED_STEPS as $stepName => $config) {
            $stepRecord = OrganizationRegistrationStep::where('session_id', $sessionId)
                ->where('step_name', $stepName)
                ->first();
            
            $stepProgress = [
                'name' => $stepName,
                'title' => $config['title'],
                'description' => $config['description'],
                'is_completed' => $stepRecord?->is_completed ?? false,
                'completed_at' => $stepRecord?->completed_at,
                'step_data' => $stepRecord?->step_data ?? [],
                'weight' => $config['weight'],
                'completion_percentage' => $this->calculateStepCompletion($sessionId, $stepName, $config)
            ];

            $steps[$stepName] = $stepProgress;
            $totalWeight += $config['weight'];
            
            if ($stepProgress['is_completed']) {
                $completedWeight += $config['weight'];
            }
        }

        $overallPercentage = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100) : 0;

        return [
            'steps' => $steps,
            'overall_percentage' => $overallPercentage,
            'completed_steps' => collect($steps)->where('is_completed', true)->count(),
            'total_steps' => count($steps),
            'current_step' => $this->getCurrentStep($sessionId),
            'next_step' => $this->getNextStep($sessionId),
            'is_complete' => $this->isRegistrationComplete($sessionId)
        ];
    }

    /**
     * Calculate completion percentage for a specific step
     */
    public function calculateStepCompletion(string $sessionId, string $stepName, array $config): int
    {
        $stepRecord = OrganizationRegistrationStep::where('session_id', $sessionId)
            ->where('step_name', $stepName)
            ->first();

        if (!$stepRecord || $stepRecord->is_completed) {
            return $stepRecord?->is_completed ? 100 : 0;
        }

        $requiredFields = $config['required_fields'] ?? [];
        if (empty($requiredFields)) {
            return 0;
        }

        $stepData = $stepRecord->step_data ?? [];
        $completedFields = 0;

        foreach ($requiredFields as $field) {
            if (array_key_exists($field, $stepData) && !empty($stepData[$field])) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * Complete a registration step
     */
    public function completeStep(string $sessionId, string $stepName, array $stepData = []): bool
    {
        $config = $this->getStepConfiguration($stepName);
        if (!$config) {
            return false;
        }

        // Validate step data
        if (!$this->validateStepData($stepName, $stepData, $config)) {
            return false;
        }

        $stepRecord = OrganizationRegistrationStep::where('session_id', $sessionId)
            ->where('step_name', $stepName)
            ->first();

        if (!$stepRecord) {
            return false;
        }

        $stepRecord->complete($stepData);

        // Track step completion
        $this->trackRegistrationEvent($sessionId, 'step_completed', [
            'step_name' => $stepName,
            'completion_time' => now()
        ]);

        // Check if registration is complete
        if ($this->isRegistrationComplete($sessionId)) {
            $this->completeRegistration($sessionId);
        }

        return true;
    }

    /**
     * Validate step data
     */
    public function validateStepData(string $stepName, array $stepData, array $config): bool
    {
        $requiredFields = $config['required_fields'] ?? [];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $stepData) || empty($stepData[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if registration is complete
     */
    public function isRegistrationComplete(string $sessionId): bool
    {
        $totalSteps = count(self::REQUIRED_STEPS);
        $completedSteps = OrganizationRegistrationStep::where('session_id', $sessionId)
            ->where('is_completed', true)
            ->count();

        return $completedSteps === $totalSteps;
    }

    /**
     * Get the current step to complete
     */
    public function getCurrentStep(string $sessionId): ?string
    {
        $firstIncompleteStep = OrganizationRegistrationStep::where('session_id', $sessionId)
            ->where('is_completed', false)
            ->orderByStepOrder()
            ->first();

        return $firstIncompleteStep?->step_name;
    }

    /**
     * Get the next step to complete
     */
    public function getNextStep(string $sessionId): ?string
    {
        return $this->getCurrentStep($sessionId);
    }

    /**
     * Complete the entire registration process
     */
    public function completeRegistration(string $sessionId): bool
    {
        try {
            // Get all step data
            $allStepData = $this->getAllStepData($sessionId);
            
            // Create organization
            $organization = $this->createOrganization($allStepData);
            
            // Create admin user
            $adminUser = $this->createAdminUser($allStepData, $organization);
            
            // Update step records with created IDs
            OrganizationRegistrationStep::where('session_id', $sessionId)
                ->update([
                    'organization_id' => $organization->id,
                    'user_id' => $adminUser->id
                ]);

            // Send welcome email
            $adminUser->notify(new OrganizationRegistrationWelcome($organization));

            // Track completion
            $this->trackRegistrationEvent($sessionId, 'organization_registration_completed', [
                'organization_id' => $organization->id,
                'admin_user_id' => $adminUser->id,
                'completion_time' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Organization registration completion failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all step data for a session
     */
    private function getAllStepData(string $sessionId): array
    {
        $steps = OrganizationRegistrationStep::where('session_id', $sessionId)
            ->orderByStepOrder()
            ->get();

        $allData = [];
        foreach ($steps as $step) {
            $allData[$step->step_name] = $step->step_data ?? [];
        }

        return $allData;
    }

    /**
     * Create organization from step data
     */
    private function createOrganization(array $allStepData): Organization
    {
        $orgData = $allStepData['organization_details'] ?? [];
        $docData = $allStepData['document_upload'] ?? [];

        $organization = Organization::create([
            'name' => $orgData['name'],
            'about' => $orgData['about'],
            'address' => $orgData['address'],
            'country_id' => $orgData['country_id'],
            'city_id' => $orgData['city_id'],
            'phone_number' => $orgData['phone_number'],
            'website' => $orgData['website'] ?? null,
            'organization_type_id' => $orgData['organization_type_id'],
            'date_of_establishment' => $orgData['date_of_establishment'] ?? null,
            'lat' => $orgData['lat'] ?? null,
            'lng' => $orgData['lng'] ?? null,
            'registration_document' => $docData['registration_document'] ?? null,
            'organization_sector' => $docData['organization_sector'] ?? null,
            'status' => 'pending', // Pending admin approval
            'created' => now(),
            'modified' => now(),
        ]);

        return $organization;
    }

    /**
     * Create admin user from step data
     */
    private function createAdminUser(array $allStepData, Organization $organization): User
    {
        $userData = $allStepData['admin_user'] ?? [];

        $user = User::create([
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'gender' => $userData['gender'],
            'date_of_birth' => $userData['date_of_birth'],
            'preferred_language' => $userData['preferred_language'],
            'status' => 'pending', // Pending email verification
            'email_verification_token' => Str::random(60),
            'is_admin' => false,
            'created' => now(),
            'modified' => now(),
        ]);

        // Associate user with organization as admin
        $user->organizations()->attach($organization->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_date' => now()
        ]);

        // Add platform interests if provided
        if (!empty($userData['platform_interests'])) {
            foreach ($userData['platform_interests'] as $interestId) {
                $user->platformInterests()->attach($interestId);
            }
        }

        return $user;
    }

    /**
     * Save step progress (for auto-save functionality)
     */
    public function saveStepProgress(string $sessionId, string $stepName, array $stepData): bool
    {
        $stepRecord = OrganizationRegistrationStep::where('session_id', $sessionId)
            ->where('step_name', $stepName)
            ->first();

        if (!$stepRecord) {
            return false;
        }

        $stepRecord->updateData($stepData);
        return true;
    }

    /**
     * Handle file upload for documents
     */
    public function handleFileUpload(UploadedFile $file, string $type = 'registration'): ?string
    {
        try {
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = "organization_documents/{$type}/" . date('Y/m');
            
            $uploadedPath = $file->storeAs($path, $filename, 'public');
            
            return $uploadedPath;
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            return null;
        }
    }

    /**
     * Get countries for form options
     */
    public function getCountries(): \Illuminate\Database\Eloquent\Collection
    {
        return Country::where('status', 'active')
            ->where('region_id', '!=', null) // African countries only
            ->orderBy('name')
            ->get();
    }

    /**
     * Get cities by country for form options
     */
    public function getCitiesByCountry(int $countryId): \Illuminate\Database\Eloquent\Collection
    {
        return City::where('country_id', $countryId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get organization types for form options
     */
    public function getOrganizationTypes(): \Illuminate\Database\Eloquent\Collection
    {
        return OrganizationType::where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get platform interests for form options
     */
    public function getPlatformInterests(): \Illuminate\Database\Eloquent\Collection
    {
        return PlatformInterest::where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Track registration events for analytics
     */
    private function trackRegistrationEvent(string $sessionId, string $event, array $data = []): void
    {
        $eventData = array_merge([
            'session_id' => $sessionId,
            'event' => $event,
            'timestamp' => now(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip()
        ], $data);

        // Store in cache for analytics processing
        $cacheKey = "org_registration_events_" . now()->format('Y-m-d');
        $events = Cache::get($cacheKey, []);
        $events[] = $eventData;
        Cache::put($cacheKey, $events, 24 * 60); // 24 hours

        Log::info('Organization registration event tracked', $eventData);
    }

    /**
     * Get registration analytics
     */
    public function getRegistrationAnalytics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $totalRegistrations = OrganizationRegistrationStep::where('step_name', 'organization_details')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('session_id')
            ->count();

        $completedRegistrations = Organization::whereBetween('created_at', [$startDate, $endDate])->count();

        $conversionRate = $totalRegistrations > 0 ? ($completedRegistrations / $totalRegistrations) * 100 : 0;

        // Step completion rates
        $stepCompletionRates = [];
        foreach (array_keys(self::REQUIRED_STEPS) as $stepName) {
            $stepCompletions = OrganizationRegistrationStep::where('step_name', $stepName)
                ->where('is_completed', true)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            
            $stepCompletionRates[$stepName] = $totalRegistrations > 0 ? 
                ($stepCompletions / $totalRegistrations) * 100 : 0;
        }

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ],
            'total_registrations' => $totalRegistrations,
            'completed_registrations' => $completedRegistrations,
            'conversion_rate' => round($conversionRate, 2),
            'step_completion_rates' => $stepCompletionRates,
            'abandonment_rate' => round(100 - $conversionRate, 2)
        ];
    }

    /**
     * Clean up abandoned registrations
     */
    public function cleanupAbandonedRegistrations(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $abandonedSessions = OrganizationRegistrationStep::where('created_at', '<', $cutoffDate)
            ->whereNull('organization_id')
            ->distinct('session_id')
            ->pluck('session_id');

        $deletedCount = OrganizationRegistrationStep::whereIn('session_id', $abandonedSessions)->delete();

        Log::info('Cleaned up abandoned organization registrations', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate
        ]);

        return $deletedCount;
    }
}
