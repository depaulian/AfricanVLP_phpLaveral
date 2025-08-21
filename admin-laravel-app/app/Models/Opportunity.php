<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'requirements',
        'responsibilities',
        'benefits',
        'organization_id',
        'category_id',
        'type',
        'location',
        'remote_allowed',
        'duration',
        'time_commitment',
        'start_date',
        'end_date',
        'application_deadline',
        'status',
        'featured',
        'contact_email',
        'contact_phone',
        'external_url',
        'skills_required',
        'experience_level',
        'language_requirements',
        'age_requirements',
        'education_requirements',
        'max_applicants',
        'current_applicants',
        'views_count',
        'applications_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'tags'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'application_deadline' => 'datetime',
        'remote_allowed' => 'boolean',
        'featured' => 'boolean',
        'skills_required' => 'array',
        'language_requirements' => 'array',
        'tags' => 'array',
        'views_count' => 'integer',
        'applications_count' => 'integer',
        'max_applicants' => 'integer',
        'current_applicants' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    // Boot method to generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($opportunity) {
            if (empty($opportunity->slug)) {
                $opportunity->slug = Str::slug($opportunity->title);
            }
        });

        static::updating(function ($opportunity) {
            if ($opportunity->isDirty('title') && empty($opportunity->slug)) {
                $opportunity->slug = Str::slug($opportunity->title);
            }
        });
    }

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(OpportunityCategory::class, 'category_id');
    }

    public function applications()
    {
        return $this->hasMany(OpportunityApplication::class);
    }

    public function applicants()
    {
        return $this->belongsToMany(User::class, 'opportunity_applications')
                    ->withPivot(['status', 'applied_at', 'message'])
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('application_deadline', '>', now());
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRemoteAllowed($query)
    {
        return $query->where('remote_allowed', true);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    public function scopeByExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    public function scopeAcceptingApplications($query)
    {
        return $query->where('application_deadline', '>', now())
                    ->where(function($q) {
                        $q->whereNull('max_applicants')
                          ->orWhereRaw('current_applicants < max_applicants');
                    });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('requirements', 'like', "%{$search}%")
              ->orWhere('responsibilities', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && $this->application_deadline > now();
    }

    public function isFeatured()
    {
        return $this->featured;
    }

    public function isAcceptingApplications()
    {
        return $this->application_deadline > now() && 
               (is_null($this->max_applicants) || $this->current_applicants < $this->max_applicants);
    }

    public function isExpired()
    {
        return $this->application_deadline <= now();
    }

    public function isFull()
    {
        return !is_null($this->max_applicants) && $this->current_applicants >= $this->max_applicants;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getDaysUntilDeadline()
    {
        return now()->diffInDays($this->application_deadline, false);
    }

    public function getApplicationProgress()
    {
        if (is_null($this->max_applicants)) {
            return null;
        }
        
        return ($this->current_applicants / $this->max_applicants) * 100;
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function incrementApplications()
    {
        $this->increment('applications_count');
        $this->increment('current_applicants');
    }

    public function decrementApplications()
    {
        $this->decrement('applications_count');
        $this->decrement('current_applicants');
    }

    // URL helpers
    public function getUrl()
    {
        return route('opportunities.show', $this->slug);
    }

    public function getAdminUrl()
    {
        return route('admin.opportunities.show', $this->id);
    }

    public function getApplyUrl()
    {
        return route('opportunities.apply', $this->slug);
    }

    // Status badge helper
    public function getStatusBadge()
    {
        $badges = [
            'draft' => 'bg-gray-100 text-gray-800',
            'active' => 'bg-green-100 text-green-800',
            'paused' => 'bg-yellow-100 text-yellow-800',
            'closed' => 'bg-red-100 text-red-800',
            'archived' => 'bg-gray-100 text-gray-600'
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    // Type badge helper
    public function getTypeBadge()
    {
        $badges = [
            'volunteer' => 'bg-blue-100 text-blue-800',
            'internship' => 'bg-purple-100 text-purple-800',
            'job' => 'bg-green-100 text-green-800',
            'fellowship' => 'bg-yellow-100 text-yellow-800',
            'scholarship' => 'bg-indigo-100 text-indigo-800',
            'grant' => 'bg-pink-100 text-pink-800',
            'competition' => 'bg-red-100 text-red-800'
        ];

        return $badges[$this->type] ?? 'bg-gray-100 text-gray-800';
    }
}
