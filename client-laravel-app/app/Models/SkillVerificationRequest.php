<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillVerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_id',
        'evidence',
        'status',
        'reviewer_id',
        'reviewer_notes',
        'submitted_at',
        'reviewed_at'
    ];

    protected $casts = [
        'evidence' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime'
    ];

    /**
     * Get the skill being verified
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(UserSkill::class, 'skill_id');
    }

    /**
     * Get the reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get under review requests
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope to get approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is under review
     */
    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected
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
            'under_review' => 'info',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return match ($this->status) {
            'under_review' => 'Under Review',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get days since submission
     */
    public function getDaysSinceSubmissionAttribute(): int
    {
        return $this->submitted_at->diffInDays(now());
    }

    /**
     * Get processing time in days
     */
    public function getProcessingTimeAttribute(): ?int
    {
        return $this->reviewed_at ? $this->submitted_at->diffInDays($this->reviewed_at) : null;
    }

    /**
     * Check if request is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->isApproved() || $this->isRejected()) {
            return false;
        }

        return $this->days_since_submission > 14; // 14 days processing time
    }

    /**
     * Get evidence summary
     */
    public function getEvidenceSummaryAttribute(): string
    {
        if (empty($this->evidence)) {
            return 'No evidence provided';
        }

        $types = [];
        foreach ($this->evidence as $item) {
            if (isset($item['type'])) {
                $types[] = $item['type'];
            }
        }

        return implode(', ', array_unique($types));
    }

    /**
     * Get evidence count
     */
    public function getEvidenceCountAttribute(): int
    {
        return count($this->evidence ?? []);
    }
}