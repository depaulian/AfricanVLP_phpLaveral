<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SecurityEvent;
use App\Models\UserSession;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'address',
        'city_id',
        'country_id',
        'profile_image',
        'date_of_birth',
        'gender',
        'status',
        'is_admin',
        'email_verified_at',
        'email_verification_token',
        'password_reset_token',
        'last_login',
        'login_count',
        'volunteer_notifications_enabled',
        'trending_notifications_enabled',
        'digest_notifications_enabled',
        'immediate_notifications_enabled',
        'email_notifications_enabled',
        'bio',
        'registration_completed_at',
        'onboarding_completed',
        'registration_metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'password_reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'last_login' => 'datetime',
        'login_count' => 'integer',
        'is_admin' => 'boolean',
        'volunteer_notifications_enabled' => 'boolean',
        'trending_notifications_enabled' => 'boolean',
        'digest_notifications_enabled' => 'boolean',
        'immediate_notifications_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'created' => 'datetime',
        'modified' => 'datetime',
        'registration_completed_at' => 'datetime',
        'onboarding_completed' => 'boolean',
        'registration_metadata' => 'array',
    ];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the city that the user belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country that the user belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the organizations that the user belongs to.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
                    ->withPivot('role', 'status', 'joined_date')
                    ->withTimestamps();
    }

    /**
     * Get the organizations where the user is an alumni.
     */
    public function alumniOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_alumni')
                    ->withPivot('status', 'graduation_year')
                    ->withTimestamps();
    }

    /**
     * Get the user's volunteering interests.
     */
    public function volunteeringInterests(): HasMany
    {
        return $this->hasMany(UserVolunteeringInterest::class);
    }

    /**
     * Get the user's skills.
     */
    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    /**
     * Get the user's volunteer applications.
     */
    public function volunteerApplications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class);
    }

    /**
     * Get the user's volunteer assignments through applications.
     */
    public function volunteerAssignments()
    {
        return $this->hasManyThrough(VolunteerAssignment::class, VolunteerApplication::class);
    }

    /**
     * Get the user's volunteering history.
     */
    public function volunteeringHistory(): HasMany
    {
        return $this->hasMany(VolunteeringHistory::class);
    }

    /**
     * Get the user's name attribute (for compatibility).
     */
    public function getNameAttribute(): string
    {
        return $this->getFullNameAttribute();
    }

    /**
     * Get the user's conversations.
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withPivot('joined_at', 'left_at')
                    ->withTimestamps();
    }

    /**
     * Get the user's forum posts.
     */
    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'author_id');
    }

    /**
     * Get the user's forum threads.
     */
    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'author_id');
    }

    /**
     * Get the user's forum votes.
     */
    public function forumVotes(): HasMany
    {
        return $this->hasMany(ForumVote::class);
    }

    /**
     * Reports made by the user.
     */
    public function reportsMade(): HasMany
    {
        return $this->hasMany(ForumReport::class, 'reporter_id');
    }

    /**
     * Reports moderated by the user.
     */
    public function moderatedReports(): HasMany
    {
        return $this->hasMany(ForumReport::class, 'moderator_id');
    }

    /**
     * Moderation logs performed by the user (as moderator).
     */
    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ForumModerationLog::class, 'moderator_id');
    }

    /**
     * Warnings issued by the user (as moderator).
     */
    public function warningsIssued(): HasMany
    {
        return $this->hasMany(ForumWarning::class, 'moderator_id');
    }

    /**
     * Warnings received by the user.
     */
    public function warningsReceived(): HasMany
    {
        return $this->hasMany(ForumWarning::class, 'user_id');
    }

    /**
     * Forum analytics generated by the user.
     */
    public function forumAnalytics(): HasMany
    {
        return $this->hasMany(ForumAnalytic::class);
    }

    /**
     * Forum notification preferences for the user.
     */
    public function forumNotificationPreferences(): HasMany
    {
        return $this->hasMany(ForumNotificationPreference::class);
    }

    /**
     * Get the user's notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user's email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user belongs to a specific organization.
     */
    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->exists();
    }

    /**
     * Get user's active volunteering opportunities.
     */
    public function getActiveVolunteeringOpportunities()
    {
        return $this->volunteeringHistory()
                    ->where('status', 'active')
                    ->with('volunteeringOpportunity')
                    ->get();
    }

    /**
     * Get the user's profile.
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the user's volunteering history.
     */
    public function userVolunteeringHistory()
    {
        return $this->hasMany(UserVolunteeringHistory::class);
    }

    /**
     * Get the user's documents.
     */
    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    /**
     * Get the user's alumni organizations.
     */
    public function userAlumniOrganizations()
    {
        return $this->hasMany(UserAlumniOrganization::class);
    }

    /**
     * Get the user's registration steps.
     */
    public function registrationSteps()
    {
        return $this->hasMany(UserRegistrationStep::class);
    }

    /**
     * Get the user's platform interests.
     */
    public function platformInterests()
    {
        return $this->hasMany(UserPlatformInterest::class);
    }

    /**
     * Get the user's profile images.
     */
    public function profileImages()
    {
        return $this->hasMany(ProfileImage::class);
    }

    /**
     * Get the user's current profile image.
     */
    public function currentProfileImage()
    {
        return $this->hasOne(ProfileImage::class)->where('is_current', true)->where('status', 'approved');
    }

    /**
     * Get user's achievements
     */
    public function achievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Get user's certificates
     */
    public function certificates()
    {
        return $this->hasMany(VolunteerCertificate::class);
    }

    /**
     * Get testimonials written about this user
     */
    public function testimonials()
    {
        return $this->hasMany(VolunteerTestimonial::class, 'volunteer_id');
    }

    /**
     * Get testimonials written by this user
     */
    public function writtenTestimonials()
    {
        return $this->hasMany(VolunteerTestimonial::class, 'author_id');
    }

    /**
     * Get user's achievement points
     */
    public function getTotalAchievementPointsAttribute(): int
    {
        return $this->achievements()
            ->with('achievement')
            ->get()
            ->sum('achievement.points');
    }

    /**
     * Get user's volunteer rank based on hours
     */
    public function getVolunteerRankAttribute(): string
    {
        $totalHours = $this->volunteerApplications()
            ->whereHas('assignments.timeLogs', function ($q) {
                $q->where('supervisor_approved', true);
            })
            ->with('assignments.timeLogs')
            ->get()
            ->sum(function ($application) {
                return $application->assignments->sum(function ($assignment) {
                    return $assignment->timeLogs->where('supervisor_approved', true)->sum('hours');
                });
            });

        return match (true) {
            $totalHours >= 1000 => 'Master Volunteer',
            $totalHours >= 500 => 'Expert Volunteer',
            $totalHours >= 250 => 'Advanced Volunteer',
            $totalHours >= 100 => 'Experienced Volunteer',
            $totalHours >= 50 => 'Active Volunteer',
            $totalHours >= 25 => 'Regular Volunteer',
            $totalHours >= 10 => 'Contributing Volunteer',
            $totalHours > 0 => 'New Volunteer',
            default => 'Aspiring Volunteer',
        };
    }

    /**
     * Get impact records for this user
     */
    public function impactRecords(): HasMany
    {
        return $this->hasMany(VolunteerImpactRecord::class);
    }

    /**
     * Get beneficiary feedback about this user
     */
    public function beneficiaryFeedback(): HasMany
    {
        return $this->hasMany(BeneficiaryFeedback::class, 'volunteer_id');
    }

    /**
     * Get impact stories authored by this user
     */
    public function authoredImpactStories(): HasMany
    {
        return $this->hasMany(ImpactStory::class, 'author_id');
    }

    /**
     * Get impact stories featuring this user as volunteer
     */
    public function featuredImpactStories(): HasMany
    {
        return $this->hasMany(ImpactStory::class, 'volunteer_id');
    }

    /**
     * Get the volunteer notification preferences for the user
     */
    public function volunteerNotificationPreferences(): HasMany
    {
        return $this->hasMany(VolunteerNotificationPreference::class);
    }

    /**
     * Get the volunteer notifications for the user
     */
    public function volunteerNotifications(): HasMany
    {
        return $this->hasMany(VolunteerNotification::class);
    }

    /**
     * Get unread volunteer notifications
     */
    public function unreadVolunteerNotifications(): HasMany
    {
        return $this->hasMany(VolunteerNotification::class)->where('is_read', false);
    }

    /**
     * Get unread volunteer notification count
     */
    public function getUnreadVolunteerNotificationCountAttribute(): int
    {
        return $this->unreadVolunteerNotifications()->count();
    }

    /**
     * Check if user should receive a specific type of notification on a channel
     */
    public function shouldReceiveVolunteerNotification(string $type, string $channel): bool
    {
        return VolunteerNotificationPreference::shouldReceiveNotification($this, $type, $channel);
    }

    /**
     * Get enabled notification channels for a specific type
     */
    public function getEnabledNotificationChannels(string $type): array
    {
        return VolunteerNotificationPreference::getEnabledChannelsForUser($this, $type);
    }

    /**
     * Initialize default notification preferences for the user
     */
    public function initializeNotificationPreferences(): void
    {
        VolunteerNotificationPreference::createDefaultsForUser($this);
    }

    /**
     * Get the user's profile completion percentage.
     */
    public function getProfileCompletionPercentageAttribute(): int
    {
        return $this->profile?->calculateCompletionPercentage() ?? 0;
    }

    /**
     * Check if the user has completed registration.
     */
    public function hasCompletedRegistration(): bool
    {
        $requiredSteps = ['basic_info', 'profile_details', 'interests', 'verification'];
        
        $completedSteps = $this->registrationSteps()
            ->whereIn('step_name', $requiredSteps)
            ->where('is_completed', true)
            ->count();
            
        return $completedSteps === count($requiredSteps);
    }

    /**
     * Get the next registration step for the user.
     */
    public function getNextRegistrationStep(): ?string
    {
        $steps = ['basic_info', 'profile_details', 'interests', 'verification'];
        
        foreach ($steps as $step) {
            $stepRecord = $this->registrationSteps()->where('step_name', $step)->first();
            if (!$stepRecord || !$stepRecord->is_completed) {
                return $step;
            }
        }
        
        return null;
    }

    /**
     * Get the user's registration progress.
     */
    public function getRegistrationProgressAttribute(): array
    {
        $steps = ['basic_info', 'profile_details', 'interests', 'verification'];
        $progress = [];
        
        foreach ($steps as $step) {
            $stepRecord = $this->registrationSteps()->where('step_name', $step)->first();
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
     * Initialize default platform interests for the user.
     */
    public function initializePlatformInterests(): void
    {
        UserPlatformInterest::createDefaultsForUser($this);
    }

    /**
     * Get user's verified documents count.
     */
    public function getVerifiedDocumentsCountAttribute(): int
    {
        return $this->documents()->verified()->count();
    }

    /**
     * Get user's verified alumni organizations count.
     */
    public function getVerifiedAlumniCountAttribute(): int
    {
        return $this->userAlumniOrganizations()->verified()->count();
    }

    /**
     * Check if user has a complete profile.
     */
    public function hasCompleteProfile(): bool
    {
        return $this->profile && $this->profile->isComplete();
    }

    /**
     * Check if registration is complete (enhanced version).
     */
    public function isRegistrationComplete(): bool
    {
        return !is_null($this->registration_completed_at);
    }

    /**
     * Mark registration as complete.
     */
    public function markRegistrationComplete(): void
    {
        $this->update([
            'registration_completed_at' => now(),
            'onboarding_completed' => true
        ]);
    }

    /**
     * Get registration completion time in minutes.
     */
    public function getRegistrationCompletionTimeAttribute(): ?int
    {
        if (!$this->registration_completed_at) {
            return null;
        }

        return $this->created->diffInMinutes($this->registration_completed_at);
    }

    /**
     * Check if user has role (for admin checks).
     */
    public function hasRole(string $role): bool
    {
        return match ($role) {
            'admin' => $this->is_admin,
            'user' => !$this->is_admin,
            default => false
        };
    }

    /**
     * Get registration abandonment status.
     */
    public function getRegistrationAbandonmentStatusAttribute(): array
    {
        if ($this->isRegistrationComplete()) {
            return [
                'is_abandoned' => false,
                'status' => 'completed'
            ];
        }

        $daysSinceStart = $this->created->diffInDays(now());
        $hasSteps = $this->registrationSteps()->exists();

        if (!$hasSteps) {
            return [
                'is_abandoned' => $daysSinceStart > 1,
                'status' => 'not_started',
                'days_since_start' => $daysSinceStart
            ];
        }

        $completedSteps = $this->registrationSteps()->where('is_completed', true)->count();
        $totalSteps = $this->registrationSteps()->count();

        return [
            'is_abandoned' => $daysSinceStart > 7 && $completedSteps < $totalSteps,
            'status' => $completedSteps > 0 ? 'in_progress' : 'started',
            'days_since_start' => $daysSinceStart,
            'completion_percentage' => $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0
        ];
    }

    /**
     * Get the user's profile activity logs.
     */
    public function profileActivityLogs(): HasMany
    {
        return $this->hasMany(ProfileActivityLog::class);
    }

    /**
     * Get profile activity logs where this user is the target.
     */
    public function targetedProfileActivityLogs(): HasMany
    {
        return $this->hasMany(ProfileActivityLog::class, 'target_user_id');
    }

    /**
     * Get recent profile activities for this user.
     */
    public function getRecentProfileActivitiesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->profileActivityLogs()
            ->with(['targetUser:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    /**
     * Get profile activity statistics for this user.
     */
    public function getProfileActivityStatsAttribute(): array
    {
        return ProfileActivityLog::getUserActivityStats($this->id);
    }

    /**
     * Get the user's profile achievements.
     */
    public function profileAchievements(): HasMany
    {
        return $this->hasMany(ProfileAchievement::class);
    }

    /**
     * Get the user's profile score.
     */
    public function profileScore()
    {
        return $this->hasOne(ProfileScore::class);
    }

    /**
     * Get the user's volunteer time logs.
     */
    public function volunteerTimeLogs(): HasMany
    {
        return $this->hasMany(VolunteerTimeLog::class);
    }

    /**
     * Get recent achievements for this user.
     */
    public function getRecentAchievementsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->profileAchievements()
            ->where('earned_at', '>=', now()->subDays(30))
            ->orderByDesc('earned_at')
            ->get();
    }

    /**
     * Get featured achievements for this user.
     */
    public function getFeaturedAchievementsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->profileAchievements()
            ->where('is_featured', true)
            ->orderByDesc('earned_at')
            ->get();
    }

    /**
     * Get total achievement points for this user.
     */
    public function getTotalAchievementPointsFromProfileAttribute(): int
    {
        return $this->profileAchievements()->sum('points_awarded');
    }
}