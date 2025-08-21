<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'description',
        'email',
        'phone_number',
        'website',
        'address',
        'city_id',
        'country_id',
        'category_id',
        'institution_type_id',
        'logo',
        'status',
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
        'created' => 'datetime',
        'modified' => 'datetime',
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
    public function categoryOfOrganization(): BelongsTo
    {
        return $this->belongsTo(CategoryOfOrganization::class, 'category_id');
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
     * Get the organization alumni.
     */
    public function alumni(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_alumni')
                    ->withPivot(['graduation_year', 'status', 'notes'])
                    ->withTimestamps();
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

    /**
     * Check if the organization is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the organization's logo URL.
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
     * Get upcoming events count.
     */
    public function getUpcomingEventsCount(): int
    {
        return $this->events()
                    ->where('start_date', '>', now())
                    ->where('status', 'active')
                    ->count();
    }
}