<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the organizations in this category.
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'organization_category_id');
    }

    /**
     * Get active organizations in this category.
     */
    public function activeOrganizations(): HasMany
    {
        return $this->organizations()->where('status', 'active');
    }

    /**
     * Get verified organizations in this category.
     */
    public function verifiedOrganizations(): HasMany
    {
        return $this->organizations()->where('is_verified', true);
    }

    /**
     * Check if this category is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get organizations count for this category.
     */
    public function getOrganizationsCount(): int
    {
        return $this->organizations()->count();
    }

    /**
     * Get active organizations count for this category.
     */
    public function getActiveOrganizationsCount(): int
    {
        return $this->activeOrganizations()->count();
    }

    /**
     * Get verified organizations count for this category.
     */
    public function getVerifiedOrganizationsCount(): int
    {
        return $this->verifiedOrganizations()->count();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    public function scopeWithOrganizationsCount($query)
    {
        return $query->withCount('organizations');
    }

    public function scopeWithActiveOrganizationsCount($query)
    {
        return $query->withCount(['organizations as active_organizations_count' => function($q) {
            $q->where('status', 'active');
        }]);
    }
}