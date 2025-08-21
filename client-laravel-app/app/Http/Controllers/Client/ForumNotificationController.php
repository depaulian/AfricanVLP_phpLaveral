<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ForumNotificationService;
use App\Models\ForumNotification;
use App\Models\ForumNotificationPreference;
use App\Models\ForumSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ForumNotificationController extends Controller
{
    protected ForumNotificationService $notificationService;

    public function __construct(ForumNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
    }

    /**
     * Display notifications page
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $notifications = ForumNotification::where('user_id', $user->id)
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return view('client.forums.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Get unread notifications (AJAX)
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->get('limit', 10);
        
        $notifications = $this->notificationService->getUnreadNotifications($user, $limit);
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'url' => $notification->url,
                    'icon' => $notification->icon,
                    'color_class' => $notification->color_class,
                    'time_ago' => $notification->time_ago,
                    'created_at' => $notification->created_at->toISOString(),
                ];
            }),
            'count' => $count,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, ForumNotification $notification): JsonResponse
    {
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->markAllAsRead($user);

        return response()->json(['success' => true]);
    }

    /**
     * Display notification preferences
     */
    public function preferences(Request $request): View
    {
        $user = $request->user();
        $preferences = ForumNotificationPreference::getUserPreferences($user);
        $types = ForumNotificationPreference::getNotificationTypes();

        return view('client.forums.notifications.preferences', compact('preferences', 'types'));
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'array',
            'preferences.*.email_enabled' => 'boolean',
            'preferences.*.in_app_enabled' => 'boolean',
            'preferences.*.digest_enabled' => 'boolean',
            'preferences.*.digest_frequency' => 'in:daily,weekly,monthly',
        ]);

        $preferences = $request->input('preferences');

        foreach ($preferences as $type => $settings) {
            ForumNotificationPreference::updateUserPreference($user, $type, $settings);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully.'
        ]);
    }

    /**
     * Display subscriptions page
     */
    public function subscriptions(Request $request): View
    {
        $user = $request->user();
        
        $subscriptions = $this->notificationService->getUserSubscriptions($user);
        
        $groupedSubscriptions = $subscriptions->groupBy('type');

        return view('client.forums.notifications.subscriptions', compact('groupedSubscriptions'));
    }

    /**
     * Subscribe to forum/thread/post
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'subscribable_type' => 'required|string|in:forum,thread,post',
            'subscribable_id' => 'required|integer',
            'type' => 'required|string|in:forum,thread,post',
            'preferences' => 'array',
        ]);

        $subscribableType = $request->input('subscribable_type');
        $subscribableId = $request->input('subscribable_id');
        $type = $request->input('type');
        $preferences = $request->input('preferences', []);

        // Get the subscribable model
        $subscribable = $this->getSubscribableModel($subscribableType, $subscribableId);
        
        if (!$subscribable) {
            return response()->json(['error' => 'Invalid subscribable entity'], 400);
        }

        $subscription = $this->notificationService->subscribe($user, $subscribable, $type, $preferences);

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed',
            'subscription' => [
                'id' => $subscription->id,
                'type' => $subscription->type,
                'display_name' => $subscription->display_name,
            ]
        ]);
    }

    /**
     * Unsubscribe from forum/thread/post
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'subscribable_type' => 'required|string|in:forum,thread,post',
            'subscribable_id' => 'required|integer',
            'type' => 'required|string|in:forum,thread,post',
        ]);

        $subscribableType = $request->input('subscribable_type');
        $subscribableId = $request->input('subscribable_id');
        $type = $request->input('type');

        // Get the subscribable model
        $subscribable = $this->getSubscribableModel($subscribableType, $subscribableId);
        
        if (!$subscribable) {
            return response()->json(['error' => 'Invalid subscribable entity'], 400);
        }

        $success = $this->notificationService->unsubscribe($user, $subscribable, $type);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Successfully unsubscribed' : 'Subscription not found'
        ]);
    }

    /**
     * Remove subscription by ID
     */
    public function removeSubscription(Request $request, ForumSubscription $subscription): JsonResponse
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription removed successfully'
        ]);
    }

    /**
     * Toggle subscription status
     */
    public function toggleSubscription(Request $request, ForumSubscription $subscription): JsonResponse
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($subscription->is_active) {
            $subscription->deactivate();
            $message = 'Subscription deactivated';
        } else {
            $subscription->activate();
            $message = 'Subscription activated';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_active' => $subscription->is_active
        ]);
    }

    /**
     * Get subscribable model instance
     */
    protected function getSubscribableModel(string $type, int $id)
    {
        switch ($type) {
            case 'forum':
                return \App\Models\Forum::find($id);
            case 'thread':
                return \App\Models\ForumThread::find($id);
            case 'post':
                return \App\Models\ForumPost::find($id);
            default:
                return null;
        }
    }

    /**
     * Check subscription status
     */
    public function checkSubscription(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'subscribable_type' => 'required|string|in:forum,thread,post',
            'subscribable_id' => 'required|integer',
            'type' => 'required|string|in:forum,thread,post',
        ]);

        $subscribableType = $request->input('subscribable_type');
        $subscribableId = $request->input('subscribable_id');
        $type = $request->input('type');

        $subscribable = $this->getSubscribableModel($subscribableType, $subscribableId);
        
        if (!$subscribable) {
            return response()->json(['error' => 'Invalid subscribable entity'], 400);
        }

        $isSubscribed = $this->notificationService->isSubscribed($user, $subscribable, $type);

        return response()->json([
            'is_subscribed' => $isSubscribed
        ]);
    }

    /**
     * Get notification count (for header badge)
     */
    public function count(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json(['count' => $count]);
    }
}