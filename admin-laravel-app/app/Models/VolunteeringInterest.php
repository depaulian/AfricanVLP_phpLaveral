<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteeringInterest extends Model
{
    protected $table = 'volunteering_interests';

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'user_id',
        'volunteering_category_id',
        'interest_level',
        'notes',
        'status',
    ];

    protected $casts = [
        'interest_level' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the user that owns the volunteering interest.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the volunteering category.
     */
    public function volunteeringCategory(): BelongsTo
    {
        return $this->belongsTo(VolunteeringCategory::class);
    }

    /**
     * Check if the interest is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the interest level as a string.
     */
    public function getInterestLevelText(): string
    {
        return match($this->interest_level) {
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            4 => 'Very High',
            default => 'Unknown'
        };
    }
}