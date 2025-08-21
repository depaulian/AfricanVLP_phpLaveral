<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'moderator_id',
        'forum_id',
        'reason',
        'description',
        'severity',
        'expires_at',
        'is_active',
        'acknowledged_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the warned user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the moderator who issued the warning
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Get the forum (if forum-specific warning)
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    /**
     * Scope for active warnings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired warnings
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if warning is active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->expires_at->isFuture();
    }

    /**
     * Check if warning is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Acknowledge the warning
     */
    public function acknowledge(): void
    {
        $this->update(['acknowledged_at' => now()]);
    }

    /**
     * Deactivate the warning
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get time remaining until expiration
     */
    public function getTimeRemainingAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }
}