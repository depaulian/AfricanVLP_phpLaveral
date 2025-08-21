<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'action_url',
        'action_text',
        'priority',
        'category',
        'expires_at'
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'data' => 'array'
    ];

    /**
     * Get the user that owns the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
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
     * Scope for active notifications (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific category
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Get notification icon based on type
     */
    public function getIcon(): string
    {
        $icons = [
            'info' => 'fas fa-info-circle',
            'success' => 'fas fa-check-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'error' => 'fas fa-times-circle',
            'message' => 'fas fa-envelope',
            'event' => 'fas fa-calendar',
            'news' => 'fas fa-newspaper',
            'resource' => 'fas fa-file',
            'volunteer' => 'fas fa-hands-helping',
            'organization' => 'fas fa-building',
            'user' => 'fas fa-user',
            'system' => 'fas fa-cog'
        ];

        return $icons[$this->type] ?? 'fas fa-bell';
    }

    /**
     * Get notification color based on priority
     */
    public function getColor(): string
    {
        $colors = [
            'low' => 'text-gray-500',
            'normal' => 'text-blue-500',
            'high' => 'text-orange-500',
            'urgent' => 'text-red-500'
        ];

        return $colors[$this->priority] ?? 'text-blue-500';
    }

    /**
     * Get time ago string
     */
    public function getTimeAgo(): string
    {
        return $this->created->diffForHumans();
    }
}