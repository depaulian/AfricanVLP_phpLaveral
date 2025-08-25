<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Auditable;

     // Constants for preferred_language
     public const LANGUAGE_ENGLISH = 'English';
     public const LANGUAGE_FRENCH = 'Français';
     public const LANGUAGE_PORTUGUESE = 'Português';
     public const LANGUAGE_ARABIC = 'العربية';
     public const LANGUAGES = [
         self::LANGUAGE_ENGLISH,
         self::LANGUAGE_FRENCH,
         self::LANGUAGE_PORTUGUESE,
         self::LANGUAGE_ARABIC,
     ];
 
     // Constants for time_commitment
     public const COMMITMENT_WEEKLY = 'Weekly';
     public const COMMITMENT_MONTHLY = 'Monthly';
     public const COMMITMENT_QUARTERLY = 'Quarterly';
     public const COMMITMENT_ANNUALLY = 'Annually';
     public const TIME_COMMITMENTS = [
         self::COMMITMENT_WEEKLY,
         self::COMMITMENT_MONTHLY,
         self::COMMITMENT_QUARTERLY,
         self::COMMITMENT_ANNUALLY,
     ];
 
     // Constants for volunteer_mode
     public const MODE_VIRTUAL = 'Virtual';
     public const MODE_PHYSICAL = 'Physical';
     public const MODE_BOTH = 'Both';
     public const VOLUNTEER_MODES = [
         self::MODE_VIRTUAL,
         self::MODE_PHYSICAL,
         self::MODE_BOTH,
     ];

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
        'profile_image_thumbnail',
        'profile_image_medium',
        'profile_image_large',
        'cv_url',
        'date_of_birth',
        'gender',
        'status',
        'is_admin',
        'email_verified_at',
        'email_verification_token',
        'password_reset_token',
        'last_login_at',
        'login_count',
        'fcm_token',
        'volunteer_notification_preferences',
        'registration_step',
        'registration_completed_at',
        'preferred_language',
        'time_commitment',
        'volunteer_mode',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'password_reset_token',
        'fcm_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'registration_completed_at' => 'datetime',
        'login_count' => 'integer',
        'registration_step' => 'integer',
        'is_admin' => 'boolean',
        'volunteer_notification_preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the user's display name (falls back to email if no name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->getFullNameAttribute() ?: $this->email;
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
     * Get the blogs authored by this user.
     */
    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'author_id');
    }

    /**
     * Get the user's volunteering interests.
     */
    public function volunteeringInterests(): HasMany
    {
        return $this->hasMany(UserVolunteeringInterest::class);
    }

    /**
     * Get the volunteering categories the user is interested in.
     */
    public function volunteeringCategories(): BelongsToMany
    {
        return $this->belongsToMany(VolunteeringCategory::class, 'user_volunteering_interests');
    }

    /**
     * Get the user's organization category interests.
     */
    public function organizationCategoryInterests(): HasMany
    {
        return $this->hasMany(UserVolunteeringOrganizationCategoryInterest::class);
    }

    /**
     * Get the organization categories the user is interested in.
     */
    public function organizationCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            OrganizationCategory::class, 
            'user_volunteering_organization_category_interests'
        );
    }

    // Status check methods
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function hasCompletedRegistration(): bool
    {
        return !is_null($this->registration_completed_at);
    }

    /**
     * Get the profile image URL.
     */
    public function getProfileImageUrl(string $size = 'medium'): string
    {
        $sizeField = $size === 'original' ? 'profile_image' : "profile_image_{$size}";
        
        if ($this->$sizeField) {
            return asset('storage/profiles/' . $this->$sizeField);
        }
        
        // Fallback to original if specific size doesn't exist
        if ($size !== 'original' && $this->profile_image) {
            return asset('storage/profiles/' . $this->profile_image);
        }
        
        return asset('images/default-avatar.png');
    }

    /**
     * Get the profile image URL attribute (for backward compatibility).
     */
    public function getProfileImageUrlAttribute(): string
    {
        return $this->getProfileImageUrl('medium');
    }

    /**
     * Get the CV URL with fallback.
     */
    public function getCvUrl(): ?string
    {
        if ($this->cv_url) {
            // If it's a full URL, return as-is, otherwise prepend storage path
            if (str_starts_with($this->cv_url, 'http')) {
                return $this->cv_url;
            }
            return asset('storage/cvs/' . $this->cv_url);
        }
        
        return null;
    }

    /**
     * Check if user has uploaded a CV.
     */
    public function hasCv(): bool
    {
        return !empty($this->cv_url);
    }

    /**
     * Get user's location string.
     */
    public function getLocationString(): string
    {
        $locationParts = array_filter([
            $this->city?->name,
            $this->country?->name
        ]);
        
        return implode(', ', $locationParts);
    }

    /**
     * Get user's age.
     */
    public function getAge(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Check if user has FCM token for notifications.
     */
    public function hasFcmToken(): bool
    {
        return !empty($this->fcm_token);
    }

    /**
     * Get volunteer notification preferences.
     */
    public function getVolunteerNotificationPreferences(): array
    {
        return $this->volunteer_notification_preferences ?: [];
    }

    /**
     * Check if user wants specific volunteer notifications.
     */
    public function wantsVolunteerNotification(string $type): bool
    {
        $preferences = $this->getVolunteerNotificationPreferences();
        return $preferences[$type] ?? true; // Default to true if not set
    }

    /**
     * Get registration progress percentage.
     */
    public function getRegistrationProgress(): int
    {
        $totalSteps = 5; // Assuming 5 registration steps
        return min(100, ($this->registration_step / $totalSteps) * 100);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeAdmin($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('is_admin', false);
    }

    public function scopeWithProfileImage($query)
    {
        return $query->whereNotNull('profile_image');
    }

    public function scopeWithCv($query)
    {
        return $query->whereNotNull('cv_url');
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeCompletedRegistration($query)
    {
        return $query->whereNotNull('registration_completed_at');
    }

    public function scopeIncompleteRegistration($query)
    {
        return $query->whereNull('registration_completed_at');
    }

    public function scopeByVolunteerMode($query, string $mode)
    {
        return $query->where('volunteer_mode', $mode);
    }

    public function scopeByTimeCommitment($query, string $commitment)
    {
        return $query->where('time_commitment', $commitment);
    }

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('preferred_language', $language);
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'login_count' => $this->login_count + 1,
        ]);
    }

    /**
     * Mark registration as completed.
     */
    public function completeRegistration(): void
    {
        $this->update([
            'registration_completed_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Check if user is interested in a specific volunteering category.
     */
    public function isInterestedInCategory(int $categoryId): bool
    {
        return $this->volunteeringCategories()->where('volunteering_categories.id', $categoryId)->exists();
    }

    /**
     * Check if user is interested in a specific organization category.
     */
    public function isInterestedInOrganizationCategory(int $organizationCategoryId): bool
    {
        return $this->organizationCategories()->where('organization_categories.id', $organizationCategoryId)->exists();
    }

    /**
     * Add volunteering category interest.
     */
    public function addVolunteeringInterest(int $categoryId): bool
    {
        if (!$this->isInterestedInCategory($categoryId)) {
            $this->volunteeringCategories()->attach($categoryId);
            return true;
        }
        return false;
    }

    /**
     * Remove volunteering category interest.
     */
    public function removeVolunteeringInterest(int $categoryId): bool
    {
        if ($this->isInterestedInCategory($categoryId)) {
            $this->volunteeringCategories()->detach($categoryId);
            return true;
        }
        return false;
    }

    /**
     * Add organization category interest.
     */
    public function addOrganizationCategoryInterest(int $organizationCategoryId): bool
    {
        if (!$this->isInterestedInOrganizationCategory($organizationCategoryId)) {
            $this->organizationCategories()->attach($organizationCategoryId);
            return true;
        }
        return false;
    }

    /**
     * Remove organization category interest.
     */
    public function removeOrganizationCategoryInterest(int $organizationCategoryId): bool
    {
        if ($this->isInterestedInOrganizationCategory($organizationCategoryId)) {
            $this->organizationCategories()->detach($organizationCategoryId);
            return true;
        }
        return false;
    }
}