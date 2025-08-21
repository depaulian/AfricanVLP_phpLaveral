<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuMessage;
use App\Models\AuMessageAttachment;
use App\Models\User;
use App\Models\Organization;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuMessageController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display messages inbox
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = AuMessage::with(['sender', 'recipient', 'organization', 'attachments'])
            ->orderBy('created', 'desc');

        // Apply filters
        if ($request->filled('folder')) {
            switch ($request->folder) {
                case 'inbox':
                    $query->received(auth()->id());
                    break;
                case 'sent':
                    $query->sent(auth()->id());
                    break;
                case 'unread':
                    $query->received(auth()->id())->unread();
                    break;
                case 'archived':
                    $query->received(auth()->id())->byStatus('archived');
                    break;
            }
        } else {
            // Default to inbox
            $query->received(auth()->id());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%")
                  ->orWhereHas('sender', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created', '<=', $request->date_to);
        }

        // Only show thread root messages in list view
        $query->threadRoots();

        $messages = $query->paginate(20);
        $organizations = Organization::orderBy('name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'messages' => $messages,
                'organizations' => $organizations,
                'stats' => $this->getMessageStats(),
            ]);
        }

        return view('admin.au-messages.index', compact('messages', 'organizations'));
    }

    /**
     * Show compose message form
     */
    public function create(): View
    {
        $users = User::where('is_active', true)->orderBy('name')->get();
        $organizations = Organization::orderBy('name')->get();
        $types = AuMessage::getAvailableTypes();
        $priorities = AuMessage::getAvailablePriorities();

        return view('admin.au-messages.create', compact('users', 'organizations', 'types', 'priorities'));
    }

    /**
     * Send new message
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|in:' . implode(',', AuMessage::getAvailableTypes()),
            'priority' => 'required|in:' . implode(',', AuMessage::getAvailablePriorities()),
            'organization_id' => 'sometimes|nullable|exists:organizations,id',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'expires_at' => 'sometimes|nullable|date|after:today',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,txt,csv,zip',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $message = AuMessage::create([
                'sender_id' => auth()->id(),
                'recipient_id' => $request->recipient_id,
                'subject' => $request->subject,
                'body' => $request->body,
                'type' => $request->type,
                'priority' => $request->priority,
                'status' => AuMessage::STATUS_SENT,
                'organization_id' => $request->organization_id,
                'tags' => $request->tags ?? [],
                'expires_at' => $request->expires_at,
                'is_read' => false,
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('au-messages/attachments', $filename, 'public');

                    AuMessageAttachment::create([
                        'au_message_id' => $message->id,
                        'filename' => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            // Log the message sending
            $this->activityLogService->log(
                'create',
                $message,
                auth()->user(),
                "Sent AU message to {$message->recipient->name}: {$message->subject}"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Message sent successfully',
                    'au_message' => $message->load(['recipient', 'attachments'])
                ]);
            }

            return redirect()->route('admin.au-messages.show', $message)
                            ->with('success', 'Message sent successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to send message'], 500);
            }
            
            return back()->with('error', 'Failed to send message');
        }
    }

    /**
     * Show specific message
     */
    public function show(AuMessage $auMessage): View|JsonResponse
    {
        // Mark as read if recipient is viewing
        if ($auMessage->recipient_id === auth()->id() && !$auMessage->is_read) {
            $auMessage->markAsRead();
        }

        $auMessage->load(['sender', 'recipient', 'organization', 'replies.sender', 'replies.attachments', 'attachments']);

        if (request()->expectsJson()) {
            return response()->json(['message' => $auMessage]);
        }

        return view('admin.au-messages.show', compact('auMessage'));
    }

    /**
     * Reply to message
     */
    public function reply(Request $request, AuMessage $auMessage): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,txt,csv,zip',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Determine thread ID
            $threadId = $auMessage->thread_id ?? $auMessage->id;

            $reply = AuMessage::create([
                'sender_id' => auth()->id(),
                'recipient_id' => $auMessage->sender_id, // Reply to original sender
                'subject' => 'Re: ' . $auMessage->subject,
                'body' => $request->body,
                'type' => $auMessage->type,
                'priority' => $auMessage->priority,
                'status' => AuMessage::STATUS_SENT,
                'parent_id' => $auMessage->id,
                'thread_id' => $threadId,
                'organization_id' => $auMessage->organization_id,
                'is_read' => false,
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('au-messages/attachments', $filename, 'public');

                    AuMessageAttachment::create([
                        'au_message_id' => $reply->id,
                        'filename' => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            // Update original message replied status
            $auMessage->update([
                'status' => AuMessage::STATUS_REPLIED,
                'replied_at' => now(),
            ]);

            // Log the reply
            $this->activityLogService->log(
                'create',
                $reply,
                auth()->user(),
                "Replied to AU message: {$auMessage->subject}"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Reply sent successfully',
                    'reply' => $reply->load(['sender', 'attachments'])
                ]);
            }

            return redirect()->route('admin.au-messages.show', $auMessage)
                            ->with('success', 'Reply sent successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to send reply'], 500);
            }
            
            return back()->with('error', 'Failed to send reply');
        }
    }

    /**
     * Mark message as read/unread
     */
    public function markRead(Request $request, AuMessage $auMessage): JsonResponse|RedirectResponse
    {
        $read = $request->boolean('read', true);

        if ($read) {
            $auMessage->markAsRead();
        } else {
            $auMessage->markAsUnread();
        }

        // Log the action
        $action = $read ? 'marked as read' : 'marked as unread';
        $this->activityLogService->log(
            'update',
            $auMessage,
            auth()->user(),
            "AU message {$action}: {$auMessage->subject}"
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Message {$action} successfully",
                'is_read' => $auMessage->is_read
            ]);
        }

        return back()->with('success', "Message {$action} successfully");
    }

    /**
     * Archive/unarchive message
     */
    public function archive(Request $request, AuMessage $auMessage): JsonResponse|RedirectResponse
    {
        $archive = $request->boolean('archive', true);
        $status = $archive ? AuMessage::STATUS_ARCHIVED : AuMessage::STATUS_DELIVERED;

        $auMessage->update(['status' => $status]);

        // Log the action
        $action = $archive ? 'archived' : 'unarchived';
        $this->activityLogService->log(
            'update',
            $auMessage,
            auth()->user(),
            "AU message {$action}: {$auMessage->subject}"
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Message {$action} successfully",
                'status' => $auMessage->status
            ]);
        }

        return back()->with('success', "Message {$action} successfully");
    }

    /**
     * Delete message
     */
    public function destroy(AuMessage $auMessage): JsonResponse|RedirectResponse
    {
        try {
            $subject = $auMessage->subject;

            // Delete attachments
            foreach ($auMessage->attachments as $attachment) {
                $attachment->deleteFile();
                $attachment->delete();
            }

            $auMessage->delete();

            // Log the deletion
            $this->activityLogService->log(
                'delete',
                $auMessage,
                auth()->user(),
                "Deleted AU message: {$subject}"
            );

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Message deleted successfully']);
            }

            return redirect()->route('admin.au-messages.index')
                            ->with('success', 'Message deleted successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete message'], 500);
            }
            
            return back()->with('error', 'Failed to delete message');
        }
    }

    /**
     * Bulk operations on messages
     */
    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:au_messages,id',
            'action' => 'required|in:mark_read,mark_unread,archive,unarchive,delete',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        $messages = AuMessage::whereIn('id', $request->message_ids)->get();
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($messages as $message) {
                switch ($request->action) {
                    case 'mark_read':
                        $message->markAsRead();
                        break;
                    case 'mark_unread':
                        $message->markAsUnread();
                        break;
                    case 'archive':
                        $message->update(['status' => AuMessage::STATUS_ARCHIVED]);
                        break;
                    case 'unarchive':
                        $message->update(['status' => AuMessage::STATUS_DELIVERED]);
                        break;
                    case 'delete':
                        foreach ($message->attachments as $attachment) {
                            $attachment->deleteFile();
                            $attachment->delete();
                        }
                        $message->delete();
                        break;
                }

                $this->activityLogService->log(
                    $request->action === 'delete' ? 'delete' : 'update',
                    $message,
                    auth()->user(),
                    "Bulk {$request->action} on AU message: {$message->subject}"
                );

                $updated++;
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Successfully processed {$updated} messages"
                ]);
            }

            return back()->with('success', "Successfully processed {$updated} messages");

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bulk action failed'], 500);
            }
            
            return back()->with('error', 'Bulk action failed');
        }
    }

    /**
     * Get message statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getMessageStats());
    }

    /**
     * Download message attachment
     */
    public function downloadAttachment(AuMessageAttachment $attachment)
    {
        if (!$attachment->fileExists()) {
            abort(404, 'File not found');
        }

        // Log download
        $this->activityLogService->log(
            'download',
            $attachment,
            auth()->user(),
            "Downloaded AU message attachment: {$attachment->original_filename}"
        );

        return Storage::download($attachment->file_path, $attachment->original_filename);
    }

    /**
     * Export messages as CSV
     */
    public function export(Request $request)
    {
        $query = AuMessage::with(['sender', 'recipient', 'organization']);

        // Apply same filters as index
        if ($request->filled('folder')) {
            switch ($request->folder) {
                case 'inbox':
                    $query->received(auth()->id());
                    break;
                case 'sent':
                    $query->sent(auth()->id());
                    break;
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created', '<=', $request->date_to);
        }

        $messages = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="au_messages_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($messages) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Subject', 'Type', 'Priority', 'Status', 'Sender', 'Recipient',
                'Organization', 'Created', 'Read At', 'Is Read', 'Body Preview'
            ]);

            // CSV data
            foreach ($messages as $message) {
                fputcsv($file, [
                    $message->id,
                    $message->subject,
                    $message->type,
                    $message->priority,
                    $message->status,
                    $message->sender->name ?? '',
                    $message->recipient->name ?? '',
                    $message->organization->name ?? '',
                    $message->created->format('Y-m-d H:i:s'),
                    $message->read_at ? $message->read_at->format('Y-m-d H:i:s') : '',
                    $message->is_read ? 'Yes' : 'No',
                    $message->preview,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get message statistics for dashboard
     */
    private function getMessageStats(): array
    {
        $userId = auth()->id();
        
        $totalReceived = AuMessage::received($userId)->count();
        $unreadCount = AuMessage::received($userId)->unread()->count();
        $totalSent = AuMessage::sent($userId)->count();
        $archivedCount = AuMessage::received($userId)->byStatus('archived')->count();
        
        $recentMessages = AuMessage::received($userId)
            ->where('created', '>=', Carbon::now()->subDays(7))
            ->count();

        $urgentMessages = AuMessage::received($userId)
            ->byPriority('urgent')
            ->unread()
            ->count();

        // Type distribution
        $typeDistribution = AuMessage::received($userId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Priority distribution
        $priorityDistribution = AuMessage::received($userId)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total_received' => $totalReceived,
            'unread_count' => $unreadCount,
            'total_sent' => $totalSent,
            'archived_count' => $archivedCount,
            'recent_messages' => $recentMessages,
            'urgent_messages' => $urgentMessages,
            'type_distribution' => $typeDistribution,
            'priority_distribution' => $priorityDistribution,
            'read_rate' => $totalReceived > 0 ? round((($totalReceived - $unreadCount) / $totalReceived) * 100, 2) : 0,
        ];
    }
}
