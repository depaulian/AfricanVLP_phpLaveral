<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'channels',
        'is_enabled',
        'settings',
    ];

    protected $casts = [
        'channels' => 'array',
        'is_enabled' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the user that owns the preference
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for enabled preferences
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope for specific notification type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Check if a specific channel is enabled
     */
    public function isChannelEnabled(string $channel): bool
    {
        return $this->is_enabled && in_array($channel, $this->channels ?? []);
    }

    /**
     * Get enabled channels for this preference
     */
    public function getEnabledChannels(): array
    {
        return $this->is_enabled ? ($this->channels ?? []) : [];
    }

    /**
     * Get notification type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->notification_type) {
            'opportunity_match' => 'Opportunity Matches',
            'application_status' => 'Application Status Updates',
            'hour_approval' => 'Hour Approvals',
            'deadline_reminder' => 'Deadline Reminders',
            'supervisor_notification' => 'Supervisor Notifications',
            'digest' => 'Activity Digest',
            'assignment_created' => 'New Assignments',
            'assignment_completed' => 'Assignment Completions',
            'certificate_issued' => 'Certificate Notifications',
            'achievement_earned' => 'Achievement Notifications',
            'feedback_request' => 'Feedback Requests',
            default => ucwords(str_replace('_', ' ', $this->notification_type)),
        };
    }

    /**
     * Get default preferences for a user
     */
    public static function getDefaultPreferences(): array
    {
        return [
            'opportunity_match' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                    'match_threshold' => 70,
                ],
            ],
            'application_status' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
            'hour_approval' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
            'deadline_reminder' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'advance_days' => [7, 3, 1],
                    'frequency' => 'daily',
                ],
            ],
            'supervisor_notification' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
            'digest' => [
                'channels' => ['email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'weekly',
                    'day_of_week' => 'monday',
                    'time' => '09:00',
                ],
            ],
            'assignment_created' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
            'assignment_completed' => [
                'channels' => ['database'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
            'certificate_issued' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
            'achievement_earned' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
            'feedback_request' => [
                'channels' => ['database', 'email'],
                'is_enabled' => true,
                'settings' => [
                    'frequency' => 'immediate',
                ],
            ],
        ];
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
    public static function getUserPreference(User $user, string $notificationType): ?self
    {
        return self::where('user_id', $user->id)
                  ->where('notification_type', $notificationType)
                  ->first();
    }

    /**
     * Check if user should receive notification on a specific channel
     */
    public static function shouldReceiveNotification(User $user, string $notificationType, string $channel): bool
    {
        $preference = self::getUserPreference($user, $notificationType);

        if (!$preference) {
            // If no preference exists, check defaults
            $defaults = self::getDefaultPreferences();
            $defaultSettings = $defaults[$notificationType] ?? null;
            
            if (!$defaultSettings) {
                return false;
            }

            return $defaultSettings['is_enabled'] && in_array($channel, $defaultSettings['channels']);
        }

        return $preference->isChannelEnabled($channel);
    }

    /**
     * Get all enabled channels for a user and notification type
     */
    public static function getEnabledChannelsForUser(User $user, string $notificationType): array
    {
        $preference = self::getUserPreference($user, $notificationType);

        if (!$preference) {
            // If no preference exists, return defaults
            $defaults = self::getDefaultPreferences();
            $defaultSettings = $defaults[$notificationType] ?? null;
            
            if (!$defaultSettings || !$defaultSettings['is_enabled']) {
                return [];
            }

            return $defaultSettings['channels'];
        }

        return $preference->getEnabledChannels();
    }

    /**
     * Update user's notification preferences
     */
    public static function updateUserPreferences(User $user, array $preferences): void
    {
        foreach ($preferences as $type => $settings) {
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
     * Get all preferences for a user
     */
    public static function getUserPreferences(User $user): array
    {
        $preferences = self::where('user_id', $user->id)->get()->keyBy('notification_type');
        $defaults = self::getDefaultPreferences();
        $result = [];

        foreach ($defaults as $type => $defaultSettings) {
            if (isset($preferences[$type])) {
                $result[$type] = $preferences[$type];
            } else {
                // Create a virtual preference object with defaults
                $result[$type] = new self(array_merge($defaultSettings, [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                ]));
            }
        }

        return $result;
    }
}