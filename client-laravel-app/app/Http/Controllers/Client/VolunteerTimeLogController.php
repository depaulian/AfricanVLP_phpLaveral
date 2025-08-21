<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\VolunteerTimeLogRequest;
use App\Models\VolunteerAssignment;
use App\Models\VolunteerTimeLog;
use App\Services\VolunteerTimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VolunteerTimeLogController extends Controller
{
    public function __construct(
        private VolunteerTimeTrackingService $timeTrackingService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display volunteer's time logs
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get user's assignments
        $allAssignments = VolunteerAssignment::whereHas('application', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['opportunity.organization'])->get();
        
        $activeAssignments = $allAssignments->where('status', 'active');
        
        // Build time logs query
        $timeLogsQuery = VolunteerTimeLog::whereHas('assignment.application', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['assignment.opportunity.organization']);
        
        // Apply filters
        if ($request->filled('assignment')) {
            $timeLogsQuery->where('assignment_id', $request->assignment);
        }
        
        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $timeLogsQuery->where('supervisor_approved', true);
            } elseif ($request->status === 'pending') {
                $timeLogsQuery->where('supervisor_approved', false);
            }
        }
        
        $timeLogs = $timeLogsQuery->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(20);
        
        // Calculate statistics
        $totalApprovedHours = VolunteerTimeLog::whereHas('assignment.application', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('supervisor_approved', true)->sum('hours');
        
        $pendingHours = VolunteerTimeLog::whereHas('assignment.application', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('supervisor_approved', false)->sum('hours');
        
        return view('client.volunteering.time-logs.index', compact(
            'timeLogs',
            'activeAssignments',
            'allAssignments',
            'totalApprovedHours',
            'pendingHours'
        ));
    }

    /**
     * Store a new time log entry
     */
    public function store(VolunteerAssignment $assignment, VolunteerTimeLogRequest $request)
    {
        // Ensure user owns this assignment
        if ($assignment->application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to assignment.');
        }

        // Ensure assignment is active
        if ($assignment->status !== 'active') {
            return back()->with('error', 'Cannot log hours for inactive assignments.');
        }

        try {
            $timeLog = $this->timeTrackingService->logHours($assignment, $request->validated());
            
            return back()->with('success', 'Hours logged successfully! Waiting for supervisor approval.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to log hours: ' . $e->getMessage());
        }
    }

    /**
     * Show form for editing time log
     */
    public function edit(VolunteerTimeLog $timeLog)
    {
        // Ensure user owns this time log
        if ($timeLog->assignment->application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to time log.');
        }

        // Cannot edit approved time logs
        if ($timeLog->supervisor_approved) {
            return redirect()->route('client.volunteering.time-logs.index')
                ->with('error', 'Cannot edit approved time logs.');
        }

        return view('client.volunteering.time-logs.edit', compact('timeLog'));
    }

    /**
     * Update time log entry
     */
    public function update(VolunteerTimeLog $timeLog, VolunteerTimeLogRequest $request)
    {
        // Ensure user owns this time log
        if ($timeLog->assignment->application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to time log.');
        }

        // Cannot edit approved time logs
        if ($timeLog->supervisor_approved) {
            return back()->with('error', 'Cannot edit approved time logs.');
        }

        try {
            $this->timeTrackingService->updateTimeLog($timeLog, $request->validated());
            
            return redirect()->route('client.volunteering.time-logs.index')
                ->with('success', 'Time log updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update time log: ' . $e->getMessage());
        }
    }

    /**
     * Delete time log entry
     */
    public function destroy(VolunteerTimeLog $timeLog)
    {
        // Ensure user owns this time log
        if ($timeLog->assignment->application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to time log.');
        }

        // Cannot delete approved time logs
        if ($timeLog->supervisor_approved) {
            return response()->json(['error' => 'Cannot delete approved time logs.'], 403);
        }

        try {
            $timeLog->delete();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete time log.'], 500);
        }
    }

    /**
     * Export time logs to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Build query
        $timeLogsQuery = VolunteerTimeLog::whereHas('assignment.application', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['assignment.opportunity.organization']);
        
        // Apply filters
        if ($request->filled('assignment')) {
            $timeLogsQuery->where('assignment_id', $request->assignment);
        }
        
        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $timeLogsQuery->where('supervisor_approved', true);
            } elseif ($request->status === 'pending') {
                $timeLogsQuery->where('supervisor_approved', false);
            }
        }
        
        if ($request->filled('date_from')) {
            $timeLogsQuery->where('date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $timeLogsQuery->where('date', '<=', $request->date_to);
        }
        
        $timeLogs = $timeLogsQuery->orderBy('date', 'desc')->get();
        
        return $this->timeTrackingService->exportTimeLogsToCSV($timeLogs, $user);
    }

    /**
     * Show time log details
     */
    public function show(VolunteerTimeLog $timeLog)
    {
        // Ensure user owns this time log
        if ($timeLog->assignment->application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to time log.');
        }

        $timeLog->load([
            'assignment.opportunity.organization',
            'assignment.supervisor',
            'approver'
        ]);

        return view('client.volunteering.time-logs.show', compact('timeLog'));
    }

    /**
     * Get assignment time logs for AJAX requests
     */
    public function getAssignmentLogs(VolunteerAssignment $assignment)
    {
        // Ensure user owns this assignment
        if ($assignment->application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to assignment.');
        }

        $timeLogs = $assignment->timeLogs()
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'timeLogs' => $timeLogs,
            'totalHours' => $timeLogs->sum('hours'),
            'approvedHours' => $timeLogs->where('supervisor_approved', true)->sum('hours'),
            'pendingHours' => $timeLogs->where('supervisor_approved', false)->sum('hours')
        ]);
    }

    /**
     * Bulk operations on time logs
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,export',
            'time_log_ids' => 'required|array',
            'time_log_ids.*' => 'exists:volunteer_time_logs,id'
        ]);

        $user = Auth::user();
        
        // Get time logs that belong to the user
        $timeLogs = VolunteerTimeLog::whereIn('id', $request->time_log_ids)
            ->whereHas('assignment.application', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        if ($timeLogs->isEmpty()) {
            return back()->with('error', 'No valid time logs selected.');
        }

        try {
            switch ($request->action) {
                case 'delete':
                    // Only delete non-approved logs
                    $deletableCount = $timeLogs->where('supervisor_approved', false)->count();
                    
                    if ($deletableCount === 0) {
                        return back()->with('error', 'Cannot delete approved time logs.');
                    }
                    
                    VolunteerTimeLog::whereIn('id', $timeLogs->where('supervisor_approved', false)->pluck('id'))
                        ->delete();
                    
                    return back()->with('success', "Deleted {$deletableCount} time log entries.");

                case 'export':
                    return $this->timeTrackingService->exportTimeLogsToCSV($timeLogs, $user);

                default:
                    return back()->with('error', 'Invalid action.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to perform bulk action: ' . $e->getMessage());
        }
    }

    /**
     * Get time tracking analytics for user
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();
        
        $analytics = $this->timeTrackingService->getUserAnalytics($user, [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'assignment_id' => $request->assignment_id
        ]);

        if ($request->wantsJson()) {
            return response()->json($analytics);
        }

        return view('client.volunteering.time-logs.analytics', compact('analytics'));
    }

    /**
     * Generate time tracking report
     */
    public function report(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'format' => 'required|in:pdf,csv',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'assignment_ids' => 'nullable|array',
            'assignment_ids.*' => 'exists:volunteer_assignments,id'
        ]);

        try {
            $report = $this->timeTrackingService->generateUserReport($user, [
                'format' => $request->format,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'assignment_ids' => $request->assignment_ids
            ]);

            return $report;
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }
}