<?php

namespace App\Services;

use App\Models\User;
use App\Models\City;
use App\Models\Country;
use App\Models\VolunteeringCategory;
use App\Models\OrganizationCategory;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringOrganizationCategoryInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception;

class RegistrationService
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Register a new volunteer user
     *
     * @param Request $request
     * @return array
     */
    public function registerVolunteer(Request $request): array
    {
        try {
            DB::beginTransaction();

            // Create the user
            $user = $this->createUser($request);

            // Handle interests and preferences
            $this->handleUserInterests($user, $request);

            // Handle CV upload if provided
            if ($request->hasFile('cv')) {
                $this->handleCvUpload($user, $request->file('cv'));
            }

            // Send verification email
            $this->sendVerificationEmail($user);

            DB::commit();

            Log::info('User registration completed successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Registration completed successfully! Please check your email to verify your account.',
                'user' => $user,
                'redirect_url' => route('verification.notice')
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown'
            ]);

            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create the user record
     */
    private function createUser(Request $request): User
    {
        // Validate required fields
        $this->validateRegistrationData($request);

        $userData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'city_id' => $request->city_id,
            'country_id' => $request->country_id,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'preferred_language' => $request->preferred_language ?? User::LANGUAGE_ENGLISH,
            'time_commitment' => $request->time_commitment,
            'volunteer_mode' => $request->volunteer_mode,
            'registration_step' => 3, // Mark as completed
            'status' => 'pending', // Will be active after email verification
            'email_verification_token' => Str::random(60),
            'volunteer_notification_preferences' => $this->getDefaultNotificationPreferences(),
        ];

        return User::create($userData);
    }

    /**
     * Handle user interests (volunteering categories and organization categories)
     */
    private function handleUserInterests(User $user, Request $request): void
    {
        // Handle volunteering interests
        if ($request->has('volunteering_interests') && is_array($request->volunteering_interests)) {
            $volunteeringInterests = array_filter($request->volunteering_interests);
            if (!empty($volunteeringInterests)) {
                // Validate that categories exist
                $validCategories = VolunteeringCategory::whereIn('id', $volunteeringInterests)
                    ->where('status', 'active')
                    ->pluck('id')
                    ->toArray();
                
                // Clear existing volunteering interests
                UserVolunteeringInterest::where('user_id', $user->id)->delete();
                
                // Add new volunteering interests
                foreach ($validCategories as $categoryId) {
                    UserVolunteeringInterest::create([
                        'user_id' => $user->id,
                        'category_id' => $categoryId
                    ]);
                }
            }
        }

        // Handle organization category interests
        if ($request->has('organization_interests') && is_array($request->organization_interests)) {
            $organizationInterests = array_filter($request->organization_interests);
            if (!empty($organizationInterests)) {
                // Validate that categories exist
                $validOrgCategories = OrganizationCategory::whereIn('id', $organizationInterests)
                    ->pluck('id')
                    ->toArray();
                
                // Clear existing organization category interests
                UserVolunteeringOrganizationCategoryInterest::where('user_id', $user->id)->delete();
                
                // Add new organization category interests
                foreach ($validOrgCategories as $categoryId) {
                    UserVolunteeringOrganizationCategoryInterest::create([
                        'user_id' => $user->id,
                        'organization_category_id' => $categoryId
                    ]);
                }
            }
        }
    }

    /**
     * Handle CV file upload - made public for controller access
     */
    public function handleCvUpload(User $user, $cvFile): void
    {
        try {
            $result = $this->fileUploadService->uploadFile($cvFile, 'cvs', [
                'Metadata' => [
                    'user-id' => (string)$user->id,
                    'type' => 'cv',
                    'uploaded-by' => (string)$user->id
                ]
            ]);

            if ($result['success']) {
                $user->update(['cv_url' => $result['key']]);
                
                Log::info('CV uploaded successfully', [
                    'user_id' => $user->id,
                    'cv_key' => $result['key']
                ]);
            }
        } catch (Exception $e) {
            Log::warning('CV upload failed during registration', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail registration if CV upload fails
        }
    }

    /**
     * Send email verification
     */
    private function sendVerificationEmail(User $user): void
    {
        try {
            Mail::send('emails.verify-email', [
                'user' => $user,
                'verificationUrl' => route('verification.verify', [
                    'id' => $user->id,
                    'hash' => sha1($user->email),
                    'token' => $user->email_verification_token
                ])
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Verify Your Email Address - AU-VLP');
            });
        } catch (Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData(Request $request): void
    {
        $rules = [
            'first_name' => 'required|string|max:45',
            'last_name' => 'required|string|max:45',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'preferred_language' => 'nullable|in:' . implode(',', User::LANGUAGES),
            'time_commitment' => 'nullable|in:' . implode(',', User::TIME_COMMITMENTS),
            'volunteer_mode' => 'nullable|in:' . implode(',', User::VOLUNTEER_MODES),
            'volunteering_interests' => 'nullable|array',
            'volunteering_interests.*' => 'exists:volunteering_categories,id',
            'organization_interests' => 'nullable|array',
            'organization_interests.*' => 'exists:organization_categories,id',
            'interests' => 'nullable|array',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'terms_accepted' => 'required|accepted'
        ];

        $messages = [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.required' => 'Email address is required',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Passwords do not match',
            'date_of_birth.before' => 'Please enter a valid date of birth',
            'cv.mimes' => 'CV must be a PDF, DOC, or DOCX file',
            'cv.max' => 'CV file size must not exceed 5MB',
            'terms_accepted.required' => 'You must accept the terms and conditions'
        ];

        $request->validate($rules, $messages);
    }

    /**
     * Get default notification preferences
     */
    private function getDefaultNotificationPreferences(): array
    {
        return [
            'new_opportunities' => true,
            'application_updates' => true,
            'reminders' => true,
            'newsletters' => true,
            'events' => true,
            'messages' => true,
            'forum_notifications' => true,
            'achievement_notifications' => true
        ];
    }

    /**
     * Check if email is available
     */
    public function isEmailAvailable(string $email): bool
    {
        return !User::where('email', strtolower(trim($email)))->exists();
    }

    /**
     * Get registration progress data
     */
    public function getRegistrationProgress(User $user): array
    {
        $totalSteps = 3;
        $currentStep = $user->registration_step ?? 1;
        
        return [
            'current_step' => $currentStep,
            'total_steps' => $totalSteps,
            'progress_percentage' => min(100, ($currentStep / $totalSteps) * 100),
            'completed' => !is_null($user->registration_completed_at),
            'email_verified' => !is_null($user->email_verified_at)
        ];
    }

    /**
     * Complete registration process
     */
    public function completeRegistration(User $user): void
    {
        $user->update([
            'registration_completed_at' => now(),
            'status' => 'active'
        ]);

        // Send welcome email
        $this->sendWelcomeEmail($user);
    }

    /**
     * Send welcome email
     */
    private function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::send('emails.welcome', [
                'user' => $user
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Welcome to AU-VLP - Your Volunteering Journey Begins!');
            });
        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get countries for registration form
     */
    public function getCountries()
    {
        return Country::orderBy('name')->get(['id', 'name', 'code']);
    }

    /**
     * Get cities by country for registration form
     */
    public function getCitiesByCountry(int $countryId)
    {
        return City::where('country_id', $countryId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Auto-save registration progress
     */
    public function autoSaveProgress(User $user, array $data): void
    {
        try {
            // Only save specific fields to avoid overwriting completed data
            $allowedFields = [
                'phone_number', 'address', 'city_id', 'country_id',
                'date_of_birth', 'gender', 'preferred_language',
                'time_commitment', 'volunteer_mode'
            ];

            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            if (!empty($updateData)) {
                $user->update($updateData);
            }
        } catch (Exception $e) {
            Log::warning('Auto-save failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}