<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillEndorsement extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_id',
        'endorser_id',
        'status',
        'request_message',
        'endorsement_comment',
        'rejection_reason',
        'requested_at',
        'endorsed_at',
        'responded_at'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'endorsed_at' => 'datetime',
        'responded_at' => 'datetime'
    ];

    /**
     * Get the skill being endorsed
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(UserSkill::class, 'skill_id');
    }

    /**
     * Get the user providing the endorsement
     */
    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorser_id');
    }

    /**
     * Scope to get approved endorsements
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get pending endorsements
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get rejected endorsements
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get recent endorsements
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('endorsed_at', '>=', now()->subDays($days));
    }

    /**
     * Check if endorsement is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if endorsement is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if endorsement is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get endorsement strength score
     */
    public function getStrengthScoreAttribute(): float
    {
        if (!$this->isApproved()) {
            return 0;
        }

        $score = 20; // Base score

        // Endorser skill level bonus
        $endorserSkill = $this->endorser->skills()
            ->where('skill_name', $this->skill->skill_name)
            ->first();

        if ($endorserSkill) {
            $score += match ($endorserSkill->proficiency_level) {
                'expert' => 30,
                'advanced' => 25,
                'intermediate' => 20,
                'beginner' => 15,
                default => 15
            };

            if ($endorserSkill->verified) {
                $score += 20;
            }
        }

        // Comment quality bonus
        if (!empty($this->endorsement_comment) && strlen($this->endorsement_comment) > 50) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Get days since endorsement
     */
    public function getDaysSinceEndorsementAttribute(): ?int
    {
        return $this->endorsed_at ? $this->endorsed_at->diffInDays(now()) : null;
    }

    /**
     * Get endorsement age category
     */
    public function getAgeCategoryAttribute(): string
    {
        if (!$this->endorsed_at) {
            return 'pending';
        }

        $days = $this->days_since_endorsement;

        if ($days <= 7) return 'recent';
        if ($days <= 30) return 'current';
        if ($days <= 90) return 'older';
        return 'old';
    }
}