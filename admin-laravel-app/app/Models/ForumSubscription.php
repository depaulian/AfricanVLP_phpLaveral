<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscribable_type',
        'subscribable_id',
        'type',
        'is_active',
        'notification_preferences',
        'last_notified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notification_preferences' => 'array',
        'last_notified_at' => 'datetime',
    ];

    /**
     * Get the user who owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscribable model (forum, thread, or post)
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific subscription type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if email notifications are enabled
     */
    public function isEmailEnabled(): bool
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences['email_enabled'] ?? true;
    }

    /**
     * Check if in-app notifications are enabled
     */
    public function isInAppEnabled(): bool
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences['in_app_enabled'] ?? true;
    }

    /**
     * Check if digest notifications are enabled
     */
    public function isDigestEnabled(): bool
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences['digest_enabled'] ?? true;
    }

    /**
     * Get digest frequency
     */
    public function getDigestFrequency(): string
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences['digest_frequency'] ?? 'weekly';
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(array $preferences): void
    {
        $current = $this->notification_preferences ?? [];
        $this->update([
            'notification_preferences' => array_merge($current, $preferences)
        ]);
    }

    /**
     * Mark as notified
     */
    public function markAsNotified(): void
    {
        $this->update(['last_notified_at' => now()]);
    }

    /**
     * Activate subscription
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate subscription
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get subscription display name
     */
    public function getDisplayNameAttribute(): string
    {
        switch ($this->type) {
            case 'forum':
                return $this->subscribable->name ?? 'Forum';
            case 'thread':
                return $this->subscribable->title ?? 'Thread';
            case 'post':
                return 'Post in ' . ($this->subscribable->thread->title ?? 'Thread');
            default:
                return 'Subscription';
        }
    }

    /**
     * Get subscription URL
     */
    public function getUrlAttribute(): string
    {
        switch ($this->type) {
            case 'forum':
                return route('admin.forums.show', $this->subscribable);
            case 'thread':
                return route('admin.forums.threads.show', [$this->subscribable->forum, $this->subscribable]);
            case 'post':
                return route('admin.forums.threads.show', [$this->subscribable->thread->forum, $this->subscribable->thread]) . '#post-' . $this->subscribable->id;
            default:
                return route('admin.forums.index');
        }
    }
}