<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create a new notification
     *
     * @param User|int $user
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Notification
     */
    public function create($user, string $type, string $title, string $message, array $options = []): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $options['data'] ?? [],
            'action_url' => $options['action_url'] ?? null,
            'action_text' => $options['action_text'] ?? null,
            'priority' => $options['priority'] ?? 'normal',
            'category' => $options['category'] ?? 'general',
            'expires_at' => $options['expires_at'] ?? null
        ]);
    }

    /**
     * Send notification to multiple users
     *
     * @param Collection|array $users
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Collection
     */
    public function sendToMany($users, string $type, string $title, string $message, array $options = []): Collection
    {
        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push($this->create($user, $type, $title, $message, $options));
        }

        return $notifications;
    }

    /**
     * Send notification to all users
     *
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Collection
     */
    public function sendToAll(string $type, string $title, string $message, array $options = []): Collection
    {
        $users = User::all();
        return $this->sendToMany($users, $type, $title, $message, $options);
    }

    /**
     * Send notification to users with specific role
     *
     * @param string $role
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Collection
     */
    public function sendToRole(string $role, string $type, string $title, string $message, array $options = []): Collection
    {
        $users = User::where('role', $role)->get();
        return $this->sendToMany($users, $type, $title, $message, $options);
    }

    /**
     * Send notification to organization members
     *
     * @param int $organizationId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Collection
     */
    public function sendToOrganization(int $organizationId, string $type, string $title, string $message, array $options = []): Collection
    {
        $users = User::whereHas('organizations', function($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })->get();

        return $this->sendToMany($users, $type, $title, $message, $options);
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int|null $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, ?int $userId = null): bool
    {
        $query = Notification::where('id', $notificationId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $notification = $query->first();
        
        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param int $userId
     * @return int
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
                          ->whereNull('read_at')
                          ->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications for a user
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getUnread(int $userId, int $limit = 10): Collection
    {
        return Notification::where('user_id', $userId)
                          ->unread()
                          ->active()
                          ->orderBy('created', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Get unread count for a user
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
                          ->unread()
                          ->active()
                          ->count();
    }

    /**
     * Get recent notifications for a user
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $userId, int $limit = 20): Collection
    {
        return Notification::where('user_id', $userId)
                          ->active()
                          ->orderBy('created', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Delete notification
     *
     * @param int $notificationId
     * @param int|null $userId
     * @return bool
     */
    public function delete(int $notificationId, ?int $userId = null): bool
    {
        $query = Notification::where('id', $notificationId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->delete() > 0;
    }

    /**
     * Delete all read notifications for a user
     *
     * @param int $userId
     * @return int
     */
    public function deleteRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
                          ->read()
                          ->delete();
    }

    /**
     * Clean up expired notifications
     *
     * @return int
     */
    public function cleanupExpired(): int
    {
        return Notification::where('expires_at', '<', now())->delete();
    }

    /**
     * Send email notification
     *
     * @param User $user
     * @param string $subject
     * @param string $template
     * @param array $data
     * @return bool
     */
    public function sendEmail(User $user, string $subject, string $template, array $data = []): bool
    {
        try {
            Mail::send($template, $data, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)
                        ->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email notification: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'subject' => $subject,
                'template' => $template
            ]);

            return false;
        }
    }

    /**
     * Send notification with email
     *
     * @param User|int $user
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $options
     * @return array
     */
    public function createWithEmail($user, string $type, string $title, string $message, array $options = []): array
    {
        // Create in-app notification
        $notification = $this->create($user, $type, $title, $message, $options);

        // Send email if requested
        $emailSent = false;
        if ($options['send_email'] ?? false) {
            $userModel = $user instanceof User ? $user : User::find($user);
            if ($userModel) {
                $emailSent = $this->sendEmail(
                    $userModel,
                    $title,
                    $options['email_template'] ?? 'emails.notification',
                    array_merge($options['email_data'] ?? [], [
                        'notification' => $notification,
                        'user' => $userModel
                    ])
                );
            }
        }

        return [
            'notification' => $notification,
            'email_sent' => $emailSent
        ];
    }

    /**
     * Create system notification
     *
     * @param User|int $user
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Notification
     */
    public function system($user, string $title, string $message, array $options = []): Notification
    {
        return $this->create($user, 'system', $title, $message, array_merge($options, [
            'category' => 'system',
            'priority' => $options['priority'] ?? 'normal'
        ]));
    }

    /**
     * Create success notification
     *
     * @param User|int $user
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Notification
     */
    public function success($user, string $title, string $message, array $options = []): Notification
    {
        return $this->create($user, 'success', $title, $message, array_merge($options, [
            'priority' => 'normal'
        ]));
    }

    /**
     * Create warning notification
     *
     * @param User|int $user
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Notification
     */
    public function warning($user, string $title, string $message, array $options = []): Notification
    {
        return $this->create($user, 'warning', $title, $message, array_merge($options, [
            'priority' => 'high'
        ]));
    }

    /**
     * Create error notification
     *
     * @param User|int $user
     * @param string $title
     * @param string $message
     * @param array $options
     * @return Notification
     */
    public function error($user, string $title, string $message, array $options = []): Notification
    {
        return $this->create($user, 'error', $title, $message, array_merge($options, [
            'priority' => 'urgent'
        ]));
    }

    /**
     * Get notification statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::unread()->count(),
            'notifications_today' => Notification::whereDate('created', today())->count(),
            'notifications_this_week' => Notification::where('created', '>=', now()->startOfWeek())->count(),
            'notifications_by_type' => Notification::selectRaw('type, COUNT(*) as count')
                                                  ->groupBy('type')
                                                  ->pluck('count', 'type')
                                                  ->toArray(),
            'notifications_by_priority' => Notification::selectRaw('priority, COUNT(*) as count')
                                                      ->groupBy('priority')
                                                      ->pluck('count', 'priority')
                                                      ->toArray()
        ];
    }
}