<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerAssignment;
use App\Models\VolunteerTimeLog;
use App\Services\VolunteerTimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VolunteerTimeLogController extends Controller
{
    public function __construct(
        private VolunteerTimeTrackingService $timeTrackingService
    ) {
        $this->middleware('auth:admin');
    }

    /**
     * Display time logs for supervisor approval
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build query for time logs that need approval
        $query = VolunteerTimeLog::with([
            'assignment.application.user',
            'assignment.opportunity.organization',
            'assignment.supervisor',
            'approver'
        ]);

        // Filter by supervisor assignments if user is a supervisor
        if (!$user->hasRole('super-admin')) {
            $query->whereHas('assignment', function ($q) use ($user) {
                $q->where('supervisor_id', $user->id);
            });
        }

        // Apply filters
        if ($request->filled('assignment')) {
            $query->where('assignment_id', $request->assignment);
        }

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('supervisor_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('supervisor_approved', true);
            }
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('volunteer')) {
            $query->whereHas('assignment.application.user', function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->volunteer}%")
                  ->orWhere('email', 'LIKE', "%{$request->volunteer}%");
            });
        }

        $timeLogs = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get assignments for filter dropdown
        $assignmentsQuery = VolunteerAssignment::with(['opportunity', 'application.user']);
        
        if (!$user->hasRole('super-admin')) {
            $assignmentsQuery->where('supervisor_id', $user->id);
        }
        
        $assignments = $assignmentsQuery->get();

        // Count pending approvals
        $pendingQuery = VolunteerTimeLog::where('supervisor_approved', false);
        
        if (!$user->hasRole('super-admin')) {
            $pendingQuery->whereHas('assignment', function ($q) use ($user) {
                $q->where('supervisor_id', $user->id);
            });
        }
        
        $pendingCount = $pendingQuery->count();

        return view('admin.volunteering.time-logs.index', compact(
            'timeLogs',
            'assignments',
            'pendingCount'
        ));
    }

    /**
     * Show time log details
     */
    public function show(VolunteerTimeLog $timeLog)
    {
        $user = Auth::user();
        
        // Check if user can view this time log
        if (!$user->hasRole('super-admin') && $timeLog->assignment->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized access to time log.');
        }

        $timeLog->load([
            'assignment.application.user',
            'assignment.opportunity.organization',
            'assignment.supervisor',
            'approver'
        ]);

        return view('admin.volunteering.time-logs.show', compact('timeLog'));
    }

    /**
     * Approve time log
     */
    public function approve(VolunteerTimeLog $timeLog)
    {
        $user = Auth::user();
        
        // Check if user can approve this time log
        if (!$user->hasRole('super-admin') && $timeLog->assignment->supervisor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        if ($timeLog->supervisor_approved) {
            return response()->json(['success' => false, 'message' => 'Time log is already approved.']);
        }

        try {
            $this->timeTrackingService->approveTimeLog($timeLog, $user);
            
            return response()->json([
                'success' => true,
                'message' => 'Time log approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve time log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject time log
     */
    public function reject(VolunteerTimeLog $timeLog, Request $request)
    {
        $user = Auth::user();
        
        // Check if user can reject this time log
        if (!$user->hasRole('super-admin') && $timeLog->assignment->supervisor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        if ($timeLog->supervisor_approved) {
            return response()->json(['success' => false, 'message' => 'Cannot reject approved time log.']);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $this->timeTrackingService->rejectTimeLog($timeLog, $user, $request->reason);
            
            return response()->json([
                'success' => true,
                'message' => 'Time log rejected successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject time log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unapprove time log
     */
    public function unapprove(VolunteerTimeLog $timeLog)
    {
        $user = Auth::user();
        
        // Check if user can unapprove this time log
        if (!$user->hasRole('super-admin') && $timeLog->assignment->supervisor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        if (!$timeLog->supervisor_approved) {
            return response()->json(['success' => false, 'message' => 'Time log is not approved.']);
        }

        try {
            DB::transaction(function () use ($timeLog, $user) {
                // Update time log
                $timeLog->update([
                    'supervisor_approved' => false,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);

                // Update assignment hours
                $timeLog->assignment->decrement('hours_completed', $timeLog->hours);

                // Notify volunteer
                $timeLog->assignment->application->user->notify(
                    new \App\Notifications\VolunteerHoursUnapproved($timeLog)
                );
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Time log unapproved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unapprove time log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk approve time logs
     */
    public function bulkApprove(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'time_log_ids' => 'required|array',
            'time_log_ids.*' => 'exists:volunteer_time_logs,id'
        ]);

        try {
            $query = VolunteerTimeLog::whereIn('id', $request->time_log_ids)
                ->where('supervisor_approved', false);

            // Filter by supervisor if not super admin
            if (!$user->hasRole('super-admin')) {
                $query->whereHas('assignment', function ($q) use ($user) {
                    $q->where('supervisor_id', $user->id);
                });
            }

            $timeLogs = $query->get();

            if ($timeLogs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid time logs found for approval.'
                ]);
            }

            $approvedCount = 0;

            DB::transaction(function () use ($timeLogs, $user, &$approvedCount) {
                foreach ($timeLogs as $timeLog) {
                    $this->timeTrackingService->approveTimeLog($timeLog, $user);
                    $approvedCount++;
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$approvedCount} time log entries."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve time logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export time logs
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Build query
        $query = VolunteerTimeLog::with([
            'assignment.application.user',
            'assignment.opportunity.organization',
            'assignment.supervisor',
            'approver'
        ]);

        // Filter by supervisor if not super admin
        if (!$user->hasRole('super-admin')) {
            $query->whereHas('assignment', function ($q) use ($user) {
                $q->where('supervisor_id', $user->id);
            });
        }

        // Apply same filters as index
        if ($request->filled('assignment')) {
            $query->where('assignment_id', $request->assignment);
        }

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('supervisor_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('supervisor_approved', true);
            }
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $timeLogs = $query->orderBy('date', 'desc')->get();

        return $this->timeTrackingService->exportTimeLogsToCSV($timeLogs, $user);
    }

    /**
     * Get pending count for AJAX updates
     */
    public function pendingCount()
    {
        $user = Auth::user();
        
        $query = VolunteerTimeLog::where('supervisor_approved', false);
        
        if (!$user->hasRole('super-admin')) {
            $query->whereHas('assignment', function ($q) use ($user) {
                $q->where('supervisor_id', $user->id);
            });
        }
        
        $count = $query->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Time tracking analytics
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();
        
        // Build base query
        $query = VolunteerTimeLog::with([
            'assignment.application.user',
            'assignment.opportunity.organization'
        ]);

        // Filter by supervisor if not super admin
        if (!$user->hasRole('super-admin')) {
            $query->whereHas('assignment', function ($q) use ($user) {
                $q->where('supervisor_id', $user->id);
            });
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $timeLogs = $query->get();

        $analytics = [
            'total_entries' => $timeLogs->count(),
            'total_hours' => $timeLogs->sum('hours'),
            'approved_hours' => $timeLogs->where('supervisor_approved', true)->sum('hours'),
            'pending_hours' => $timeLogs->where('supervisor_approved', false)->sum('hours'),
            'unique_volunteers' => $timeLogs->pluck('assignment.application.user_id')->unique()->count(),
            'approval_rate' => $timeLogs->count() > 0 
                ? round(($timeLogs->where('supervisor_approved', true)->count() / $timeLogs->count()) * 100, 1)
                : 0,
            'hours_by_month' => $timeLogs->groupBy(function ($log) {
                return $log->date->format('Y-m');
            })->map->sum('hours'),
            'top_volunteers' => $timeLogs->groupBy('assignment.application.user_id')
                ->map(function ($logs) {
                    $user = $logs->first()->assignment->application->user;
                    return [
                        'name' => $user->name,
                        'hours' => $logs->sum('hours'),
                        'entries' => $logs->count()
                    ];
                })
                ->sortByDesc('hours')
                ->take(10)
                ->values()
        ];

        if ($request->wantsJson()) {
            return response()->json($analytics);
        }

        return view('admin.volunteering.time-logs.analytics', compact('analytics'));
    }

    /**
     * Send approval reminders
     */
    public function sendReminders()
    {
        try {
            $remindersSent = $this->timeTrackingService->sendApprovalReminders();
            
            return response()->json([
                'success' => true,
                'message' => "Sent {$remindersSent} approval reminders."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reminders: ' . $e->getMessage()
            ], 500);
        }
    }
}