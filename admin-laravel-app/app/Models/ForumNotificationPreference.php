<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'email_enabled',
        'in_app_enabled',
        'digest_enabled',
        'digest_frequency',
        'additional_settings',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
        'digest_enabled' => 'boolean',
        'additional_settings' => 'array',
    ];

    /**
     * Get the user who owns the preference
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for specific notification type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope for email enabled preferences
     */
    public function scopeEmailEnabled($query)
    {
        return $query->where('email_enabled', true);
    }

    /**
     * Scope for in-app enabled preferences
     */
    public function scopeInAppEnabled($query)
    {
        return $query->where('in_app_enabled', true);
    }

    /**
     * Scope for digest enabled preferences
     */
    public function scopeDigestEnabled($query)
    {
        return $query->where('digest_enabled', true);
    }

    /**
     * Get all available notification types
     */
    public static function getNotificationTypes(): array
    {
        return [
            'reply' => [
                'label' => 'New Replies',
                'description' => 'When someone replies to your threads or posts',
                'default_email' => true,
                'default_in_app' => true,
                'default_digest' => true,
            ],
            'mention' => [
                'label' => 'Mentions',
                'description' => 'When someone mentions you in a post',
                'default_email' => true,
                'default_in_app' => true,
                'default_digest' => false,
            ],
            'vote' => [
                'label' => 'Votes',
                'description' => 'When someone votes on your posts',
                'default_email' => false,
                'default_in_app' => true,
                'default_digest' => true,
            ],
            'solution' => [
                'label' => 'Solutions',
                'description' => 'When your post is marked as a solution',
                'default_email' => true,
                'default_in_app' => true,
                'default_digest' => false,
            ],
            'thread_created' => [
                'label' => 'New Threads',
                'description' => 'When new threads are created in subscribed forums',
                'default_email' => false,
                'default_in_app' => true,
                'default_digest' => true,
            ],
            'moderation' => [
                'label' => 'Moderation Actions',
                'description' => 'When moderation actions affect your content',
                'default_email' => true,
                'default_in_app' => true,
                'default_digest' => false,
            ],
            'warning' => [
                'label' => 'Warnings',
                'description' => 'When you receive warnings from moderators',
                'default_email' => true,
                'default_in_app' => true,
                'default_digest' => false,
            ],
            'suspension' => [
                'label' => 'Suspensions',
                'description' => 'When your account is suspended',
                'default_email' => true,
                'default_in_app' => true,
                'default_digest' => false,
            ],
        ];
    }

    /**
     * Get default preferences for a user
     */
    public static function getDefaultPreferences(): array
    {
        $preferences = [];
        $types = self::getNotificationTypes();
        
        foreach ($types as $type => $config) {
            $preferences[$type] = [
                'email_enabled' => $config['default_email'],
                'in_app_enabled' => $config['default_in_app'],
                'digest_enabled' => $config['default_digest'],
                'digest_frequency' => 'weekly',
            ];
        }
        
        return $preferences;
    }

    /**
     * Create default preferences for a user
     */
    public static function createDefaultsForUser(User $user): void
    {
        $defaults = self::getDefaultPreferences();
        
        foreach ($defaults as $type => $settings) {
            self::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                ],
                $settings
            );
        }
    }

    /**
     * Get user's preference for a specific notification type
     */
    public static function getUserPreference(User $user, string $type): ?self
    {
        return self::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->first();
    }

    /**
     * Check if user has email enabled for notification type
     */
    public static function isEmailEnabledForUser(User $user, string $type): bool
    {
        $preference = self::getUserPreference($user, $type);
        
        if (!$preference) {
            $defaults = self::getNotificationTypes();
            return $defaults[$type]['default_email'] ?? true;
        }
        
        return $preference->email_enabled;
    }

    /**
     * Check if user has in-app enabled for notification type
     */
    public static function isInAppEnabledForUser(User $user, string $type): bool
    {
        $preference = self::getUserPreference($user, $type);
        
        if (!$preference) {
            $defaults = self::getNotificationTypes();
            return $defaults[$type]['default_in_app'] ?? true;
        }
        
        return $preference->in_app_enabled;
    }

    /**
     * Check if user has digest enabled for notification type
     */
    public static function isDigestEnabledForUser(User $user, string $type): bool
    {
        $preference = self::getUserPreference($user, $type);
        
        if (!$preference) {
            $defaults = self::getNotificationTypes();
            return $defaults[$type]['default_digest'] ?? true;
        }
        
        return $preference->digest_enabled;
    }

    /**
     * Get user's digest frequency for notification type
     */
    public static function getDigestFrequencyForUser(User $user, string $type): string
    {
        $preference = self::getUserPreference($user, $type);
        return $preference ? $preference->digest_frequency : 'weekly';
    }

    /**
     * Update user preference for notification type
     */
    public static function updateUserPreference(User $user, string $type, array $settings): void
    {
        self::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $type,
            ],
            $settings
        );
    }

    /**
     * Get all preferences for a user
     */
    public static function getUserPreferences(User $user): array
    {
        $preferences = self::where('user_id', $user->id)->get()->keyBy('notification_type');
        $types = self::getNotificationTypes();
        $result = [];
        
        foreach ($types as $type => $config) {
            $preference = $preferences->get($type);
            
            $result[$type] = [
                'label' => $config['label'],
                'description' => $config['description'],
                'email_enabled' => $preference ? $preference->email_enabled : $config['default_email'],
                'in_app_enabled' => $preference ? $preference->in_app_enabled : $config['default_in_app'],
                'digest_enabled' => $preference ? $preference->digest_enabled : $config['default_digest'],
                'digest_frequency' => $preference ? $preference->digest_frequency : 'weekly',
            ];
        }
        
        return $result;
    }

    /**
     * Reset user preferences to defaults
     */
    public static function resetUserPreferences(User $user): void
    {
        self::where('user_id', $user->id)->delete();
        self::createDefaultsForUser($user);
    }
}