<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteeringDuration extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'description',
        'hours_per_week',
        'weeks_duration',
        'total_hours',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'hours_per_week' => 'decimal:2',
        'weeks_duration' => 'integer',
        'total_hours' => 'decimal:2',
        'sort_order' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the volunteering opportunities for this duration.
     */
    public function volunteeringOpportunities(): HasMany
    {
        return $this->hasMany(VolunteeringOpportunity::class);
    }

    /**
     * Check if the duration is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the duration as a formatted string.
     */
    public function getFormattedDuration(): string
    {
        $parts = [];

        if ($this->hours_per_week) {
            $parts[] = $this->hours_per_week . ' hours/week';
        }

        if ($this->weeks_duration) {
            $parts[] = $this->weeks_duration . ' weeks';
        }

        if ($this->total_hours) {
            $parts[] = $this->total_hours . ' total hours';
        }

        return !empty($parts) ? implode(', ', $parts) : $this->name;
    }

    /**
     * Get the commitment level based on total hours.
     */
    public function getCommitmentLevel(): string
    {
        if (!$this->total_hours) {
            return 'Flexible';
        }

        if ($this->total_hours <= 10) {
            return 'Light';
        } elseif ($this->total_hours <= 40) {
            return 'Moderate';
        } elseif ($this->total_hours <= 100) {
            return 'Heavy';
        } else {
            return 'Intensive';
        }
    }

    /**
     * Get active opportunities count for this duration.
     */
    public function getActiveOpportunitiesCount(): int
    {
        return $this->volunteeringOpportunities()
                    ->where('status', 'active')
                    ->where('end_date', '>', now())
                    ->count();
    }

    /**
     * Calculate total hours if not set.
     */
    public function calculateTotalHours(): float
    {
        if ($this->total_hours) {
            return $this->total_hours;
        }

        if ($this->hours_per_week && $this->weeks_duration) {
            return $this->hours_per_week * $this->weeks_duration;
        }

        return 0;
    }

    /**
     * Scope to get active durations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get durations ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}