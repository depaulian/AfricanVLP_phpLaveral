<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display activity logs with filtering options
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'user_id', 'action', 'subject_type', 
            'start_date', 'end_date', 'per_page'
        ]);

        // Set default date range if not provided (last 30 days)
        if (!isset($filters['start_date'])) {
            $filters['start_date'] = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        if (!isset($filters['end_date'])) {
            $filters['end_date'] = Carbon::now()->format('Y-m-d');
        }

        $activityLogs = $this->activityLogService->getActivityLogs($filters);
        $stats = $this->activityLogService->getActivityStats($filters);

        // Get filter options
        $users = User::select('id', 'name', 'email')
            ->whereHas('activityLogs')
            ->orderBy('name')
            ->get();

        $actions = ActivityLog::distinct('action')
            ->orderBy('action')
            ->pluck('action');

        $subjectTypes = ActivityLog::distinct('subject_type')
            ->whereNotNull('subject_type')
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type)
                ];
            });

        return view('admin.activity-logs.index', compact(
            'activityLogs', 'stats', 'users', 'actions', 
            'subjectTypes', 'filters'
        ));
    }

    /**
     * Show detailed view of a specific activity log
     */
    public function show(ActivityLog $activityLog): View
    {
        $activityLog->load(['user', 'subject']);
        
        return view('admin.activity-logs.show', compact('activityLog'));
    }

    /**
     * Export activity logs to CSV
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'user_id', 'action', 'subject_type', 
            'start_date', 'end_date'
        ]);

        // Remove pagination for export
        $filters['per_page'] = null;
        
        $activityLogs = ActivityLog::with(['user', 'subject'])
            ->when(isset($filters['user_id']), fn($q) => $q->byUser($filters['user_id']))
            ->when(isset($filters['action']), fn($q) => $q->ofAction($filters['action']))
            ->when(isset($filters['subject_type']), fn($q) => $q->forSubjectType($filters['subject_type']))
            ->when(isset($filters['start_date']) && isset($filters['end_date']), 
                fn($q) => $q->betweenDates($filters['start_date'], $filters['end_date']))
            ->orderBy('created_at', 'desc')
            ->get();

        // Log the export activity
        $this->activityLogService->logExport('Activity Logs', [
            'filters' => $filters,
            'record_count' => $activityLogs->count()
        ]);

        $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($activityLogs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Date/Time', 'User', 'Action', 'Description', 
                'Subject Type', 'Subject ID', 'IP Address', 'Properties'
            ]);

            foreach ($activityLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? $log->user->name : 'System',
                    $log->action,
                    $log->description,
                    $log->subject_type ? class_basename($log->subject_type) : '',
                    $log->subject_id ?? '',
                    $log->ip_address ?? '',
                    $log->formatted_properties
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get activity statistics for dashboard widgets
     */
    public function stats(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date']);
        
        // Default to last 7 days for dashboard stats
        if (!isset($filters['start_date'])) {
            $filters['start_date'] = Carbon::now()->subDays(7)->format('Y-m-d');
        }
        if (!isset($filters['end_date'])) {
            $filters['end_date'] = Carbon::now()->format('Y-m-d');
        }

        $stats = $this->activityLogService->getActivityStats($filters);

        return response()->json($stats);
    }

    /**
     * Get recent activities for dashboard
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $recentActivities = ActivityLog::with(['user', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->full_description,
                    'user' => $log->user ? $log->user->name : 'System',
                    'created_at' => $log->created_at->diffForHumans(),
                    'created_at_formatted' => $log->created_at->format('M j, Y g:i A'),
                ];
            });

        return response()->json($recentActivities);
    }

    /**
     * Delete old activity logs (cleanup)
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:30|max:365'
        ]);

        $days = $request->get('days', 90);
        $cutoffDate = Carbon::now()->subDays($days);
        
        $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();

        // Log the cleanup activity
        $this->activityLogService->log(
            'cleanup',
            'Cleaned up old activity logs',
            null,
            [
                'days_retained' => $days,
                'records_deleted' => $deletedCount,
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} old activity logs.",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Get user activity timeline
     */
    public function userTimeline(Request $request, User $user): JsonResponse
    {
        $limit = $request->get('limit', 50);
        
        $activities = ActivityLog::where('user_id', $user->id)
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->full_description,
                    'created_at' => $log->created_at->format('M j, Y g:i A'),
                    'created_at_human' => $log->created_at->diffForHumans(),
                    'properties' => $log->properties,
                ];
            });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'activities' => $activities
        ]);
    }
}
