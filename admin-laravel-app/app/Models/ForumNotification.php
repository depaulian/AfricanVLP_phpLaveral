<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
    ];

    /**
     * Get the user who owns the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable model
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for specific notification type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications that haven't been emailed
     */
    public function scopeNotEmailed($query)
    {
        return $query->where('email_sent', false);
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        if ($this->isRead()) {
            $this->update(['read_at' => null]);
        }
    }

    /**
     * Mark email as sent
     */
    public function markEmailAsSent(): void
    {
        $this->update([
            'email_sent' => true,
            'email_sent_at' => now()
        ]);
    }

    /**
     * Get notification title
     */
    public function getTitleAttribute(): string
    {
        return $this->data['title'] ?? $this->getDefaultTitle();
    }

    /**
     * Get notification message
     */
    public function getMessageAttribute(): string
    {
        return $this->data['message'] ?? '';
    }

    /**
     * Get notification URL
     */
    public function getUrlAttribute(): string
    {
        return $this->data['url'] ?? route('admin.forums.index');
    }

    /**
     * Get notification icon
     */
    public function getIconAttribute(): string
    {
        $icons = [
            'reply' => 'fas fa-reply',
            'mention' => 'fas fa-at',
            'vote' => 'fas fa-thumbs-up',
            'solution' => 'fas fa-check-circle',
            'thread_created' => 'fas fa-plus-circle',
            'forum_created' => 'fas fa-comments',
            'moderation' => 'fas fa-shield-alt',
            'warning' => 'fas fa-exclamation-triangle',
            'suspension' => 'fas fa-ban',
        ];

        return $icons[$this->type] ?? 'fas fa-bell';
    }

    /**
     * Get notification color class
     */
    public function getColorClassAttribute(): string
    {
        $colors = [
            'reply' => 'text-blue-600',
            'mention' => 'text-purple-600',
            'vote' => 'text-green-600',
            'solution' => 'text-green-600',
            'thread_created' => 'text-blue-600',
            'forum_created' => 'text-blue-600',
            'moderation' => 'text-orange-600',
            'warning' => 'text-yellow-600',
            'suspension' => 'text-red-600',
        ];

        return $colors[$this->type] ?? 'text-gray-600';
    }

    /**
     * Get default title based on notification type
     */
    private function getDefaultTitle(): string
    {
        $titles = [
            'reply' => 'New Reply',
            'mention' => 'You were mentioned',
            'vote' => 'Your post was voted on',
            'solution' => 'Your post was marked as solution',
            'thread_created' => 'New thread created',
            'forum_created' => 'New forum created',
            'moderation' => 'Moderation action',
            'warning' => 'Warning received',
            'suspension' => 'Account suspended',
        ];

        return $titles[$this->type] ?? 'Notification';
    }

    /**
     * Get time ago string
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Create notification data array
     */
    public static function createData(string $title, string $message, string $url, array $additional = []): array
    {
        return array_merge([
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ], $additional);
    }
}