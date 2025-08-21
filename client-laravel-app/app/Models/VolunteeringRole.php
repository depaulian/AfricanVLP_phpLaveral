<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteeringRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category_id',
        'required_skills',
        'time_commitment',
        'experience_level',
        'status',
        'settings'
    ];

    protected $casts = [
        'required_skills' => 'array',
        'settings' => 'array'
    ];

    /**
     * Get the category this role belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VolunteeringCategory::class, 'category_id');
    }

    /**
     * Get the opportunities for this role
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(VolunteeringOpportunity::class, 'role_id');
    }

    /**
     * Scope to get only active roles
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
     * Check if role requires specific skill
     */
    public function requiresSkill(string $skill): bool
    {
        if (!$this->required_skills) {
            return false;
        }

        return in_array($skill, $this->required_skills);
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
     * Get opportunities count for this role
     */
    public function getOpportunitiesCountAttribute(): int
    {
        return $this->opportunities()->count();
    }

    /**
     * Get active opportunities count for this role
     */
    public function getActiveOpportunitiesCountAttribute(): int
    {
        return $this->opportunities()->where('status', 'active')->count();
    }
}