<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteeringHistory extends Model
{
    protected $table = 'volunteering_histories';

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'user_id',
        'volunteering_oppurtunity_id',
        'organization_id',
        'status',
        'applied_date',
        'start_date',
        'end_date',
        'hours_completed',
        'feedback',
        'rating',
        'notes',
        'certificate_issued',
    ];

    protected $casts = [
        'applied_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'hours_completed' => 'decimal:2',
        'rating' => 'integer',
        'certificate_issued' => 'boolean',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the user that owns the volunteering history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the volunteering opportunity.
     */
    public function volunteeringOpportunity(): BelongsTo
    {
        return $this->belongsTo(VolunteeringOpportunity::class, 'volunteering_oppurtunity_id');
    }

    /**
     * Get the organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if the volunteering is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the volunteering is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the status as a formatted string.
     */
    public function getStatusText(): string
    {
        return match($this->status) {
            'applied' => 'Applied',
            'accepted' => 'Accepted',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Get the duration in days.
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }
}