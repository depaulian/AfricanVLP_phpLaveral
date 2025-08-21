<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VolunteerFeedback extends Model
{
    use HasFactory;

    protected $table = 'volunteer_feedback';

    protected $fillable = [
        'assignment_id',
        'reviewer_id',
        'reviewee_id',
        'feedback_type',
        'reviewer_type',
        'template_id',
        'overall_rating',
        'communication_rating',
        'reliability_rating',
        'skill_rating',
        'attitude_rating',
        'impact_rating',
        'positive_feedback',
        'improvement_feedback',
        'additional_comments',
        'structured_ratings',
        'tags',
        'is_anonymous',
        'is_public',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'response',
        'response_at',
        'follow_up_requested',
        'follow_up_scheduled_at',
    ];

    protected $casts = [
        'overall_rating' => 'decimal:1',
        'communication_rating' => 'decimal:1',
        'reliability_rating' => 'decimal:1',
        'skill_rating' => 'decimal:1',
        'attitude_rating' => 'decimal:1',
        'impact_rating' => 'decimal:1',
        'structured_ratings' => 'array',
        'tags' => 'array',
        'is_anonymous' => 'boolean',
        'is_public' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'response_at' => 'datetime',
        'follow_up_requested' => 'boolean',
        'follow_up_scheduled_at' => 'datetime',
    ];

    /**
     * Get the assignment this feedback belongs to
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VolunteerAssignment::class);
    }

    /**
     * Get the user who gave the feedback
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the user who received the feedback
     */
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    /**
     * Get the user who reviewed this feedback
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the template used for this feedback
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FeedbackTemplate::class, 'template_id');
    }

    /**
     * Scope for specific feedback type
     */
    public function scopeFeedbackType($query, string $type)
    {
        return $query->where('feedback_type', $type);
    }

    /**
     * Scope for specific reviewer type
     */
    public function scopeReviewerType($query, string $type)
    {
        return $query->where('reviewer_type', $type);
    }

    /**
     * Scope for specific status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for submitted feedback
     */
    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', ['submitted', 'reviewed', 'published']);
    }

    /**
     * Scope for public feedback
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', 'published');
    }

    /**
     * Submit the feedback
     */
    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Add response to feedback
     */
    public function addResponse(string $response): void
    {
        $this->update([
            'response' => $response,
            'response_at' => now(),
        ]);
    }

    /**
     * Request follow-up
     */
    public function requestFollowUp(Carbon $scheduledAt = null): void
    {
        $this->update([
            'follow_up_requested' => true,
            'follow_up_scheduled_at' => $scheduledAt ?? now()->addWeek(),
        ]);
    }

    /**
     * Get feedback type display name
     */
    public function getFeedbackTypeDisplayAttribute(): string
    {
        return match ($this->feedback_type) {
            'volunteer_to_organization' => 'Volunteer to Organization',
            'organization_to_volunteer' => 'Organization to Volunteer',
            'supervisor_to_volunteer' => 'Supervisor to Volunteer',
            'volunteer_to_supervisor' => 'Volunteer to Supervisor',
            'beneficiary_to_volunteer' => 'Beneficiary to Volunteer',
            default => ucwords(str_replace('_', ' ', $this->feedback_type)),
        };
    }

    /**
     * Get reviewer type display name
     */
    public function getReviewerTypeDisplayAttribute(): string
    {
        return match ($this->reviewer_type) {
            'volunteer' => 'Volunteer',
            'supervisor' => 'Supervisor',
            'organization_admin' => 'Organization Admin',
            'beneficiary' => 'Beneficiary',
            default => ucwords(str_replace('_', ' ', $this->reviewer_type)),
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'reviewed' => 'Reviewed',
            'published' => 'Published',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'submitted' => 'yellow',
            'reviewed' => 'blue',
            'published' => 'green',
            default => 'gray',
        };
    }

    /**
     * Check if feedback can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'submitted']);
    }

    /**
     * Check if feedback has any ratings
     */
    public function hasRatings(): bool
    {
        return !is_null($this->overall_rating) ||
               !is_null($this->communication_rating) ||
               !is_null($this->reliability_rating) ||
               !is_null($this->skill_rating) ||
               !is_null($this->attitude_rating) ||
               !is_null($this->impact_rating);
    }

    /**
     * Check if feedback has written content
     */
    public function hasWrittenFeedback(): bool
    {
        return !empty($this->positive_feedback) ||
               !empty($this->improvement_feedback) ||
               !empty($this->additional_comments);
    }

    /**
     * Get reviewer display name (considering anonymity)
     */
    public function getReviewerDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous ' . $this->reviewer_type_display;
        }
        
        return $this->reviewer->name ?? 'Unknown';
    }

    /**
     * Get rating categories with labels
     */
    public function getRatingCategoriesAttribute(): array
    {
        return [
            'overall' => [
                'label' => 'Overall Experience',
                'value' => $this->overall_rating,
                'description' => 'Overall satisfaction with the experience',
            ],
            'communication' => [
                'label' => 'Communication',
                'value' => $this->communication_rating,
                'description' => 'Quality of communication and responsiveness',
            ],
            'reliability' => [
                'label' => 'Reliability',
                'value' => $this->reliability_rating,
                'description' => 'Punctuality and dependability',
            ],
            'skill' => [
                'label' => 'Skills & Competence',
                'value' => $this->skill_rating,
                'description' => 'Technical skills and competence level',
            ],
            'attitude' => [
                'label' => 'Attitude & Behavior',
                'value' => $this->attitude_rating,
                'description' => 'Professional attitude and behavior',
            ],
            'impact' => [
                'label' => 'Impact & Contribution',
                'value' => $this->impact_rating,
                'description' => 'Positive impact and contribution made',
            ],
        ];
    }

    /**
     * Get feedback summary for display
     */
    public function getSummaryAttribute(): string
    {
        $parts = [];
        
        if ($this->overall_rating) {
            $parts[] = $this->overall_rating . '/5 stars';
        }
        
        if ($this->positive_feedback) {
            $parts[] = 'Positive: ' . Str::limit($this->positive_feedback, 50);
        }
        
        if ($this->improvement_feedback) {
            $parts[] = 'Improvement: ' . Str::limit($this->improvement_feedback, 50);
        }
        
        return implode(' | ', $parts) ?: 'No feedback provided';
    }

    /**
     * Get available tags for feedback type
     */
    public static function getAvailableTags(string $feedbackType): array
    {
        $commonTags = [
            'professional', 'punctual', 'reliable', 'communicative', 'helpful',
            'skilled', 'creative', 'organized', 'flexible', 'enthusiastic',
        ];
        
        $specificTags = match ($feedbackType) {
            'volunteer_to_organization' => [
                'well_organized', 'clear_instructions', 'supportive', 'responsive',
                'good_training', 'meaningful_work', 'welcoming_environment',
            ],
            'organization_to_volunteer' => [
                'dedicated', 'proactive', 'team_player', 'quick_learner',
                'positive_attitude', 'goes_above_beyond', 'culturally_sensitive',
            ],
            'supervisor_to_volunteer' => [
                'follows_instructions', 'takes_initiative', 'asks_good_questions',
                'adapts_well', 'shows_leadership', 'mentors_others',
            ],
            'beneficiary_to_volunteer' => [
                'caring', 'patient', 'understanding', 'respectful',
                'made_difference', 'inspiring', 'compassionate',
            ],
            default => [],
        };
        
        return array_merge($commonTags, $specificTags);
    }

    /**
     * Get feedback statistics for a user
     */
    public static function getUserStats(User $user): array
    {
        $asReviewee = static::where('reviewee_id', $user->id)->submitted();
        $asReviewer = static::where('reviewer_id', $user->id)->submitted();
        
        return [
            'received_count' => $asReviewee->count(),
            'given_count' => $asReviewer->count(),
            'average_rating_received' => $asReviewee->whereNotNull('overall_rating')->avg('overall_rating'),
            'total_ratings_received' => $asReviewee->whereNotNull('overall_rating')->count(),
            'positive_feedback_count' => $asReviewee->whereNotNull('positive_feedback')->count(),
            'improvement_feedback_count' => $asReviewee->whereNotNull('improvement_feedback')->count(),
            'public_feedback_count' => $asReviewee->where('is_public', true)->count(),
        ];
    }

    /**
     * Get feedback statistics for an organization
     */
    public static function getOrganizationStats(Organization $organization): array
    {
        $feedback = static::whereHas('assignment', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })->submitted();
        
        return [
            'total_feedback' => $feedback->count(),
            'average_rating' => $feedback->whereNotNull('overall_rating')->avg('overall_rating'),
            'volunteer_to_org_feedback' => $feedback->where('feedback_type', 'volunteer_to_organization')->count(),
            'org_to_volunteer_feedback' => $feedback->where('feedback_type', 'organization_to_volunteer')->count(),
            'supervisor_feedback' => $feedback->where('feedback_type', 'supervisor_to_volunteer')->count(),
            'beneficiary_feedback' => $feedback->where('feedback_type', 'beneficiary_to_volunteer')->count(),
            'public_feedback_count' => $feedback->where('is_public', true)->count(),
            'response_rate' => $feedback->whereNotNull('response')->count() / max($feedback->count(), 1) * 100,
        ];
    }
}