<?php

namespace App\Http\Controllers\Admin;

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
     * Display notifications management page
     */
    public function index(Request $request): View
    {
        $notifications = ForumNotification::with(['user', 'notifiable'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $stats = [
            'total' => ForumNotification::count(),
            'unread' => ForumNotification::unread()->count(),
            'today' => ForumNotification::whereDate('created_at', today())->count(),
            'this_week' => ForumNotification::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return view('admin.forums.notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Display notification details
     */
    public function show(ForumNotification $notification): View
    {
        $notification->load(['user', 'notifiable']);
        
        return view('admin.forums.notifications.show', compact('notification'));
    }

    /**
     * Display subscriptions management
     */
    public function subscriptions(Request $request): View
    {
        $subscriptions = ForumSubscription::with(['user', 'subscribable'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $stats = [
            'total' => ForumSubscription::count(),
            'active' => ForumSubscription::active()->count(),
            'forum_subs' => ForumSubscription::ofType('forum')->count(),
            'thread_subs' => ForumSubscription::ofType('thread')->count(),
        ];

        return view('admin.forums.notifications.subscriptions', compact('subscriptions', 'stats'));
    }

    /**
     * Display notification preferences management
     */
    public function preferences(Request $request): View
    {
        $preferences = ForumNotificationPreference::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $types = ForumNotificationPreference::getNotificationTypes();

        $stats = [
            'total_users' => ForumNotificationPreference::distinct('user_id')->count(),
            'email_enabled' => ForumNotificationPreference::emailEnabled()->count(),
            'digest_enabled' => ForumNotificationPreference::digestEnabled()->count(),
        ];

        return view('admin.forums.notifications.preferences', compact('preferences', 'types', 'stats'));
    }

    /**
     * Bulk mark notifications as read
     */
    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:forum_notifications,id',
        ]);

        $notificationIds = $request->input('notification_ids');
        
        ForumNotification::whereIn('id', $notificationIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read successfully'
        ]);
    }

    /**
     * Bulk delete notifications
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:forum_notifications,id',
        ]);

        $notificationIds = $request->input('notification_ids');
        
        ForumNotification::whereIn('id', $notificationIds)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifications deleted successfully'
        ]);
    }

    /**
     * Clean up old notifications
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days_old' => 'required|integer|min:1|max:365',
        ]);

        $daysOld = $request->input('days_old');
        $deletedCount = $this->notificationService->cleanupOldNotifications($daysOld);

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$deletedCount} old notifications"
        ]);
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'url' => 'nullable|url',
        ]);

        $user = \App\Models\User::find($request->input('user_id'));
        $type = $request->input('type');
        $title = $request->input('title');
        $message = $request->input('message');
        $url = $request->input('url', route('admin.forums.index'));

        // Create a dummy notifiable (using the user itself)
        $notifications = $this->notificationService->createNotification(
            collect([$user]),
            $type,
            $user,
            $title,
            $message,
            $url,
            ['test' => true]
        );

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent successfully',
            'notification_id' => $notifications->first()->id ?? null
        ]);
    }

    /**
     * Get notification statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $period = $request->get('period', '7'); // days
        $startDate = now()->subDays($period);

        $stats = [
            'total_notifications' => ForumNotification::where('created_at', '>=', $startDate)->count(),
            'by_type' => ForumNotification::where('created_at', '>=', $startDate)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_day' => ForumNotification::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
            'read_rate' => [
                'total' => ForumNotification::where('created_at', '>=', $startDate)->count(),
                'read' => ForumNotification::where('created_at', '>=', $startDate)->whereNotNull('read_at')->count(),
            ],
            'email_stats' => [
                'sent' => ForumNotification::where('created_at', '>=', $startDate)->where('email_sent', true)->count(),
                'pending' => ForumNotification::where('created_at', '>=', $startDate)->where('email_sent', false)->count(),
            ],
        ];

        // Calculate read rate percentage
        if ($stats['read_rate']['total'] > 0) {
            $stats['read_rate']['percentage'] = round(($stats['read_rate']['read'] / $stats['read_rate']['total']) * 100, 2);
        } else {
            $stats['read_rate']['percentage'] = 0;
        }

        return response()->json($stats);
    }

    /**
     * Export notifications data
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'type' => 'nullable|string',
        ]);

        $query = ForumNotification::with(['user', 'notifiable']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $notifications = $query->get();
        $format = $request->input('format');

        // For now, just return success - actual export implementation would go here
        return response()->json([
            'success' => true,
            'message' => "Export prepared for {$notifications->count()} notifications in {$format} format",
            'count' => $notifications->count()
        ]);
    }

    /**
     * Manage user subscription
     */
    public function manageSubscription(Request $request, ForumSubscription $subscription): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        $action = $request->input('action');

        switch ($action) {
            case 'activate':
                $subscription->activate();
                $message = 'Subscription activated';
                break;
            case 'deactivate':
                $subscription->deactivate();
                $message = 'Subscription deactivated';
                break;
            case 'delete':
                $subscription->delete();
                $message = 'Subscription deleted';
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Update user notification preferences (admin override)
     */
    public function updateUserPreferences(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'preferences' => 'required|array',
            'preferences.*' => 'array',
            'preferences.*.email_enabled' => 'boolean',
            'preferences.*.in_app_enabled' => 'boolean',
            'preferences.*.digest_enabled' => 'boolean',
            'preferences.*.digest_frequency' => 'in:daily,weekly,monthly',
        ]);

        $user = \App\Models\User::find($request->input('user_id'));
        $preferences = $request->input('preferences');

        foreach ($preferences as $type => $settings) {
            ForumNotificationPreference::updateUserPreference($user, $type, $settings);
        }

        return response()->json([
            'success' => true,
            'message' => 'User notification preferences updated successfully'
        ]);
    }
}