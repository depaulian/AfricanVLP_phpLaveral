<?php

namespace App\Services;

use App\Models\User;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\ForumSubscription;
use App\Models\ForumNotification;
use App\Models\ForumNotificationPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ForumNotificationService
{
    /**
     * Create a subscription for a user to a forum entity
     */
    public function subscribe(User $user, $subscribable, string $type, array $preferences = []): ForumSubscription
    {
        $subscription = ForumSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'subscribable_type' => get_class($subscribable),
                'subscribable_id' => $subscribable->id,
                'type' => $type,
            ],
            [
                'is_active' => true,
                'notification_preferences' => $preferences,
            ]
        );

        return $subscription;
    }

    /**
     * Remove a subscription
     */
    public function unsubscribe(User $user, $subscribable, string $type): bool
    {
        return ForumSubscription::where('user_id', $user->id)
            ->where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->id)
            ->where('type', $type)
            ->delete() > 0;
    }

    /**
     * Check if user is subscribed to an entity
     */
    public function isSubscribed(User $user, $subscribable, string $type): bool
    {
        return ForumSubscription::where('user_id', $user->id)
            ->where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->id)
            ->where('type', $type)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get user's subscriptions
     */
    public function getUserSubscriptions(User $user, ?string $type = null): Collection
    {
        $query = ForumSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('subscribable');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    /**
     * Create a notification for users
     */
    public function createNotification(
        Collection $users,
        string $type,
        $notifiable,
        string $title,
        string $message,
        string $url,
        array $additionalData = []
    ): Collection {
        $notifications = collect();

        foreach ($users as $user) {
            // Check if user wants this type of notification
            if (!ForumNotificationPreference::isInAppEnabledForUser($user, $type)) {
                continue;
            }

            $data = ForumNotification::createData($title, $message, $url, $additionalData);

            $notification = ForumNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'data' => $data,
            ]);

            $notifications->push($notification);

            // Send email if enabled
            if (ForumNotificationPreference::isEmailEnabledForUser($user, $type)) {
                $this->sendEmailNotification($notification);
            }
        }

        return $notifications;
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(ForumNotification $notification): void
    {
        try {
            // Here you would send the actual email
            // For now, we'll just mark it as sent
            $notification->markEmailAsSent();
            
            Log::info('Forum notification email sent', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'type' => $notification->type,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send forum notification email', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify about new reply
     */
    public function notifyNewReply(ForumPost $post): void
    {
        $thread = $post->thread;
        $forum = $thread->forum;
        
        // Get users to notify (thread subscribers, forum subscribers, mentioned users)
        $usersToNotify = collect();

        // Thread subscribers
        $threadSubscribers = $this->getSubscribers($thread, 'thread');
        $usersToNotify = $usersToNotify->merge($threadSubscribers);

        // Forum subscribers (for new replies)
        $forumSubscribers = $this->getSubscribers($forum, 'forum');
        $usersToNotify = $usersToNotify->merge($forumSubscribers);

        // Mentioned users
        $mentionedUsers = $this->extractMentionedUsers($post->content);
        $usersToNotify = $usersToNotify->merge($mentionedUsers);

        // Remove the post author from notifications
        $usersToNotify = $usersToNotify->reject(function ($user) use ($post) {
            return $user->id === $post->user_id;
        })->unique('id');

        if ($usersToNotify->isNotEmpty()) {
            $title = "New reply in: {$thread->title}";
            $message = "New reply by {$post->user->name}";
            $url = route('admin.forums.threads.show', [$forum, $thread]) . '#post-' . $post->id;

            $this->createNotification(
                $usersToNotify,
                'reply',
                $post,
                $title,
                $message,
                $url,
                [
                    'thread_title' => $thread->title,
                    'forum_name' => $forum->name,
                    'author_name' => $post->user->name,
                ]
            );
        }

        // Handle mentions separately
        if ($mentionedUsers->isNotEmpty()) {
            $this->notifyMentions($post, $mentionedUsers);
        }
    }

    /**
     * Notify about mentions
     */
    public function notifyMentions(ForumPost $post, Collection $mentionedUsers): void
    {
        $thread = $post->thread;
        $forum = $thread->forum;

        $title = "You were mentioned in: {$thread->title}";
        $message = "You were mentioned by {$post->user->name}";
        $url = route('admin.forums.threads.show', [$forum, $thread]) . '#post-' . $post->id;

        $this->createNotification(
            $mentionedUsers,
            'mention',
            $post,
            $title,
            $message,
            $url,
            [
                'thread_title' => $thread->title,
                'forum_name' => $forum->name,
                'author_name' => $post->user->name,
            ]
        );
    }

    /**
     * Notify about new thread
     */
    public function notifyNewThread(ForumThread $thread): void
    {
        $forum = $thread->forum;
        
        // Get forum subscribers
        $subscribers = $this->getSubscribers($forum, 'forum');

        // Remove the thread author from notifications
        $subscribers = $subscribers->reject(function ($user) use ($thread) {
            return $user->id === $thread->user_id;
        });

        if ($subscribers->isNotEmpty()) {
            $title = "New thread in {$forum->name}: {$thread->title}";
            $message = "New thread created by {$thread->user->name}";
            $url = route('admin.forums.threads.show', [$forum, $thread]);

            $this->createNotification(
                $subscribers,
                'thread_created',
                $thread,
                $title,
                $message,
                $url,
                [
                    'thread_title' => $thread->title,
                    'forum_name' => $forum->name,
                    'author_name' => $thread->user->name,
                ]
            );
        }
    }

    /**
     * Notify about vote
     */
    public function notifyVote(ForumPost $post, User $voter, string $voteType): void
    {
        // Don't notify if user voted on their own post
        if ($post->user_id === $voter->id) {
            return;
        }

        $thread = $post->thread;
        $forum = $thread->forum;

        $title = "Your post was {$voteType}d";
        $message = "{$voter->name} {$voteType}d your post";
        $url = route('admin.forums.threads.show', [$forum, $thread]) . '#post-' . $post->id;

        $this->createNotification(
            collect([$post->user]),
            'vote',
            $post,
            $title,
            $message,
            $url,
            [
                'thread_title' => $thread->title,
                'forum_name' => $forum->name,
                'voter_name' => $voter->name,
                'vote_type' => $voteType,
            ]
        );
    }

    /**
     * Notify about solution
     */
    public function notifySolution(ForumPost $post, User $marker): void
    {
        // Don't notify if user marked their own post as solution
        if ($post->user_id === $marker->id) {
            return;
        }

        $thread = $post->thread;
        $forum = $thread->forum;

        $title = "Your post was marked as solution";
        $message = "{$marker->name} marked your post as the solution";
        $url = route('admin.forums.threads.show', [$forum, $thread]) . '#post-' . $post->id;

        $this->createNotification(
            collect([$post->user]),
            'solution',
            $post,
            $title,
            $message,
            $url,
            [
                'thread_title' => $thread->title,
                'forum_name' => $forum->name,
                'marker_name' => $marker->name,
            ]
        );
    }

    /**
     * Get subscribers for an entity
     */
    protected function getSubscribers($subscribable, string $type): Collection
    {
        return ForumSubscription::where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->id)
            ->where('type', $type)
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    /**
     * Extract mentioned users from content
     */
    protected function extractMentionedUsers(string $content): Collection
    {
        // Extract @username mentions
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        
        if (empty($matches[1])) {
            return collect();
        }

        $usernames = array_unique($matches[1]);
        
        return User::whereIn('username', $usernames)->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(ForumNotification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): void
    {
        ForumNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(User $user, int $limit = 10): Collection
    {
        return ForumNotification::where('user_id', $user->id)
            ->unread()
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get notification count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return ForumNotification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Auto-subscribe user to thread when they create it
     */
    public function autoSubscribeToThread(ForumThread $thread): void
    {
        $this->subscribe($thread->user, $thread, 'thread', [
            'email_enabled' => true,
            'in_app_enabled' => true,
            'digest_enabled' => true,
        ]);
    }

    /**
     * Auto-subscribe user to thread when they reply
     */
    public function autoSubscribeToThreadOnReply(ForumPost $post): void
    {
        $thread = $post->thread;
        
        // Only subscribe if not already subscribed
        if (!$this->isSubscribed($post->user, $thread, 'thread')) {
            $this->subscribe($post->user, $thread, 'thread', [
                'email_enabled' => false, // Less intrusive for replies
                'in_app_enabled' => true,
                'digest_enabled' => true,
            ]);
        }
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $daysOld = 90): int
    {
        return ForumNotification::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}