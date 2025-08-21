<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRegistrationStep;
use App\Notifications\WelcomeEmail;
use App\Notifications\RegistrationAbandonmentReminder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RegistrationService
{
    private const REQUIRED_STEPS = [
        'basic_info' => [
            'title' => 'Basic Information',
            'description' => 'Tell us about yourself',
            'required_fields' => ['first_name', 'last_name', 'bio'],
            'optional_fields' => ['profile_image', 'date_of_birth', 'gender', 'phone_number'],
            'weight' => 25
        ],
        'profile_details' => [
            'title' => 'Profile Details',
            'description' => 'Complete your profile information',
            'required_fields' => ['city_id', 'country_id'],
            'optional_fields' => ['address', 'linkedin_url', 'website_url'],
            'weight' => 20
        ],
        'interests' => [
            'title' => 'Interests & Skills',
            'description' => 'What causes are you passionate about?',
            'required_fields' => ['volunteering_interests'],
            'optional_fields' => ['skills', 'commitment_level', 'motivation'],
            'weight' => 30
        ],
        'verification' => [
            'title' => 'Verification',
            'description' => 'Verify your account and accept terms',
            'required_fields' => ['email_verified', 'terms_accepted'],
            'optional_fields' => ['phone_verified'],
            'weight' => 25
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
     * Initialize registration for a user
     */
    public function initializeRegistration(User $user): void
    {
        foreach (array_keys(self::REQUIRED_STEPS) as $stepName) {
            UserRegistrationStep::updateOrCreate([
                'user_id' => $user->id,
                'step_name' => $stepName
            ], [
                'step_data' => [],
                'is_completed' => false
            ]);
        }

        // Track registration start
        $this->trackRegistrationEvent($user, 'registration_started');
    }

    /**
     * Get detailed registration progress
     */
    public function getRegistrationProgress(User $user): array
    {
        $steps = [];
        $totalWeight = 0;
        $completedWeight = 0;

        foreach (self::REQUIRED_STEPS as $stepName => $config) {
            $stepRecord = $user->registrationSteps()->where('step_name', $stepName)->first();
            
            $stepProgress = [
                'name' => $stepName,
                'title' => $config['title'],
                'description' => $config['description'],
                'is_completed' => $stepRecord?->is_completed ?? false,
                'completed_at' => $stepRecord?->completed_at,
                'step_data' => $stepRecord?->step_data ?? [],
                'weight' => $config['weight'],
                'completion_percentage' => $this->calculateStepCompletion($user, $stepName, $config)
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
            'is_complete' => $this->isRegistrationComplete($user),
            'next_step' => $this->getNextStep($user),
            'started_at' => $user->created_at,
            'estimated_completion_time' => $this->getEstimatedCompletionTime($user)
        ];
    }

    /**
     * Calculate completion percentage for a specific step
     */
    private function calculateStepCompletion(User $user, string $stepName, array $config): int
    {
        $stepRecord = $user->registrationSteps()->where('step_name', $stepName)->first();
        
        if ($stepRecord?->is_completed) {
            return 100;
        }

        $requiredFields = $config['required_fields'];
        $optionalFields = $config['optional_fields'] ?? [];
        $allFields = array_merge($requiredFields, $optionalFields);
        
        if (empty($allFields)) {
            return 0;
        }

        $completedFields = 0;
        $stepData = $stepRecord?->step_data ?? [];

        // Check completion based on step type
        switch ($stepName) {
            case 'basic_info':
                if (!empty($user->first_name)) $completedFields++;
                if (!empty($user->last_name)) $completedFields++;
                if (!empty($user->profile?->bio)) $completedFields++;
                if (!empty($user->profile?->profile_image_url)) $completedFields++;
                if (!empty($user->profile?->date_of_birth)) $completedFields++;
                if (!empty($user->profile?->phone_number)) $completedFields++;
                break;

            case 'profile_details':
                if (!empty($user->profile?->city_id)) $completedFields++;
                if (!empty($user->profile?->country_id)) $completedFields++;
                if (!empty($user->profile?->address)) $completedFields++;
                if (!empty($user->profile?->linkedin_url)) $completedFields++;
                break;

            case 'interests':
                if ($user->volunteeringInterests()->count() > 0) $completedFields++;
                if ($user->skills()->count() > 0) $completedFields++;
                if (!empty($stepData['commitment_level'])) $completedFields++;
                if (!empty($stepData['motivation'])) $completedFields++;
                break;

            case 'verification':
                if ($user->hasVerifiedEmail()) $completedFields++;
                if (!empty($stepData['terms_accepted'])) $completedFields++;
                if (!empty($stepData['phone_verified'])) $completedFields++;
                break;
        }

        return round(($completedFields / count($allFields)) * 100);
    }

    /**
     * Complete a registration step
     */
    public function completeStep(User $user, string $stepName, array $stepData = []): UserRegistrationStep
    {
        $config = $this->getStepConfiguration($stepName);
        if (!$config) {
            throw new \InvalidArgumentException("Invalid step name: {$stepName}");
        }

        // Validate required fields
        $this->validateStepData($stepName, $stepData, $config);

        $step = UserRegistrationStep::updateOrCreate([
            'user_id' => $user->id,
            'step_name' => $stepName
        ], [
            'step_data' => $stepData,
            'is_completed' => true,
            'completed_at' => now()
        ]);

        // Track step completion
        $this->trackRegistrationEvent($user, 'step_completed', [
            'step_name' => $stepName,
            'completion_time' => now()->diffInSeconds($user->created_at)
        ]);

        // Check if registration is now complete
        if ($this->isRegistrationComplete($user)) {
            $this->completeRegistration($user);
        }

        return $step;
    }

    /**
     * Validate step data
     */
    private function validateStepData(string $stepName, array $stepData, array $config): void
    {
        $requiredFields = $config['required_fields'];
        
        foreach ($requiredFields as $field) {
            if (empty($stepData[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing for step '{$stepName}'");
            }
        }
    }

    /**
     * Check if registration is complete
     */
    public function isRegistrationComplete(User $user): bool
    {
        $requiredSteps = array_keys(self::REQUIRED_STEPS);
        
        $completedSteps = $user->registrationSteps()
            ->whereIn('step_name', $requiredSteps)
            ->where('is_completed', true)
            ->count();
            
        return $completedSteps === count($requiredSteps);
    }

    /**
     * Get the next step to complete
     */
    public function getNextStep(User $user): ?string
    {
        foreach (array_keys(self::REQUIRED_STEPS) as $stepName) {
            $stepRecord = $user->registrationSteps()->where('step_name', $stepName)->first();
            if (!$stepRecord || !$stepRecord->is_completed) {
                return $stepName;
            }
        }
        
        return null;
    }

    /**
     * Complete the entire registration process
     */
    public function completeRegistration(User $user): void
    {
        // Update user registration status
        $user->update([
            'registration_completed_at' => now(),
            'onboarding_completed' => true
        ]);

        // Send welcome email
        $user->notify(new WelcomeEmail());

        // Track registration completion
        $this->trackRegistrationEvent($user, 'registration_completed', [
            'total_time' => now()->diffInSeconds($user->created_at),
            'completion_date' => now()->toDateString()
        ]);

        // Clear any abandonment reminders
        $this->clearAbandonmentReminders($user);

        Log::info('User registration completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'completion_time' => now()->diffInSeconds($user->created_at)
        ]);
    }

    /**
     * Save step progress (for auto-save functionality)
     */
    public function saveStepProgress(User $user, string $stepName, array $stepData): void
    {
        UserRegistrationStep::updateOrCreate([
            'user_id' => $user->id,
            'step_name' => $stepName
        ], [
            'step_data' => $stepData,
            'updated_at' => now()
        ]);

        // Cache the progress for quick access
        Cache::put("registration_progress_{$user->id}_{$stepName}", $stepData, 3600);
    }

    /**
     * Get estimated completion time
     */
    private function getEstimatedCompletionTime(User $user): int
    {
        $completedSteps = $user->registrationSteps()->where('is_completed', true)->count();
        $totalSteps = count(self::REQUIRED_STEPS);
        $remainingSteps = $totalSteps - $completedSteps;
        
        // Estimate 3-5 minutes per step
        return $remainingSteps * 4; // minutes
    }

    /**
     * Track registration abandonment and send reminders
     */
    public function trackAbandonmentAndSendReminders(): void
    {
        // Find users who started registration but haven't completed it
        $abandonedUsers = User::whereNull('registration_completed_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->where('created_at', '<=', now()->subHours(24))
            ->whereHas('registrationSteps')
            ->get();

        foreach ($abandonedUsers as $user) {
            $this->sendAbandonmentReminder($user);
        }
    }

    /**
     * Send abandonment reminder
     */
    private function sendAbandonmentReminder(User $user): void
    {
        $lastReminderKey = "abandonment_reminder_{$user->id}";
        
        // Don't send more than one reminder per week
        if (Cache::has($lastReminderKey)) {
            return;
        }

        $progress = $this->getRegistrationProgress($user);
        
        $user->notify(new RegistrationAbandonmentReminder($progress));
        
        Cache::put($lastReminderKey, true, 7 * 24 * 60); // 7 days
        
        $this->trackRegistrationEvent($user, 'abandonment_reminder_sent', [
            'days_since_start' => now()->diffInDays($user->created_at),
            'completion_percentage' => $progress['overall_percentage']
        ]);
    }

    /**
     * Clear abandonment reminders
     */
    private function clearAbandonmentReminders(User $user): void
    {
        Cache::forget("abandonment_reminder_{$user->id}");
    }

    /**
     * Track registration events for analytics
     */
    private function trackRegistrationEvent(User $user, string $event, array $data = []): void
    {
        $eventData = array_merge([
            'user_id' => $user->id,
            'event' => $event,
            'timestamp' => now(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip()
        ], $data);

        // Store in cache for analytics processing
        $cacheKey = "registration_events_" . now()->format('Y-m-d');
        $events = Cache::get($cacheKey, []);
        $events[] = $eventData;
        Cache::put($cacheKey, $events, 24 * 60); // 24 hours

        Log::info('Registration event tracked', $eventData);
    }

    /**
     * Get registration analytics
     */
    public function getRegistrationAnalytics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $totalRegistrations = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedRegistrations = User::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('registration_completed_at')
            ->count();

        $conversionRate = $totalRegistrations > 0 ? ($completedRegistrations / $totalRegistrations) * 100 : 0;

        // Step completion rates
        $stepCompletionRates = [];
        foreach (array_keys(self::REQUIRED_STEPS) as $stepName) {
            $stepCompletions = UserRegistrationStep::where('step_name', $stepName)
                ->where('is_completed', true)
                ->whereHas('user', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->count();
            
            $stepCompletionRates[$stepName] = $totalRegistrations > 0 ? 
                ($stepCompletions / $totalRegistrations) * 100 : 0;
        }

        // Average completion time
        $avgCompletionTime = User::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('registration_completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, registration_completed_at)) as avg_time')
            ->value('avg_time');

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ],
            'total_registrations' => $totalRegistrations,
            'completed_registrations' => $completedRegistrations,
            'conversion_rate' => round($conversionRate, 2),
            'step_completion_rates' => $stepCompletionRates,
            'average_completion_time_minutes' => round($avgCompletionTime ?? 0, 2),
            'abandonment_rate' => round(100 - $conversionRate, 2)
        ];
    }

    /**
     * Get registration funnel data
     */
    public function getRegistrationFunnel(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $funnel = [];
        $totalUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $funnel['started'] = [
            'count' => $totalUsers,
            'percentage' => 100
        ];

        foreach (self::REQUIRED_STEPS as $stepName => $config) {
            $completedCount = UserRegistrationStep::where('step_name', $stepName)
                ->where('is_completed', true)
                ->whereHas('user', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->count();

            $funnel[$stepName] = [
                'count' => $completedCount,
                'percentage' => $totalUsers > 0 ? round(($completedCount / $totalUsers) * 100, 2) : 0,
                'title' => $config['title']
            ];
        }

        return $funnel;
    }
}