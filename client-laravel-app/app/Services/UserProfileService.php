<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use App\Models\UserRegistrationStep;
use App\Models\VolunteeringOpportunity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UserProfileService
{
    /**
     * Create a new user profile.
     */
    public function createProfile(User $user, array $data): UserProfile
    {
        try {
            DB::beginTransaction();

            $profile = $user->profile()->create($data);
            $profile->calculateCompletionPercentage();

            DB::commit();

            Log::info('User profile created', [
                'user_id' => $user->id,
                'profile_id' => $profile->id
            ]);

            return $profile;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing user profile.
     */
    public function updateProfile(UserProfile $profile, array $data): UserProfile
    {
        try {
            DB::beginTransaction();

            $profile->update($data);
            $profile->calculateCompletionPercentage();

            DB::commit();

            Log::info('User profile updated', [
                'user_id' => $profile->user_id,
                'profile_id' => $profile->id
            ]);

            return $profile->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user profile', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upload and process profile image.
     */
    public function uploadProfileImage(User $user, UploadedFile $file): string
    {
        try {
            // Validate file
            $this->validateImageFile($file);

            // Store the file
            $path = $file->store('profile-images', 'public');
            $url = Storage::url($path);

            // Update or create profile with image URL
            $user->profile()->updateOrCreate([], [
                'profile_image_url' => $url
            ]);

            // Recalculate completion percentage
            $user->profile?->calculateCompletionPercentage();

            Log::info('Profile image uploaded', [
                'user_id' => $user->id,
                'file_path' => $path
            ]);

            return $path;
        } catch (Exception $e) {
            Log::error('Failed to upload profile image', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add a skill to user's profile.
     */
    public function addSkill(User $user, array $skillData): UserSkill
    {
        try {
            DB::beginTransaction();

            $skill = $user->skills()->create($skillData);
            $user->profile?->calculateCompletionPercentage();

            DB::commit();

            Log::info('User skill added', [
                'user_id' => $user->id,
                'skill_id' => $skill->id,
                'skill_name' => $skill->skill_name
            ]);

            return $skill;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to add user skill', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing skill.
     */
    public function updateSkill(UserSkill $skill, array $data): UserSkill
    {
        try {
            $skill->update($data);

            Log::info('User skill updated', [
                'skill_id' => $skill->id,
                'user_id' => $skill->user_id
            ]);

            return $skill->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update user skill', [
                'skill_id' => $skill->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove a skill from user's profile.
     */
    public function removeSkill(UserSkill $skill): bool
    {
        try {
            DB::beginTransaction();

            $userId = $skill->user_id;
            $skill->delete();

            // Recalculate completion percentage
            $user = User::find($userId);
            $user->profile?->calculateCompletionPercentage();

            DB::commit();

            Log::info('User skill removed', [
                'skill_id' => $skill->id,
                'user_id' => $userId
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove user skill', [
                'skill_id' => $skill->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add or update a volunteering interest.
     */
    public function addVolunteeringInterest(User $user, int $categoryId, string $level = 'medium'): UserVolunteeringInterest
    {
        try {
            DB::beginTransaction();

            $interest = $user->volunteeringInterests()->updateOrCreate([
                'category_id' => $categoryId
            ], [
                'interest_level' => $level
            ]);

            $user->profile?->calculateCompletionPercentage();

            DB::commit();

            Log::info('User volunteering interest added/updated', [
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'interest_level' => $level
            ]);

            return $interest;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to add volunteering interest', [
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove a volunteering interest.
     */
    public function removeVolunteeringInterest(UserVolunteeringInterest $interest): bool
    {
        try {
            DB::beginTransaction();

            $userId = $interest->user_id;
            $interest->delete();

            // Recalculate completion percentage
            $user = User::find($userId);
            $user->profile?->calculateCompletionPercentage();

            DB::commit();

            Log::info('User volunteering interest removed', [
                'interest_id' => $interest->id,
                'user_id' => $userId
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove volunteering interest', [
                'interest_id' => $interest->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add volunteering history entry.
     */
    public function addVolunteeringHistory(User $user, array $historyData): UserVolunteeringHistory
    {
        try {
            DB::beginTransaction();

            $history = $user->userVolunteeringHistory()->create($historyData);
            $user->profile?->calculateCompletionPercentage();

            DB::commit();

            Log::info('User volunteering history added', [
                'user_id' => $user->id,
                'history_id' => $history->id,
                'organization' => $history->organization_name
            ]);

            return $history;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to add volunteering history', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update volunteering history entry.
     */
    public function updateVolunteeringHistory(UserVolunteeringHistory $history, array $data): UserVolunteeringHistory
    {
        try {
            $history->update($data);

            Log::info('User volunteering history updated', [
                'history_id' => $history->id,
                'user_id' => $history->user_id
            ]);

            return $history->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update volunteering history', [
                'history_id' => $history->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upload a user document.
     */
    public function uploadDocument(User $user, UploadedFile $file, string $type): UserDocument
    {
        try {
            // Validate file
            $this->validateDocumentFile($file);

            DB::beginTransaction();

            // Store the file securely
            $path = $file->store('user-documents', 'private');

            $document = $user->documents()->create([
                'document_type' => $type,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'verification_status' => 'pending'
            ]);

            DB::commit();

            Log::info('User document uploaded', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'document_type' => $type
            ]);

            return $document;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to upload user document', [
                'user_id' => $user->id,
                'document_type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add alumni organization.
     */
    public function addAlumniOrganization(User $user, array $alumniData): UserAlumniOrganization
    {
        try {
            DB::beginTransaction();

            $alumni = $user->userAlumniOrganizations()->create($alumniData);

            DB::commit();

            Log::info('User alumni organization added', [
                'user_id' => $user->id,
                'alumni_id' => $alumni->id,
                'organization' => $alumni->organization_name
            ]);

            return $alumni;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to add alumni organization', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update alumni organization.
     */
    public function updateAlumniOrganization(UserAlumniOrganization $alumni, array $data): UserAlumniOrganization
    {
        try {
            $alumni->update($data);

            Log::info('User alumni organization updated', [
                'alumni_id' => $alumni->id,
                'user_id' => $alumni->user_id
            ]);

            return $alumni->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update alumni organization', [
                'alumni_id' => $alumni->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Complete a registration step.
     */
    public function completeRegistrationStep(User $user, string $stepName, array $stepData = []): UserRegistrationStep
    {
        try {
            DB::beginTransaction();

            $step = $user->registrationSteps()->updateOrCreate([
                'step_name' => $stepName
            ], [
                'step_data' => $stepData,
                'is_completed' => true,
                'completed_at' => now()
            ]);

            DB::commit();

            Log::info('Registration step completed', [
                'user_id' => $user->id,
                'step_name' => $stepName
            ]);

            return $step;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete registration step', [
                'user_id' => $user->id,
                'step_name' => $stepName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get registration progress for a user.
     */
    public function getRegistrationProgress(User $user): array
    {
        $steps = ['basic_info', 'profile_details', 'interests', 'verification'];
        $progress = [];

        foreach ($steps as $step) {
            $stepRecord = $user->registrationSteps()->where('step_name', $step)->first();
            $progress[$step] = [
                'completed' => $stepRecord?->is_completed ?? false,
                'completed_at' => $stepRecord?->completed_at,
                'data' => $stepRecord?->step_data ?? []
            ];
        }

        $completedCount = collect($progress)->where('completed', true)->count();
        $progress['overall'] = [
            'completed_steps' => $completedCount,
            'total_steps' => count($steps),
            'percentage' => round(($completedCount / count($steps)) * 100)
        ];

        return $progress;
    }

    /**
     * Get matching volunteering opportunities for a user.
     */
    public function getMatchingOpportunities(User $user, int $limit = 10): Collection
    {
        $interests = $user->volunteeringInterests()->pluck('category_id');
        $skills = $user->skills()->pluck('skill_name');

        if ($interests->isEmpty() && $skills->isEmpty()) {
            return collect();
        }

        return VolunteeringOpportunity::active()
            ->where(function ($query) use ($interests, $skills) {
                if ($interests->isNotEmpty()) {
                    $query->whereIn('category_id', $interests);
                }

                if ($skills->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($skills) {
                        foreach ($skills as $skill) {
                            $q->orWhereJsonContains('required_skills', $skill);
                        }
                    });
                }
            })
            ->with(['organization', 'category'])
            ->orderByDesc('featured')
            ->limit($limit)
            ->get();
    }

    /**
     * Get comprehensive user statistics.
     */
    public function getUserStatistics(User $user): array
    {
        return [
            'profile_completion' => $user->profile_completion_percentage,
            'skills_count' => $user->skills()->count(),
            'verified_skills_count' => $user->skills()->where('verified', true)->count(),
            'interests_count' => $user->volunteeringInterests()->count(),
            'volunteering_history_count' => $user->userVolunteeringHistory()->count(),
            'total_volunteering_hours' => $user->userVolunteeringHistory()->sum('hours_contributed'),
            'documents_count' => $user->documents()->count(),
            'verified_documents_count' => $user->documents()->verified()->count(),
            'alumni_organizations_count' => $user->userAlumniOrganizations()->count(),
            'verified_alumni_count' => $user->userAlumniOrganizations()->verified()->count(),
            'registration_completed' => $user->hasCompletedRegistration(),
            'next_registration_step' => $user->getNextRegistrationStep(),
        ];
    }

    /**
     * Get user's profile timeline data.
     */
    public function getProfileTimeline(User $user): array
    {
        $timeline = [];

        // Add volunteering history
        $volunteeringHistory = $user->userVolunteeringHistory()
            ->orderByDesc('start_date')
            ->get();

        foreach ($volunteeringHistory as $history) {
            $timeline[] = [
                'type' => 'volunteering',
                'date' => $history->start_date,
                'title' => $history->role_title,
                'description' => $history->organization_name,
                'duration' => $history->duration,
                'data' => $history
            ];
        }

        // Add alumni organizations
        $alumniOrgs = $user->userAlumniOrganizations()
            ->orderByDesc('graduation_year')
            ->get();

        foreach ($alumniOrgs as $alumni) {
            if ($alumni->graduation_year) {
                $timeline[] = [
                    'type' => 'education',
                    'date' => now()->setYear($alumni->graduation_year)->startOfYear(),
                    'title' => $alumni->degree ?? 'Education',
                    'description' => $alumni->organization_name,
                    'duration' => $alumni->graduation_year,
                    'data' => $alumni
                ];
            }
        }

        // Sort timeline by date (most recent first)
        usort($timeline, function ($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return $timeline;
    }

    /**
     * Validate image file upload.
     */
    private function validateImageFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('Invalid image file type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }

        if ($file->getSize() > $maxSize) {
            throw new Exception('Image file size must be less than 5MB.');
        }
    }

    /**
     * Validate document file upload.
     */
    private function validateDocumentFile(UploadedFile $file): void
    {
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('Invalid document file type. Only PDF, images, and Word documents are allowed.');
        }

        if ($file->getSize() > $maxSize) {
            throw new Exception('Document file size must be less than 10MB.');
        }
    }

    /**
     * Initialize default data for a new user.
     */
    public function initializeUserDefaults(User $user): void
    {
        try {
            DB::beginTransaction();

            // Create basic profile if it doesn't exist
            if (!$user->profile) {
                $this->createProfile($user, [
                    'is_public' => true,
                    'settings' => []
                ]);
            }

            // Initialize platform interests
            $user->initializePlatformInterests();

            // Initialize notification preferences
            $user->initializeNotificationPreferences();

            DB::commit();

            Log::info('User defaults initialized', [
                'user_id' => $user->id
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to initialize user defaults', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add impact data to volunteering history.
     */
    public function addVolunteeringImpact(UserVolunteeringHistory $history, array $impactData): UserVolunteeringHistory
    {
        try {
            $updateData = [];
            
            if (isset($impactData['impact_description'])) {
                $updateData['impact_description'] = $impactData['impact_description'];
            }
            
            if (isset($impactData['people_helped'])) {
                $updateData['people_helped'] = $impactData['people_helped'];
            }
            
            if (isset($impactData['funds_raised'])) {
                $updateData['funds_raised'] = $impactData['funds_raised'];
            }
            
            if (isset($impactData['events_organized'])) {
                $updateData['events_organized'] = $impactData['events_organized'];
            }
            
            if (isset($impactData['impact_metrics'])) {
                $updateData['impact_metrics'] = $impactData['impact_metrics'];
            }
            
            $history->update($updateData);

            Log::info('Volunteering impact added', [
                'history_id' => $history->id,
                'user_id' => $history->user_id
            ]);

            return $history->fresh();
        } catch (Exception $e) {
            Log::error('Failed to add volunteering impact', [
                'history_id' => $history->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add certificate to volunteering history.
     */
    public function addVolunteeringCertificate(UserVolunteeringHistory $history, array $certificateData): UserVolunteeringHistory
    {
        try {
            $history->addCertificate($certificateData);

            Log::info('Volunteering certificate added', [
                'history_id' => $history->id,
                'certificate_name' => $certificateData['name'] ?? 'Unknown'
            ]);

            return $history->fresh();
        } catch (Exception $e) {
            Log::error('Failed to add volunteering certificate', [
                'history_id' => $history->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add recognition to volunteering history.
     */
    public function addVolunteeringRecognition(UserVolunteeringHistory $history, array $recognitionData): UserVolunteeringHistory
    {
        try {
            $history->addRecognition($recognitionData);

            Log::info('Volunteering recognition added', [
                'history_id' => $history->id,
                'recognition_title' => $recognitionData['title'] ?? 'Unknown'
            ]);

            return $history->fresh();
        } catch (Exception $e) {
            Log::error('Failed to add volunteering recognition', [
                'history_id' => $history->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify reference contact for volunteering history.
     */
    public function verifyVolunteeringReference(UserVolunteeringHistory $history): UserVolunteeringHistory
    {
        try {
            $history->verifyReference();

            Log::info('Volunteering reference verified', [
                'history_id' => $history->id,
                'user_id' => $history->user_id
            ]);

            return $history->fresh();
        } catch (Exception $e) {
            Log::error('Failed to verify volunteering reference', [
                'history_id' => $history->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get volunteering portfolio data for a user.
     */
    public function getVolunteeringPortfolio(User $user): array
    {
        $portfolioHistory = $user->userVolunteeringHistory()
            ->where('portfolio_visible', true)
            ->orderByDesc('start_date')
            ->get();

        $portfolio = [
            'user' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'profile_image' => $user->profile?->profile_image_url,
                'bio' => $user->profile?->bio,
                'location' => $user->profile?->full_address,
            ],
            'summary' => [
                'total_positions' => $portfolioHistory->count(),
                'total_hours' => $portfolioHistory->sum('hours_contributed'),
                'total_experience_years' => round($portfolioHistory->sum('duration_in_months') / 12, 1),
                'organizations_count' => $portfolioHistory->unique('organization_name')->count(),
                'total_impact_score' => $portfolioHistory->sum('impact_score'),
                'verified_references' => $portfolioHistory->where('reference_verified', true)->count(),
                'certificates_count' => $portfolioHistory->sum(function ($history) {
                    return count($history->certificates ?? []);
                }),
                'recognitions_count' => $portfolioHistory->sum(function ($history) {
                    return count($history->recognitions ?? []);
                }),
            ],
            'experiences' => $portfolioHistory->map(function ($history) {
                return $history->portfolio_data;
            })->toArray(),
            'skills' => $user->skills()->verified()->get()->map(function ($skill) {
                return [
                    'name' => $skill->skill_name,
                    'proficiency' => $skill->proficiency_level,
                    'years_experience' => $skill->years_experience,
                    'verified' => $skill->verified,
                ];
            })->toArray(),
            'generated_at' => now()->toISOString(),
        ];

        return $portfolio;
    }

    /**
     * Export volunteering portfolio as formatted data.
     */
    public function exportVolunteeringPortfolio(User $user, string $format = 'json'): array
    {
        $portfolio = $this->getVolunteeringPortfolio($user);

        switch ($format) {
            case 'pdf':
                return $this->formatPortfolioForPdf($portfolio);
            case 'html':
                return $this->formatPortfolioForHtml($portfolio);
            case 'csv':
                return $this->formatPortfolioForCsv($portfolio);
            default:
                return $portfolio;
        }
    }

    /**
     * Get volunteering experience timeline with visual representation data.
     */
    public function getVolunteeringTimeline(User $user): array
    {
        $history = $user->userVolunteeringHistory()
            ->orderBy('start_date')
            ->get();

        $timeline = [];
        $currentYear = now()->year;
        $startYear = $history->min('start_date')?->year ?? $currentYear;

        // Create timeline data for visualization
        for ($year = $startYear; $year <= $currentYear; $year++) {
            $yearData = [
                'year' => $year,
                'positions' => [],
                'total_hours' => 0,
                'organizations' => [],
            ];

            foreach ($history as $experience) {
                $startYear = $experience->start_date->year;
                $endYear = $experience->is_current ? $currentYear : ($experience->end_date?->year ?? $startYear);

                if ($year >= $startYear && $year <= $endYear) {
                    $yearData['positions'][] = [
                        'id' => $experience->id,
                        'organization' => $experience->organization_name,
                        'role' => $experience->role_title,
                        'is_current' => $experience->is_current && $year == $currentYear,
                        'hours_in_year' => $this->calculateHoursInYear($experience, $year),
                        'impact_score' => $experience->impact_score,
                    ];

                    $yearData['total_hours'] += $this->calculateHoursInYear($experience, $year);
                    
                    if (!in_array($experience->organization_name, $yearData['organizations'])) {
                        $yearData['organizations'][] = $experience->organization_name;
                    }
                }
            }

            $timeline[] = $yearData;
        }

        return [
            'timeline' => $timeline,
            'summary' => [
                'years_active' => count(array_filter($timeline, fn($year) => $year['total_hours'] > 0)),
                'peak_year' => collect($timeline)->sortByDesc('total_hours')->first(),
                'total_organizations' => $history->unique('organization_name')->count(),
                'current_positions' => $history->where('is_current', true)->count(),
            ]
        ];
    }

    /**
     * Calculate impact metrics for user's volunteering history.
     */
    public function calculateVolunteeringImpact(User $user): array
    {
        $history = $user->userVolunteeringHistory;

        $impact = [
            'total_hours' => $history->sum('hours_contributed'),
            'total_people_helped' => $history->sum('people_helped'),
            'total_funds_raised' => $history->sum('funds_raised'),
            'total_events_organized' => $history->sum('events_organized'),
            'total_impact_score' => $history->sum('impact_score'),
            'average_impact_score' => $history->count() > 0 ? round($history->avg('impact_score'), 1) : 0,
            'verified_experiences' => $history->where('reference_verified', true)->count(),
            'certificates_earned' => $history->sum(function ($h) { return count($h->certificates ?? []); }),
            'recognitions_received' => $history->sum(function ($h) { return count($h->recognitions ?? []); }),
            'organizations_served' => $history->unique('organization_name')->count(),
            'years_of_service' => round($history->sum('duration_in_months') / 12, 1),
            'current_commitments' => $history->where('is_current', true)->count(),
        ];

        // Calculate impact level based on total score
        if ($impact['total_impact_score'] >= 500) {
            $impact['impact_level'] = 'exceptional';
        } elseif ($impact['total_impact_score'] >= 300) {
            $impact['impact_level'] = 'high';
        } elseif ($impact['total_impact_score'] >= 150) {
            $impact['impact_level'] = 'moderate';
        } elseif ($impact['total_impact_score'] >= 50) {
            $impact['impact_level'] = 'developing';
        } else {
            $impact['impact_level'] = 'beginning';
        }

        return $impact;
    }

    /**
     * Get reference contacts for verification.
     */
    public function getVolunteeringReferences(User $user): Collection
    {
        return $user->userVolunteeringHistory()
            ->whereNotNull('reference_contact')
            ->where(function ($query) {
                $query->whereNotNull('reference_email')
                      ->orWhereNotNull('reference_phone');
            })
            ->orderByDesc('reference_verified')
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'organization' => $history->organization_name,
                    'role' => $history->role_title,
                    'reference_name' => $history->reference_contact,
                    'reference_position' => $history->reference_position,
                    'reference_email' => $history->reference_email,
                    'reference_phone' => $history->reference_phone,
                    'verified' => $history->reference_verified,
                    'verified_at' => $history->reference_verified_at,
                    'duration' => $history->duration,
                    'hours_contributed' => $history->hours_contributed,
                ];
            });
    }

    /**
     * Calculate hours contributed in a specific year.
     */
    private function calculateHoursInYear(UserVolunteeringHistory $experience, int $year): int
    {
        $startYear = $experience->start_date->year;
        $endYear = $experience->is_current ? now()->year : ($experience->end_date?->year ?? $startYear);
        
        if ($year < $startYear || $year > $endYear) {
            return 0;
        }

        $totalMonths = $experience->duration_in_months;
        $totalHours = $experience->hours_contributed ?? 0;
        
        if ($totalMonths == 0) {
            return $totalHours;
        }

        // Calculate months in this specific year
        $yearStart = max($experience->start_date, now()->setYear($year)->startOfYear());
        $yearEnd = min(
            $experience->is_current ? now() : $experience->end_date,
            now()->setYear($year)->endOfYear()
        );

        $monthsInYear = $yearStart->diffInMonths($yearEnd) + 1;
        
        return round(($totalHours / $totalMonths) * $monthsInYear);
    }

    /**
     * Format portfolio data for PDF export.
     */
    private function formatPortfolioForPdf(array $portfolio): array
    {
        return [
            'format' => 'pdf',
            'title' => 'Volunteering Portfolio - ' . $portfolio['user']['name'],
            'data' => $portfolio,
            'template' => 'portfolio-pdf',
        ];
    }

    /**
     * Format portfolio data for HTML export.
     */
    private function formatPortfolioForHtml(array $portfolio): array
    {
        return [
            'format' => 'html',
            'title' => 'Volunteering Portfolio - ' . $portfolio['user']['name'],
            'data' => $portfolio,
            'template' => 'portfolio-html',
        ];
    }

    /**
     * Format portfolio data for CSV export.
     */
    private function formatPortfolioForCsv(array $portfolio): array
    {
        $csvData = [];
        
        foreach ($portfolio['experiences'] as $experience) {
            $csvData[] = [
                'Organization' => $experience['organization'],
                'Role' => $experience['role'],
                'Duration' => $experience['duration'],
                'Hours Contributed' => $experience['hours_contributed'],
                'Impact Score' => $experience['impact_score'],
                'Skills Gained' => implode('; ', $experience['skills_gained'] ?? []),
                'Start Date' => $experience['start_date'],
                'End Date' => $experience['end_date'] ?? 'Current',
                'Reference Verified' => $experience['reference_verified'] ? 'Yes' : 'No',
            ];
        }

        return [
            'format' => 'csv',
            'filename' => 'volunteering-portfolio-' . now()->format('Y-m-d') . '.csv',
            'data' => $csvData,
            'headers' => array_keys($csvData[0] ?? []),
        ];
    }

    /**
     * Export user profile data.
     */
    public function exportUserProfile(User $user): array
    {
        $user->load([
            'profile',
            'skills',
            'volunteeringInterests.category',
            'userVolunteeringHistory',
            'documents',
            'userAlumniOrganizations',
            'registrationSteps',
            'platformInterests'
        ]);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'profile' => $user->profile,
            'skills' => $user->skills,
            'volunteering_interests' => $user->volunteeringInterests,
            'volunteering_history' => $user->userVolunteeringHistory,
            'documents' => $user->documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->document_type,
                    'name' => $doc->file_name,
                    'verification_status' => $doc->verification_status,
                    'created_at' => $doc->created_at,
                ];
            }),
            'alumni_organizations' => $user->userAlumniOrganizations,
            'registration_steps' => $user->registrationSteps,
            'platform_interests' => $user->platformInterests,
            'statistics' => $this->getUserStatistics($user),
            'timeline' => $this->getProfileTimeline($user),
            'volunteering_impact' => $this->calculateVolunteeringImpact($user),
            'volunteering_portfolio' => $this->getVolunteeringPortfolio($user),
        ];
    }
}