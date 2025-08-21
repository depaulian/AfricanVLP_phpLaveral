<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserVolunteeringHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'organization_name',
        'role_title',
        'description',
        'start_date',
        'end_date',
        'hours_contributed',
        'skills_gained',
        'reference_contact',
        'reference_email',
        'reference_phone',
        'reference_position',
        'reference_verified',
        'reference_verified_at',
        'is_current',
        'impact_description',
        'impact_metrics',
        'people_helped',
        'funds_raised',
        'events_organized',
        'certificates',
        'recognitions',
        'portfolio_visible',
        'verification_documents',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'skills_gained' => 'array',
        'is_current' => 'boolean',
        'hours_contributed' => 'integer',
        'people_helped' => 'integer',
        'funds_raised' => 'decimal:2',
        'events_organized' => 'integer',
        'impact_metrics' => 'array',
        'certificates' => 'array',
        'recognitions' => 'array',
        'verification_documents' => 'array',
        'reference_verified' => 'boolean',
        'reference_verified_at' => 'datetime',
        'portfolio_visible' => 'boolean',
    ];

    /**
     * Get the user that owns the volunteering history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization associated with this volunteering history.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the duration of volunteering in a readable format.
     */
    public function getDurationAttribute(): string
    {
        if ($this->is_current) {
            return $this->start_date->format('M Y') . ' - Present';
        }
        
        if ($this->end_date) {
            return $this->start_date->format('M Y') . ' - ' . $this->end_date->format('M Y');
        }
        
        return $this->start_date->format('M Y');
    }

    /**
     * Get the duration in months.
     */
    public function getDurationInMonthsAttribute(): int
    {
        $endDate = $this->is_current ? now() : $this->end_date;
        return $this->start_date->diffInMonths($endDate);
    }

    /**
     * Get skills gained as a comma-separated string.
     */
    public function getSkillsGainedListAttribute(): string
    {
        return collect($this->skills_gained)->implode(', ');
    }

    /**
     * Get the organization name (either from relationship or stored name).
     */
    public function getOrganizationNameAttribute(): string
    {
        return $this->organization?->name ?? $this->attributes['organization_name'] ?? 'Unknown Organization';
    }

    /**
     * Scope to get current volunteering positions.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get completed volunteering positions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_current', false);
    }

    /**
     * Scope to order by start date (most recent first).
     */
    public function scopeOrderByDate(Builder $query): Builder
    {
        return $query->orderByDesc('start_date');
    }

    /**
     * Check if this volunteering position has reference contact information.
     */
    public function hasReferenceContact(): bool
    {
        return !empty($this->reference_contact) && 
               (!empty($this->reference_email) || !empty($this->reference_phone));
    }

    /**
     * Get the total experience in years.
     */
    public function getExperienceInYearsAttribute(): float
    {
        return round($this->duration_in_months / 12, 1);
    }

    /**
     * Calculate and get the impact score based on various metrics.
     */
    public function getImpactScoreAttribute(): int
    {
        $score = 0;
        
        // Base score from hours contributed
        $score += min($this->hours_contributed * 0.1, 50);
        
        // Additional points for people helped
        if ($this->people_helped) {
            $score += min($this->people_helped * 0.5, 30);
        }
        
        // Additional points for funds raised
        if ($this->funds_raised) {
            $score += min($this->funds_raised * 0.01, 20);
        }
        
        // Additional points for events organized
        if ($this->events_organized) {
            $score += min($this->events_organized * 2, 15);
        }
        
        // Bonus for having certificates
        if ($this->certificates && count($this->certificates) > 0) {
            $score += count($this->certificates) * 5;
        }
        
        // Bonus for having recognitions
        if ($this->recognitions && count($this->recognitions) > 0) {
            $score += count($this->recognitions) * 3;
        }
        
        // Bonus for verified reference
        if ($this->reference_verified) {
            $score += 10;
        }
        
        return min(round($score), 100);
    }

    /**
     * Get formatted impact summary.
     */
    public function getImpactSummaryAttribute(): array
    {
        $summary = [];
        
        if ($this->hours_contributed) {
            $summary[] = $this->hours_contributed . ' hours contributed';
        }
        
        if ($this->people_helped) {
            $summary[] = $this->people_helped . ' people helped';
        }
        
        if ($this->funds_raised) {
            $summary[] = '$' . number_format($this->funds_raised, 2) . ' raised';
        }
        
        if ($this->events_organized) {
            $summary[] = $this->events_organized . ' events organized';
        }
        
        return $summary;
    }

    /**
     * Get certificates with formatted data.
     */
    public function getFormattedCertificatesAttribute(): array
    {
        if (!$this->certificates) {
            return [];
        }
        
        return collect($this->certificates)->map(function ($cert) {
            return [
                'name' => $cert['name'] ?? 'Certificate',
                'issuer' => $cert['issuer'] ?? 'Unknown',
                'date_issued' => $cert['date_issued'] ?? null,
                'certificate_url' => $cert['certificate_url'] ?? null,
                'verification_url' => $cert['verification_url'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Get recognitions with formatted data.
     */
    public function getFormattedRecognitionsAttribute(): array
    {
        if (!$this->recognitions) {
            return [];
        }
        
        return collect($this->recognitions)->map(function ($recognition) {
            return [
                'title' => $recognition['title'] ?? 'Recognition',
                'description' => $recognition['description'] ?? '',
                'date_received' => $recognition['date_received'] ?? null,
                'awarded_by' => $recognition['awarded_by'] ?? 'Unknown',
            ];
        })->toArray();
    }

    /**
     * Add a certificate to this volunteering experience.
     */
    public function addCertificate(array $certificateData): void
    {
        $certificates = $this->certificates ?? [];
        $certificates[] = array_merge($certificateData, [
            'added_at' => now()->toISOString(),
        ]);
        
        $this->update(['certificates' => $certificates]);
    }

    /**
     * Add a recognition to this volunteering experience.
     */
    public function addRecognition(array $recognitionData): void
    {
        $recognitions = $this->recognitions ?? [];
        $recognitions[] = array_merge($recognitionData, [
            'added_at' => now()->toISOString(),
        ]);
        
        $this->update(['recognitions' => $recognitions]);
    }

    /**
     * Verify the reference contact.
     */
    public function verifyReference(): void
    {
        $this->update([
            'reference_verified' => true,
            'reference_verified_at' => now(),
        ]);
    }

    /**
     * Check if this experience is suitable for portfolio display.
     */
    public function isSuitableForPortfolio(): bool
    {
        return $this->portfolio_visible && 
               $this->hours_contributed >= 10 && 
               !empty($this->description) &&
               ($this->hasReferenceContact() || $this->reference_verified);
    }

    /**
     * Get portfolio export data.
     */
    public function getPortfolioDataAttribute(): array
    {
        return [
            'organization' => $this->organization_name,
            'role' => $this->role_title,
            'duration' => $this->duration,
            'hours_contributed' => $this->hours_contributed,
            'description' => $this->description,
            'impact_description' => $this->impact_description,
            'impact_summary' => $this->impact_summary,
            'impact_score' => $this->impact_score,
            'skills_gained' => $this->skills_gained,
            'certificates' => $this->formatted_certificates,
            'recognitions' => $this->formatted_recognitions,
            'reference_verified' => $this->reference_verified,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_current' => $this->is_current,
        ];
    }
}