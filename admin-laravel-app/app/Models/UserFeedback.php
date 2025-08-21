<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class UserFeedback extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'user_id',
        'admin_id',
        'type',
        'category',
        'title',
        'message',
        'rating',
        'status',
        'priority',
        'page_url',
        'user_agent',
        'ip_address',
        'response',
        'responded_at',
        'is_public',
        'is_featured',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'responded_at' => 'datetime',
        'rating' => 'integer',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who submitted the feedback
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who responded to the feedback
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the responses for this feedback
     */
    public function responses(): HasMany
    {
        return $this->hasMany(UserFeedbackResponse::class);
    }

    /**
     * Get the attachments for this feedback
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(UserFeedbackAttachment::class);
    }

    /**
     * Check if feedback is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if feedback is in review
     */
    public function isInReview(): bool
    {
        return $this->status === 'in_review';
    }

    /**
     * Check if feedback is responded
     */
    public function isResponded(): bool
    {
        return $this->status === 'responded';
    }

    /**
     * Check if feedback is closed
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if feedback is implemented (for feature requests)
     */
    public function isImplemented(): bool
    {
        return $this->status === 'implemented';
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'in_review' => 'blue',
            'responded' => 'green',
            'implemented' => 'purple',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get type icon for UI
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'bug_report' => 'bug',
            'feature_request' => 'lightbulb',
            'improvement' => 'arrow-up',
            'complaint' => 'exclamation-triangle',
            'compliment' => 'heart',
            'question' => 'question-circle',
            'general' => 'comment',
            default => 'comment',
        };
    }

    /**
     * Get rating stars for display
     */
    public function getRatingStarsAttribute(): string
    {
        if (!$this->rating) {
            return 'No rating';
        }
        
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get response count
     */
    public function getResponseCount(): int
    {
        return $this->responses()->count();
    }

    /**
     * Get time since submission
     */
    public function getTimeSinceSubmission(): string
    {
        return $this->created->diffForHumans();
    }

    /**
     * Scope for pending feedback
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in review feedback
     */
    public function scopeInReview($query)
    {
        return $query->where('status', 'in_review');
    }

    /**
     * Scope for responded feedback
     */
    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    /**
     * Scope for closed feedback
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
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
     * Scope for feedback by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for feedback by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for feedback by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for feedback by rating
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Common feedback types
     */
    const TYPE_BUG_REPORT = 'bug_report';
    const TYPE_FEATURE_REQUEST = 'feature_request';
    const TYPE_IMPROVEMENT = 'improvement';
    const TYPE_COMPLAINT = 'complaint';
    const TYPE_COMPLIMENT = 'compliment';
    const TYPE_QUESTION = 'question';
    const TYPE_GENERAL = 'general';

    /**
     * Common feedback statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_RESPONDED = 'responded';
    const STATUS_IMPLEMENTED = 'implemented';
    const STATUS_CLOSED = 'closed';

    /**
     * Common feedback priorities
     */
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    /**
     * Common feedback categories
     */
    const CATEGORY_UI_UX = 'ui_ux';
    const CATEGORY_PERFORMANCE = 'performance';
    const CATEGORY_FUNCTIONALITY = 'functionality';
    const CATEGORY_CONTENT = 'content';
    const CATEGORY_ACCESSIBILITY = 'accessibility';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_OTHER = 'other';
}
