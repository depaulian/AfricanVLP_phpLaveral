<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVolunteeringInterest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'interest_level'
    ];

    /**
     * Get the user this interest belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category this interest is for
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VolunteeringCategory::class, 'category_id');
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by category
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by interest level
     */
    public function scopeByInterestLevel($query, $level)
    {
        return $query->where('interest_level', $level);
    }

    /**
     * Scope to get high interest items
     */
    public function scopeHighInterest($query)
    {
        return $query->where('interest_level', 'high');
    }

    /**
     * Get interest level badge color
     */
    public function getInterestLevelColorAttribute(): string
    {
        return match ($this->interest_level) {
            'high' => 'success',
            'medium' => 'warning',
            'low' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get formatted interest level
     */
    public function getFormattedInterestLevelAttribute(): string
    {
        return ucfirst($this->interest_level);
    }

    /**
     * Get interest level weight for matching algorithms
     */
    public function getInterestWeightAttribute(): int
    {
        return match ($this->interest_level) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 1
        };
    }
}