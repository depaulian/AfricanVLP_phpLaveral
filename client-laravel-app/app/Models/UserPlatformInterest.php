<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserPlatformInterest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'interest_type',
        'interest_level',
        'notification_enabled',
    ];

    protected $casts = [
        'notification_enabled' => 'boolean',
    ];

    /**
     * Interest types enum values.
     */
    const INTEREST_TYPES = [
        'events' => 'Events',
        'news' => 'News & Updates',
        'resources' => 'Resources',
        'forums' => 'Forum Discussions',
        'networking' => 'Networking',
    ];

    /**
     * Interest levels enum values.
     */
    const INTEREST_LEVELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    /**
     * Get the user that owns the platform interest.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the interest type label.
     */
    public function getInterestTypeLabelAttribute(): string
    {
        return self::INTEREST_TYPES[$this->interest_type] ?? ucfirst($this->interest_type);
    }

    /**
     * Get the interest level label.
     */
    public function getInterestLevelLabelAttribute(): string
    {
        return self::INTEREST_LEVELS[$this->interest_level] ?? ucfirst($this->interest_level);
    }

    /**
     * Check if notifications are enabled for this interest.
     */
    public function hasNotificationsEnabled(): bool
    {
        return $this->notification_enabled === true;
    }

    /**
     * Enable notifications for this interest.
     */
    public function enableNotifications(): void
    {
        $this->update(['notification_enabled' => true]);
    }

    /**
     * Disable notifications for this interest.
     */
    public function disableNotifications(): void
    {
        $this->update(['notification_enabled' => false]);
    }

    /**
     * Update the interest level.
     */
    public function updateInterestLevel(string $level): void
    {
        if (in_array($level, array_keys(self::INTEREST_LEVELS))) {
            $this->update(['interest_level' => $level]);
        }
    }

    /**
     * Scope to get interests with notifications enabled.
     */
    public function scopeWithNotifications(Builder $query): Builder
    {
        return $query->where('notification_enabled', true);
    }

    /**
     * Scope to get interests with notifications disabled.
     */
    public function scopeWithoutNotifications(Builder $query): Builder
    {
        return $query->where('notification_enabled', false);
    }

    /**
     * Scope to get high interest level items.
     */
    public function scopeHighInterest(Builder $query): Builder
    {
        return $query->where('interest_level', 'high');
    }

    /**
     * Scope to get medium interest level items.
     */
    public function scopeMediumInterest(Builder $query): Builder
    {
        return $query->where('interest_level', 'medium');
    }

    /**
     * Scope to get low interest level items.
     */
    public function scopeLowInterest(Builder $query): Builder
    {
        return $query->where('interest_level', 'low');
    }

    /**
     * Scope to filter by interest type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('interest_type', $type);
    }

    /**
     * Check if this is a high interest item.
     */
    public function isHighInterest(): bool
    {
        return $this->interest_level === 'high';
    }

    /**
     * Check if this is a medium interest item.
     */
    public function isMediumInterest(): bool
    {
        return $this->interest_level === 'medium';
    }

    /**
     * Check if this is a low interest item.
     */
    public function isLowInterest(): bool
    {
        return $this->interest_level === 'low';
    }

    /**
     * Get the notification priority based on interest level.
     */
    public function getNotificationPriorityAttribute(): int
    {
        return match ($this->interest_level) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Get all available interest types.
     */
    public static function getAvailableInterestTypes(): array
    {
        return self::INTEREST_TYPES;
    }

    /**
     * Get all available interest levels.
     */
    public static function getAvailableInterestLevels(): array
    {
        return self::INTEREST_LEVELS;
    }

    /**
     * Create default platform interests for a user.
     */
    public static function createDefaultsForUser(User $user): void
    {
        $defaultInterests = [
            'events' => 'medium',
            'news' => 'medium',
            'resources' => 'low',
            'forums' => 'low',
            'networking' => 'medium',
        ];

        foreach ($defaultInterests as $type => $level) {
            self::updateOrCreate([
                'user_id' => $user->id,
                'interest_type' => $type,
            ], [
                'interest_level' => $level,
                'notification_enabled' => true,
            ]);
        }
    }
}