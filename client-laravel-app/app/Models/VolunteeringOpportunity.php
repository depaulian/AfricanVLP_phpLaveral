<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;
use App\Events\VolunteeringOpportunityCreated;

class VolunteeringOpportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'organization_id',
        'category_id',
        'role_id',
        'location_type',
        'address',
        'city_id',
        'country_id',
        'required_skills',
        'time_commitment',
        'start_date',
        'end_date',
        'application_deadline',
        'max_volunteers',
        'current_volunteers',
        'experience_level',
        'age_requirement',
        'background_check_required',
        'training_provided',
        'benefits',
        'contact_person',
        'contact_email',
        'contact_phone',
        'status',
        'featured',
        'created_by'
    ];

    protected $casts = [
        'required_skills' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'application_deadline' => 'date',
        'background_check_required' => 'boolean',
        'training_provided' => 'boolean',
        'featured' => 'boolean'
    ];

    /**
     * Boot the model and register event listeners
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($opportunity) {
            // Fire event when a new opportunity is created
            event(new VolunteeringOpportunityCreated($opportunity));
        });
    }

    /**
     * Get the organization this opportunity belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the category this opportunity belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VolunteeringCategory::class, 'category_id');
    }

    /**
     * Get the role for this opportunity
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(VolunteeringRole::class, 'role_id');
    }

    /**
     * Get the city for this opportunity
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country for this opportunity
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the user who created this opportunity
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all applications for this opportunity
     */
    public function applications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class, 'opportunity_id');
    }

    /**
     * Get accepted applications for this opportunity
     */
    public function acceptedApplications(): HasMany
    {
        return $this->applications()->where('status', 'accepted');
    }

    /**
     * Get pending applications for this opportunity
     */
    public function pendingApplications(): HasMany
    {
        return $this->applications()->where('status', 'pending');
    }

    /**
     * Get assignments through applications
     */
    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(VolunteerAssignment::class, VolunteerApplication::class, 'opportunity_id', 'application_id');
    }

    /**
     * Scope to get only active opportunities
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get featured opportunities
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope to get opportunities accepting applications
     */
    public function scopeAcceptingApplications($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('application_deadline')
                          ->orWhere('application_deadline', '>=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('max_volunteers')
                          ->orWhereRaw('current_volunteers < max_volunteers');
                    });
    }

    /**
     * Scope to filter by location type
     */
    public function scopeByLocationType($query, $locationType)
    {
        return $query->where('location_type', $locationType);
    }

    /**
     * Scope to filter by experience level
     */
    public function scopeByExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to search by title or description using full-text search
     */
    public function scopeSearch($query, $search)
    {
        // Use full-text search if available, fallback to LIKE
        if (config('database.default') === 'mysql') {
            return $query->whereRaw("MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)", [$search]);
        }
        
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope for optimized opportunity listing with eager loading
     */
    public function scopeWithBasicRelations($query)
    {
        return $query->with([
            'organization:id,name,slug',
            'category:id,name,slug',
            'city:id,name',
            'country:id,name'
        ]);
    }

    /**
     * Scope for paginated opportunities with performance optimization
     */
    public function scopePaginatedWithFilters($query, array $filters = [], int $perPage = 15)
    {
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['location_type'])) {
            $query->where('location_type', $filters['location_type']);
        }

        if (!empty($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['featured']) && $filters['featured']) {
            $query->where('featured', true);
        }

        // Default filters for active opportunities
        $query->active()
              ->acceptingApplications()
              ->withBasicRelations()
              ->orderBy('featured', 'desc')
              ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Check if opportunity is accepting applications
     */
    public function getIsAcceptingApplicationsAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->application_deadline && $this->application_deadline->isPast()) {
            return false;
        }

        if ($this->max_volunteers && $this->current_volunteers >= $this->max_volunteers) {
            return false;
        }

        return true;
    }

    /**
     * Get remaining spots
     */
    public function getSpotsRemainingAttribute(): ?int
    {
        return $this->max_volunteers ? $this->max_volunteers - $this->current_volunteers : null;
    }

    /**
     * Check if opportunity has deadline
     */
    public function getHasDeadlineAttribute(): bool
    {
        return !is_null($this->application_deadline);
    }

    /**
     * Get days until deadline
     */
    public function getDaysUntilDeadlineAttribute(): ?int
    {
        if (!$this->application_deadline) {
            return null;
        }

        return now()->diffInDays($this->application_deadline, false);
    }

    /**
     * Check if deadline is approaching (within 7 days)
     */
    public function getIsDeadlineApproachingAttribute(): bool
    {
        if (!$this->application_deadline) {
            return false;
        }

        $daysUntil = $this->days_until_deadline;
        return $daysUntil !== null && $daysUntil <= 7 && $daysUntil >= 0;
    }

    /**
     * Get formatted location
     */
    public function getFormattedLocationAttribute(): string
    {
        $location = [];

        if ($this->location_type === 'remote') {
            return 'Remote';
        }

        if ($this->city) {
            $location[] = $this->city->name;
        }

        if ($this->country) {
            $location[] = $this->country->name;
        }

        if (empty($location) && $this->address) {
            return $this->address;
        }

        return implode(', ', $location) ?: 'Location not specified';
    }

    /**
     * Get formatted required skills
     */
    public function getFormattedRequiredSkillsAttribute(): string
    {
        if (!$this->required_skills) {
            return 'No specific skills required';
        }

        return implode(', ', $this->required_skills);
    }

    /**
     * Check if opportunity requires specific skill
     */
    public function requiresSkill(string $skill): bool
    {
        if (!$this->required_skills) {
            return false;
        }

        return in_array($skill, $this->required_skills);
    }

    /**
     * Get application count
     */
    public function getApplicationsCountAttribute(): int
    {
        return $this->applications()->count();
    }

    /**
     * Get pending applications count
     */
    public function getPendingApplicationsCountAttribute(): int
    {
        return $this->pendingApplications()->count();
    }

    /**
     * Increment current volunteers count
     */
    public function incrementVolunteers(): void
    {
        $this->increment('current_volunteers');
    }

    /**
     * Decrement current volunteers count
     */
    public function decrementVolunteers(): void
    {
        $this->decrement('current_volunteers');
    }
}