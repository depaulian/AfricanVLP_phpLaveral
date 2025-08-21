<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserFeedback;
use App\Models\UserFeedbackResponse;
use App\Models\UserFeedbackAttachment;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserFeedbackController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display feedback dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = UserFeedback::with(['user', 'admin', 'responses', 'attachments'])
            ->orderBy('created', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created', '<=', $request->date_to);
        }

        $feedback = $query->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'feedback' => $feedback,
                'stats' => $this->getFeedbackStats(),
            ]);
        }

        return view('admin.feedback.index', compact('feedback'));
    }

    /**
     * Show specific feedback item
     */
    public function show(UserFeedback $feedback): View|JsonResponse
    {
        $feedback->load(['user', 'admin', 'responses.admin', 'attachments.uploadedBy']);

        if (request()->expectsJson()) {
            return response()->json(['feedback' => $feedback]);
        }

        return view('admin.feedback.show', compact('feedback'));
    }

    /**
     * Update feedback status, priority, or assignment
     */
    public function update(Request $request, UserFeedback $feedback): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,in_review,responded,implemented,closed',
            'priority' => 'sometimes|in:critical,high,medium,low',
            'admin_id' => 'sometimes|nullable|exists:users,id',
            'is_public' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $oldData = $feedback->toArray();
        $feedback->update($request->only([
            'status', 'priority', 'admin_id', 'is_public', 'is_featured', 'tags'
        ]));

        // Log the update
        $this->activityLogService->log(
            'update',
            $feedback,
            auth()->user(),
            "Updated feedback: {$feedback->title}",
            ['old_data' => $oldData, 'new_data' => $feedback->fresh()->toArray()]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Feedback updated successfully',
                'feedback' => $feedback->fresh()
            ]);
        }

        return redirect()->route('admin.feedback.show', $feedback)
                        ->with('success', 'Feedback updated successfully');
    }

    /**
     * Add response to feedback
     */
    public function addResponse(Request $request, UserFeedback $feedback): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:10',
            'is_internal' => 'boolean',
            'is_solution' => 'boolean',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,txt,csv',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Create response
            $response = UserFeedbackResponse::create([
                'user_feedback_id' => $feedback->id,
                'admin_id' => auth()->id(),
                'message' => $request->message,
                'is_internal' => $request->boolean('is_internal'),
                'is_solution' => $request->boolean('is_solution'),
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('feedback/responses', $filename, 'public');

                    UserFeedbackAttachment::create([
                        'user_feedback_id' => $feedback->id,
                        'user_feedback_response_id' => $response->id,
                        'filename' => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            // Update feedback status if this is a solution
            if ($request->boolean('is_solution')) {
                $feedback->update([
                    'status' => 'responded',
                    'admin_id' => auth()->id(),
                    'responded_at' => now(),
                ]);
            }

            // Log the response
            $this->activityLogService->log(
                'create',
                $response,
                auth()->user(),
                "Added response to feedback: {$feedback->title}"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Response added successfully',
                    'response' => $response->load('admin')
                ]);
            }

            return redirect()->route('admin.feedback.show', $feedback)
                            ->with('success', 'Response added successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to add response'], 500);
            }
            
            return back()->with('error', 'Failed to add response');
        }
    }

    /**
     * Bulk update feedback items
     */
    public function bulkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'feedback_ids' => 'required|array',
            'feedback_ids.*' => 'exists:user_feedback,id',
            'action' => 'required|in:status,priority,assign,delete',
            'value' => 'required_unless:action,delete',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        $feedbackItems = UserFeedback::whereIn('id', $request->feedback_ids)->get();
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($feedbackItems as $feedback) {
                switch ($request->action) {
                    case 'status':
                        $feedback->update(['status' => $request->value]);
                        break;
                    case 'priority':
                        $feedback->update(['priority' => $request->value]);
                        break;
                    case 'assign':
                        $feedback->update(['admin_id' => $request->value]);
                        break;
                    case 'delete':
                        $feedback->delete();
                        break;
                }

                $this->activityLogService->log(
                    $request->action === 'delete' ? 'delete' : 'update',
                    $feedback,
                    auth()->user(),
                    "Bulk {$request->action} on feedback: {$feedback->title}"
                );

                $updated++;
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Successfully updated {$updated} feedback items"
                ]);
            }

            return back()->with('success', "Successfully updated {$updated} feedback items");

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bulk update failed'], 500);
            }
            
            return back()->with('error', 'Bulk update failed');
        }
    }

    /**
     * Get feedback statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getFeedbackStats());
    }

    /**
     * Export feedback data as CSV
     */
    public function export(Request $request)
    {
        $query = UserFeedback::with(['user', 'admin']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        $feedback = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($feedback) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Title', 'Type', 'Category', 'Priority', 'Status', 'Rating',
                'User Name', 'User Email', 'Admin Name', 'Created', 'Responded At',
                'Is Public', 'Is Featured', 'Message'
            ]);

            // CSV data
            foreach ($feedback as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->title,
                    $item->type,
                    $item->category,
                    $item->priority,
                    $item->status,
                    $item->rating,
                    $item->user->name ?? '',
                    $item->user->email ?? '',
                    $item->admin->name ?? '',
                    $item->created->format('Y-m-d H:i:s'),
                    $item->responded_at ? $item->responded_at->format('Y-m-d H:i:s') : '',
                    $item->is_public ? 'Yes' : 'No',
                    $item->is_featured ? 'Yes' : 'No',
                    strip_tags($item->message),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download feedback attachment
     */
    public function downloadAttachment(UserFeedbackAttachment $attachment)
    {
        if (!$attachment->fileExists()) {
            abort(404, 'File not found');
        }

        // Log download
        $this->activityLogService->log(
            'download',
            $attachment,
            auth()->user(),
            "Downloaded feedback attachment: {$attachment->original_filename}"
        );

        return Storage::download($attachment->file_path, $attachment->original_filename);
    }

    /**
     * Delete feedback attachment
     */
    public function deleteAttachment(UserFeedbackAttachment $attachment): JsonResponse|RedirectResponse
    {
        try {
            $attachment->deleteFile();
            $attachment->delete();

            $this->activityLogService->log(
                'delete',
                $attachment,
                auth()->user(),
                "Deleted feedback attachment: {$attachment->original_filename}"
            );

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Attachment deleted successfully']);
            }

            return back()->with('success', 'Attachment deleted successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete attachment'], 500);
            }
            
            return back()->with('error', 'Failed to delete attachment');
        }
    }

    /**
     * Get feedback analytics data
     */
    public function analytics(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30));
        $dateTo = $request->get('date_to', Carbon::now());

        // Feedback by type
        $feedbackByType = UserFeedback::selectRaw('type, COUNT(*) as count')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('type')
            ->get();

        // Feedback by status
        $feedbackByStatus = UserFeedback::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('status')
            ->get();

        // Feedback by priority
        $feedbackByPriority = UserFeedback::selectRaw('priority, COUNT(*) as count')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('priority')
            ->get();

        // Daily feedback count
        $dailyFeedback = UserFeedback::selectRaw('DATE(created) as date, COUNT(*) as count')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Average rating by category
        $avgRatingByCategory = UserFeedback::selectRaw('category, AVG(rating) as avg_rating, COUNT(*) as count')
            ->whereNotNull('rating')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('category')
            ->get();

        // Response time analytics
        $responseTimeStats = UserFeedback::selectRaw('
                AVG(TIMESTAMPDIFF(HOUR, created, responded_at)) as avg_response_hours,
                MIN(TIMESTAMPDIFF(HOUR, created, responded_at)) as min_response_hours,
                MAX(TIMESTAMPDIFF(HOUR, created, responded_at)) as max_response_hours
            ')
            ->whereNotNull('responded_at')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->first();

        return response()->json([
            'feedback_by_type' => $feedbackByType,
            'feedback_by_status' => $feedbackByStatus,
            'feedback_by_priority' => $feedbackByPriority,
            'daily_feedback' => $dailyFeedback,
            'avg_rating_by_category' => $avgRatingByCategory,
            'response_time_stats' => $responseTimeStats,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Get feedback statistics for dashboard
     */
    private function getFeedbackStats(): array
    {
        $total = UserFeedback::count();
        $pending = UserFeedback::where('status', 'pending')->count();
        $inReview = UserFeedback::where('status', 'in_review')->count();
        $responded = UserFeedback::where('status', 'responded')->count();
        $avgRating = UserFeedback::whereNotNull('rating')->avg('rating');
        
        $recentFeedback = UserFeedback::where('created', '>=', Carbon::now()->subDays(7))->count();
        $criticalFeedback = UserFeedback::where('priority', 'critical')
                                      ->whereIn('status', ['pending', 'in_review'])
                                      ->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'in_review' => $inReview,
            'responded' => $responded,
            'avg_rating' => round($avgRating, 2),
            'recent_feedback' => $recentFeedback,
            'critical_feedback' => $criticalFeedback,
            'response_rate' => $total > 0 ? round(($responded / $total) * 100, 2) : 0,
        ];
    }
}
