<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class AuMessage extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'subject',
        'body',
        'type',
        'priority',
        'status',
        'is_read',
        'read_at',
        'replied_at',
        'parent_id',
        'thread_id',
        'organization_id',
        'tags',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'deleted_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'expires_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the sender of the message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the recipient of the message
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the parent message (for replies)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AuMessage::class, 'parent_id');
    }

    /**
     * Get the thread root message
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(AuMessage::class, 'thread_id');
    }

    /**
     * Get the organization this message belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get replies to this message
     */
    public function replies(): HasMany
    {
        return $this->hasMany(AuMessage::class, 'parent_id')->orderBy('created');
    }

    /**
     * Get all messages in this thread
     */
    public function threadMessages(): HasMany
    {
        return $this->hasMany(AuMessage::class, 'thread_id')->orderBy('created');
    }

    /**
     * Get attachments for this message
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(AuMessageAttachment::class);
    }

    /**
     * Check if message is read
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Check if message is unread
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Check if message has been replied to
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    /**
     * Check if message is a reply
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if message is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark message as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'blue',
            'low' => 'gray',
            default => 'blue',
        };
    }

    /**
     * Get priority icon for UI
     */
    public function getPriorityIconAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'alert-triangle',
            'high' => 'arrow-up',
            'normal' => 'minus',
            'low' => 'arrow-down',
            default => 'minus',
        };
    }

    /**
     * Get type color for UI
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'system' => 'purple',
            'notification' => 'blue',
            'announcement' => 'green',
            'warning' => 'yellow',
            'alert' => 'red',
            'personal' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get type icon for UI
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'system' => 'settings',
            'notification' => 'bell',
            'announcement' => 'megaphone',
            'warning' => 'alert-triangle',
            'alert' => 'alert-circle',
            'personal' => 'mail',
            default => 'mail',
        };
    }

    /**
     * Get formatted body with basic HTML support
     */
    public function getFormattedBodyAttribute(): string
    {
        return nl2br(e($this->body));
    }

    /**
     * Get message preview (first 100 characters)
     */
    public function getPreviewAttribute(): string
    {
        return Str::limit(strip_tags($this->body), 100);
    }

    /**
     * Get time since message was sent
     */
    public function getTimeSinceAttribute(): string
    {
        return $this->created->diffForHumans();
    }

    /**
     * Get reply count
     */
    public function getReplyCountAttribute(): int
    {
        return $this->replies()->count();
    }

    /**
     * Get thread message count
     */
    public function getThreadCountAttribute(): int
    {
        if ($this->thread_id) {
            return $this->threadMessages()->count();
        }
        return $this->replies()->count() + 1; // Include this message
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read messages
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for messages by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for messages by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for messages by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for sent messages
     */
    public function scopeSent($query, int $userId)
    {
        return $query->where('sender_id', $userId);
    }

    /**
     * Scope for received messages
     */
    public function scopeReceived($query, int $userId)
    {
        return $query->where('recipient_id', $userId);
    }

    /**
     * Scope for messages in organization
     */
    public function scopeInOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope for thread root messages (not replies)
     */
    public function scopeThreadRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for replies
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope for non-expired messages
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for expired messages
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope for messages with attachments
     */
    public function scopeWithAttachments($query)
    {
        return $query->whereHas('attachments');
    }

    /**
     * Scope for messages by tag
     */
    public function scopeByTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Common message types
     */
    const TYPE_SYSTEM = 'system';
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_WARNING = 'warning';
    const TYPE_ALERT = 'alert';
    const TYPE_PERSONAL = 'personal';

    /**
     * Common message priorities
     */
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    /**
     * Common message statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_REPLIED = 'replied';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Get all available message types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_SYSTEM,
            self::TYPE_NOTIFICATION,
            self::TYPE_ANNOUNCEMENT,
            self::TYPE_WARNING,
            self::TYPE_ALERT,
            self::TYPE_PERSONAL,
        ];
    }

    /**
     * Get all available priorities
     */
    public static function getAvailablePriorities(): array
    {
        return [
            self::PRIORITY_URGENT,
            self::PRIORITY_HIGH,
            self::PRIORITY_NORMAL,
            self::PRIORITY_LOW,
        ];
    }

    /**
     * Get all available statuses
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_READ,
            self::STATUS_REPLIED,
            self::STATUS_ARCHIVED,
        ];
    }
}
