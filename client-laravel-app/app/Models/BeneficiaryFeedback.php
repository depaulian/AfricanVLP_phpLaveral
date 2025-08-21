<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id',
        'assignment_id',
        'organization_id',
        'beneficiary_name',
        'beneficiary_email',
        'beneficiary_phone',
        'beneficiary_type',
        'feedback_text',
        'satisfaction_rating',
        'impact_rating',
        'volunteer_rating',
        'specific_ratings',
        'improvement_suggestions',
        'impact_areas',
        'would_recommend',
        'is_anonymous',
        'is_public',
        'is_featured',
        'status',
        'approved_at',
        'approved_by',
        'attachments',
    ];

    protected $casts = [
        'specific_ratings' => 'array',
        'impact_areas' => 'array',
        'would_recommend' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'approved_at' => 'datetime',
        'attachments' => 'array',
        'satisfaction_rating' => 'integer',
        'impact_rating' => 'integer',
        'volunteer_rating' => 'integer',
    ];

    /**
     * Get the volunteer this feedback is about
     */
    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'volunteer_id');
    }

    /**
     * Get the assignment this feedback relates to
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VolunteerAssignment::class);
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who approved this feedback
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for approved feedback
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for public feedback
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for featured feedback
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for pending approval
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get beneficiary type display name
     */
    public function getBeneficiaryTypeDisplayAttribute(): string
    {
        return match ($this->beneficiary_type) {
            'individual' => 'Individual',
            'family' => 'Family',
            'group' => 'Group',
            'community' => 'Community',
            'organization' => 'Organization',
            default => ucfirst($this->beneficiary_type),
        };
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute(): ?float
    {
        $ratings = array_filter([
            $this->satisfaction_rating,
            $this->impact_rating,
            $this->volunteer_rating,
        ]);

        return empty($ratings) ? null : round(array_sum($ratings) / count($ratings), 1);
    }

    /**
     * Check if feedback can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if feedback can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Approve the feedback
     */
    public function approve(User $approver): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject the feedback
     */
    public function reject(User $approver): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Get display name for beneficiary
     */
    public function getBeneficiaryDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous ' . $this->beneficiary_type_display;
        }

        return $this->beneficiary_name ?: 'Anonymous';
    }

    /**
     * Check if feedback has attachments
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Get formatted feedback text (truncated for lists)
     */
    public function getShortFeedbackAttribute(): string
    {
        return strlen($this->feedback_text) > 200 
            ? substr($this->feedback_text, 0, 200) . '...'
            : $this->feedback_text;
    }

    /**
     * Get star rating HTML
     */
    public function getStarRatingHtml(string $type = 'average'): string
    {
        $rating = match ($type) {
            'satisfaction' => $this->satisfaction_rating,
            'impact' => $this->impact_rating,
            'volunteer' => $this->volunteer_rating,
            'average' => $this->average_rating,
            default => $this->average_rating,
        };

        if (!$rating) {
            return '<span class="text-gray-400">No rating</span>';
        }

        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $html .= '<i class="fas fa-star text-yellow-400"></i>';
            } elseif ($i - 0.5 <= $rating) {
                $html .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
            } else {
                $html .= '<i class="far fa-star text-gray-300"></i>';
            }
        }

        return $html . ' <span class="ml-1 text-sm text-gray-600">(' . $rating . '/5)</span>';
    }

    /**
     * Check if feedback is recent
     */
    public function isRecent(int $days = 7): bool
    {
        return $this->created_at->isAfter(now()->subDays($days));
    }

    /**
     * Get time since feedback was submitted
     */
    public function getTimeSinceSubmissionAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}