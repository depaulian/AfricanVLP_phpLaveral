<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes, Auditable;



    protected $fillable = [
        'user_id',
        'assigned_to',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'resolution',
        'resolved_at',
        'closed_at',
        'tags',
        'metadata',
        'satisfaction_rating',
        'satisfaction_feedback',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
        'satisfaction_rating' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created the ticket
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin user assigned to this ticket
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the responses for this ticket
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SupportTicketResponse::class);
    }

    /**
     * Get the attachments for this ticket
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    /**
     * Check if ticket is open
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress', 'pending']);
    }

    /**
     * Check if ticket is closed
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if ticket is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if ticket is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->isClosed() || $this->isResolved()) {
            return false;
        }

        $dueDays = $this->getDueDays();
        return $this->created_at->addDays($dueDays)->isPast();
    }

    /**
     * Get due days based on priority
     */
    public function getDueDays(): int
    {
        return match($this->priority) {
            'critical' => 1,
            'high' => 2,
            'medium' => 5,
            'low' => 10,
            default => 7,
        };
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
            'open' => 'blue',
            'in_progress' => 'yellow',
            'pending' => 'orange',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get the latest response
     */
    public function getLatestResponse(): ?SupportTicketResponse
    {
        return $this->responses()->latest('created')->first();
    }

    /**
     * Get response count
     */
    public function getResponseCount(): int
    {
        return $this->responses()->count();
    }

    /**
     * Get time since last update
     */
    public function getTimeSinceLastUpdate(): string
    {
        $latestResponse = $this->getLatestResponse();
        $lastUpdate = $latestResponse ? $latestResponse->created_at : $this->created_at;
        
        return $lastUpdate->diffForHumans();
    }

    /**
     * Scope for open tickets
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'pending']);
    }

    /**
     * Scope for closed tickets
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope for resolved tickets
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for overdue tickets
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'pending'])
                    ->where(function($q) {
                        $q->where(function($subQ) {
                            $subQ->where('priority', 'critical')
                                 ->where('created', '<', now()->subDay());
                        })->orWhere(function($subQ) {
                            $subQ->where('priority', 'high')
                                 ->where('created', '<', now()->subDays(2));
                        })->orWhere(function($subQ) {
                            $subQ->where('priority', 'medium')
                                 ->where('created', '<', now()->subDays(5));
                        })->orWhere(function($subQ) {
                            $subQ->where('priority', 'low')
                                 ->where('created', '<', now()->subDays(10));
                        });
                    });
    }

    /**
     * Scope for tickets by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for tickets by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for tickets assigned to user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for unassigned tickets
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Common ticket priorities
     */
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    /**
     * Common ticket statuses
     */
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    /**
     * Common ticket categories
     */
    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_ACCOUNT = 'account';
    const CATEGORY_BILLING = 'billing';
    const CATEGORY_FEATURE_REQUEST = 'feature_request';
    const CATEGORY_BUG_REPORT = 'bug_report';
    const CATEGORY_GENERAL = 'general';
}
