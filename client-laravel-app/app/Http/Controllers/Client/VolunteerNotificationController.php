<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VolunteerNotification;
use App\Models\VolunteerNotificationPreference;
use App\Services\VolunteerNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VolunteerNotificationController extends Controller
{
    public function __construct(
        private VolunteerNotificationService $notificationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display user's notifications
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $filters = $request->only(['type', 'channel', 'is_read', 'priority']);
        
        $query = VolunteerNotification::where('user_id', $user->id)
            ->with('related')
            ->when($filters['type'] ?? null, fn($q) => $q->ofType($filters['type']))
            ->when($filters['channel'] ?? null, fn($q) => $q->forChannel($filters['channel']))
            ->when(isset($filters['is_read']), fn($q) => $filters['is_read'] ? $q->read() : $q->unread())
            ->when($filters['priority'] ?? null, fn($q) => $q->where('priority', $filters['priority']))
            ->orderBy('created_at', 'desc');

        $notifications = $query->paginate(20);
        
        // Get filter options
        $types = VolunteerNotification::where('user_id', $user->id)
            ->distinct()
            ->pluck('type')
            ->map(fn($type) => ['value' => $type, 'label' => ucwords(str_replace('_', ' ', $type))])
            ->toArray();
            
        $channels = VolunteerNotification::where('user_id', $user->id)
            ->distinct()
            ->pluck('channel')
            ->map(fn($channel) => ['value' => $channel, 'label' => ucfirst($channel)])
            ->toArray();

        $stats = $this->notificationService->getNotificationStats($user);

        return view('client.volunteering.notifications.index', compact(
            'notifications',
            'filters',
            'types',
            'channels',
            'stats'
        ));
    }

    /**
     * Show notification details
     */
    public function show(VolunteerNotification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        // Mark as read if not already
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return view('client.volunteering.notifications.show', compact('notification'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(VolunteerNotification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $notification->markAsRead();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        VolunteerNotification::where('user_id', $user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete notification
     */
    public function destroy(VolunteerNotification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $notification->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification deleted.');
    }

    /**
     * Bulk actions on notifications
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:mark_read,delete',
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:volunteer_notifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notifications = VolunteerNotification::where('user_id', $user->id)
            ->whereIn('id', $request->notification_ids)
            ->get();

        $action = $request->action;
        $count = 0;

        foreach ($notifications as $notification) {
            switch ($action) {
                case 'mark_read':
                    if (!$notification->is_read) {
                        $notification->markAsRead();
                        $count++;
                    }
                    break;
                    
                case 'delete':
                    $notification->delete();
                    $count++;
                    break;
            }
        }

        $message = match ($action) {
            'mark_read' => "{$count} notifications marked as read.",
            'delete' => "{$count} notifications deleted.",
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'count' => $count,
        ]);
    }

    /**
     * Display notification preferences
     */
    public function preferences()
    {
        $user = Auth::user();
        $preferences = VolunteerNotificationPreference::getUserPreferences($user);
        $availableChannels = ['database', 'email', 'sms', 'push'];

        return view('client.volunteering.notifications.preferences', compact(
            'preferences',
            'availableChannels'
        ));
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
            'preferences.*.is_enabled' => 'boolean',
            'preferences.*.channels' => 'array',
            'preferences.*.channels.*' => 'in:database,email,sms,push',
            'preferences.*.settings' => 'array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $preferences = $request->input('preferences', []);
        
        foreach ($preferences as $type => $settings) {
            VolunteerNotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                ],
                [
                    'is_enabled' => $settings['is_enabled'] ?? false,
                    'channels' => $settings['channels'] ?? [],
                    'settings' => $settings['settings'] ?? [],
                ]
            );
        }

        return redirect()->back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Get unread notification count (API endpoint)
     */
    public function unreadCount()
    {
        $user = Auth::user();
        
        $count = VolunteerNotification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get recent notifications (API endpoint)
     */
    public function recent(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 10);
        
        $notifications = VolunteerNotification::where('user_id', $user->id)
            ->with('related')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'url' => $notification->url,
                    'icon' => $notification->icon,
                    'priority_color' => $notification->priority_color,
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Test notification (for development/testing)
     */
    public function test(Request $request)
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'channel' => 'required|in:database,email,sms,push',
            'priority' => 'integer|min:1|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = VolunteerNotification::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'channel' => $request->channel,
            'priority' => $request->priority ?? 3,
            'data' => ['test' => true],
        ]);

        // Send the notification
        $this->notificationService->deliverNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent successfully.',
            'notification_id' => $notification->id,
        ]);
    }

    /**
     * Get notification statistics
     */
    public function statistics()
    {
        $user = Auth::user();
        $stats = $this->notificationService->getNotificationStats($user);

        return response()->json($stats);
    }
}