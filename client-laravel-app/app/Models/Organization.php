<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'description',
        'email',
        'phone_number',
        'website',
        'address',
        'city_id',
        'country_id',
        'organization_category_id',
        'institution_type_id',
        'logo',
        'status',
        'is_verified',
        'is_featured',
        'facebook_url',
        'twitter_url',
        'linkedin_url',
        'instagram_url',
        'latitude',
        'longitude',
        'established_year',
        'employee_count',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'established_year' => 'integer',
        'employee_count' => 'integer',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the city that the organization belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country that the organization belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the category of the organization.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(OrganizationCategory::class, 'organization_category_id');
    }

    /**
     * Get the category of the organization (alias for consistency).
     */
    public function organizationCategory(): BelongsTo
    {
        return $this->category();
    }

    /**
     * Get the institution type.
     */
    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }

    /**
     * Get the users that belong to this organization.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
                    ->withPivot('role', 'status', 'joined_date')
                    ->withTimestamps();
    }

    /**
     * Get the alumni of this organization.
     */
    public function alumni(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_alumni')
                    ->withPivot('status', 'graduation_year')
                    ->withTimestamps();
    }

    /**
     * Get the events organized by this organization.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the news published by this organization.
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    /**
     * Get the blogs published by this organization.
     */
    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    /**
     * Get the resources published by this organization.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Get the offices of this organization.
     */
    public function offices(): HasMany
    {
        return $this->hasMany(OrganizationOffice::class);
    }

    /**
     * Get the forum threads for this organization.
     */
    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    /**
     * Get the organization invitations.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    /**
     * Get the volunteering histories for this organization.
     */
    public function volunteeringHistories(): HasMany
    {
        return $this->hasMany(VolunteeringHistory::class);
    }

    /**
     * Get the volunteering opportunities for this organization.
     */
    public function volunteeringOpportunities(): HasMany
    {
        return $this->hasMany(VolunteeringOpportunity::class);
    }

    /**
     * Get the temporary user invitations for this organization.
     */
    public function temporaryUserInvitations(): HasMany
    {
        return $this->hasMany(TmpOrganizationUser::class);
    }

    // Status check methods
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

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Get the organization's logo URL.
     */
    public function getLogoUrl(): string
    {
        if ($this->logo) {
            return asset('storage/logos/' . $this->logo);
        }
        return asset('images/default-organization-logo.png');
    }

    /**
     * Get the organization's logo URL attribute.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/logos/' . $this->logo) : null;
    }

    /**
     * Get the organization's social media links.
     */
    public function getSocialMediaLinks(): array
    {
        return array_filter([
            'facebook' => $this->facebook_url,
            'twitter' => $this->twitter_url,
            'linkedin' => $this->linkedin_url,
            'instagram' => $this->instagram_url,
        ]);
    }

    /**
     * Check if organization has social media presence.
     */
    public function hasSocialMedia(): bool
    {
        return !empty($this->getSocialMediaLinks());
    }

    /**
     * Check if user has access to this organization.
     */
    public function userHasAccess(int $userId): bool
    {
        return $this->users()
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->exists();
    }

    /**
     * Get active members count.
     */
    public function getActiveMembersCount(): int
    {
        return $this->users()
                    ->where('status', 'active')
                    ->count();
    }

    /**
     * Get total members count (including inactive).
     */
    public function getTotalMembersCount(): int
    {
        return $this->users()->count();
    }

    /**
     * Get alumni count.
     */
    public function getAlumniCount(): int
    {
        return $this->alumni()->count();
    }

    /**
     * Get upcoming events count.
     */
    public function getUpcomingEventsCount(): int
    {
        return $this->events()
                    ->where('start_date', '>', now())
                    ->where('status', 'active')
                    ->count();
    }

    /**
     * Get published news count.
     */
    public function getPublishedNewsCount(): int
    {
        return $this->news()
                    ->where('status', 'published')
                    ->count();
    }

    /**
     * Get published blogs count.
     */
    public function getPublishedBlogsCount(): int
    {
        return $this->blogs()
                    ->where('status', 'published')
                    ->count();
    }

    /**
     * Get published resources count.
     */
    public function getPublishedResourcesCount(): int
    {
        return $this->resources()
                    ->where('status', 'published')
                    ->count();
    }

    /**
     * Get organization's full address.
     */
    public function getFullAddress(): string
    {
        $addressParts = array_filter([
            $this->address,
            $this->city?->name,
            $this->country?->name
        ]);
        
        return implode(', ', $addressParts);
    }

    /**
     * Get organization's age in years.
     */
    public function getAge(): ?int
    {
        return $this->established_year ? now()->year - $this->established_year : null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('organization_category_id', $categoryId);
    }

    public function scopeByInstitutionType($query, int $institutionTypeId)
    {
        return $query->where('institution_type_id', $institutionTypeId);
    }

    public function scopeByCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude');
    }

    /**
     * Get organization statistics.
     */
    public function getStatistics(): array
    {
        return [
            'active_members' => $this->getActiveMembersCount(),
            'total_members' => $this->getTotalMembersCount(),
            'alumni' => $this->getAlumniCount(),
            'upcoming_events' => $this->getUpcomingEventsCount(),
            'published_news' => $this->getPublishedNewsCount(),
            'published_blogs' => $this->getPublishedBlogsCount(),
            'published_resources' => $this->getPublishedResourcesCount(),
        ];
    }
}