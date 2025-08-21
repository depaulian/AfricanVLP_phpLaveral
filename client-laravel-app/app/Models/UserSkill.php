<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skill_name',
        'proficiency_level',
        'years_experience',
        'verified',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'verified' => 'boolean',
        'verified_at' => 'datetime'
    ];

    /**
     * Get the user this skill belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who verified this skill
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get all endorsements for this skill
     */
    public function endorsements()
    {
        return $this->hasMany(SkillEndorsement::class, 'skill_id');
    }

    /**
     * Get verification requests for this skill
     */
    public function verificationRequests()
    {
        return $this->hasMany(SkillVerificationRequest::class, 'skill_id');
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by skill name
     */
    public function scopeBySkill($query, $skillName)
    {
        return $query->where('skill_name', $skillName);
    }

    /**
     * Scope to filter by proficiency level
     */
    public function scopeByProficiencyLevel($query, $level)
    {
        return $query->where('proficiency_level', $level);
    }

    /**
     * Scope to get verified skills
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope to get unverified skills
     */
    public function scopeUnverified($query)
    {
        return $query->where('verified', false);
    }

    /**
     * Scope to get advanced skills
     */
    public function scopeAdvanced($query)
    {
        return $query->whereIn('proficiency_level', ['advanced', 'expert']);
    }

    /**
     * Check if skill is verified
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Verify the skill
     */
    public function verify(User $verifier = null): void
    {
        $this->update([
            'verified' => true,
            'verified_by' => $verifier?->id,
            'verified_at' => now()
        ]);
    }

    /**
     * Unverify the skill
     */
    public function unverify(): void
    {
        $this->update([
            'verified' => false,
            'verified_by' => null,
            'verified_at' => null
        ]);
    }

    /**
     * Get proficiency level badge color
     */
    public function getProficiencyLevelColorAttribute(): string
    {
        return match ($this->proficiency_level) {
            'expert' => 'success',
            'advanced' => 'primary',
            'intermediate' => 'warning',
            'beginner' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get formatted proficiency level
     */
    public function getFormattedProficiencyLevelAttribute(): string
    {
        return ucfirst($this->proficiency_level);
    }

    /**
     * Get proficiency level weight for matching algorithms
     */
    public function getProficiencyWeightAttribute(): int
    {
        return match ($this->proficiency_level) {
            'expert' => 4,
            'advanced' => 3,
            'intermediate' => 2,
            'beginner' => 1,
            default => 1
        };
    }

    /**
     * Get experience level description
     */
    public function getExperienceLevelDescriptionAttribute(): string
    {
        if (!$this->years_experience) {
            return $this->formatted_proficiency_level;
        }

        $years = $this->years_experience;
        $yearText = $years === 1 ? 'year' : 'years';
        
        return "{$this->formatted_proficiency_level} ({$years} {$yearText})";
    }

    /**
     * Check if skill matches requirement
     */
    public function matchesRequirement(string $requiredSkill, string $minLevel = 'beginner'): bool
    {
        if (strtolower($this->skill_name) !== strtolower($requiredSkill)) {
            return false;
        }

        $levelOrder = ['beginner', 'intermediate', 'advanced', 'expert'];
        $userLevelIndex = array_search($this->proficiency_level, $levelOrder);
        $requiredLevelIndex = array_search($minLevel, $levelOrder);

        return $userLevelIndex >= $requiredLevelIndex;
    }

    /**
     * Get the proficiency level label.
     */
    public function getProficiencyLabelAttribute(): string
    {
        return ucfirst($this->proficiency_level);
    }

    /**
     * Get the experience description.
     */
    public function getExperienceDescriptionAttribute(): string
    {
        if (!$this->years_experience) {
            return 'No experience specified';
        }
        
        return $this->years_experience . ' year' . ($this->years_experience > 1 ? 's' : '') . ' experience';
    }
}