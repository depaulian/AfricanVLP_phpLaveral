<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerApplication;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerAssignment;
use App\Services\VolunteeringService;
use App\Services\VolunteerNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VolunteerApplicationController extends Controller
{
    public function __construct(
        private VolunteeringService $volunteeringService,
        private VolunteerNotificationService $notificationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display applications for organization's opportunities
     */
    public function index(Request $request): View
    {
        $status = $request->get('status', 'all');
        $opportunityId = $request->get('opportunity_id');
        $search = $request->get('search');

        // Get organization's opportunities
        $organizationOpportunities = VolunteeringOpportunity::where('organization_id', auth()->user()->organization_id)
            ->pluck('id');

        $query = VolunteerApplication::with([
            'user',
            'opportunity.organization',
            'opportunity.category',
            'reviewer',
            'assignment'
        ])
        ->whereIn('opportunity_id', $organizationOpportunities)
        ->orderBy('applied_at', 'desc');

        // Apply filters
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($opportunityId) {
            $query->where('opportunity_id', $opportunityId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            })
            ->orWhereHas('opportunity', function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%");
            });
        }

        $applications = $query->paginate(15);

        // Get opportunities for filter dropdown
        $opportunities = VolunteeringOpportunity::where('organization_id', auth()->user()->organization_id)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        // Get application statistics
        $statistics = [
            'total' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->count(),
            'pending' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->where('status', 'pending')->count(),
            'accepted' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->where('status', 'accepted')->count(),
            'rejected' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->where('status', 'rejected')->count(),
        ];

        return view('admin.volunteering.applications.index', compact(
            'applications',
            'opportunities',
            'statistics',
            'status',
            'opportunityId',
            'search'
        ));
    }

    /**
     * Show application details
     */
    public function show(VolunteerApplication $application): View
    {
        // Check if application belongs to organization's opportunity
        $this->authorize('view', $application);

        $application->load([
            'user.skills',
            'user.volunteeringInterests.category',
            'user.city',
            'user.country',
            'opportunity.organization',
            'opportunity.category',
            'opportunity.role',
            'reviewer',
            'assignment.supervisor',
            'assignment.timeLogs'
        ]);

        // Get application timeline
        $timeline = $this->volunteeringService->getApplicationTimeline($application);

        // Get user's other applications to this organization
        $otherApplications = VolunteerApplication::with('opportunity')
            ->where('user_id', $application->user_id)
            ->whereHas('opportunity', function ($q) {
                $q->where('organization_id', auth()->user()->organization_id);
            })
            ->where('id', '!=', $application->id)
            ->orderBy('applied_at', 'desc')
            ->limit(5)
            ->get();

        // Get potential supervisors for assignment
        $supervisors = auth()->user()->organization->users()
            ->where('role', 'supervisor')
            ->orWhere('role', 'admin')
            ->get();

        return view('admin.volunteering.applications.show', compact(
            'application',
            'timeline',
            'otherApplications',
            'supervisors'
        ));
    }

    /**
     * Accept volunteer application
     */
    public function accept(Request $request, VolunteerApplication $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'hours_committed' => 'nullable|integer|min:1|max:1000',
            'supervisor_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($application->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending applications can be accepted.');
        }

        try {
            DB::beginTransaction();

            // Accept the application
            $application->update([
                'status' => 'accepted',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewer_notes' => $request->notes
            ]);

            // Create volunteer assignment
            $assignment = VolunteerAssignment::create([
                'application_id' => $application->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'hours_committed' => $request->hours_committed,
                'supervisor_id' => $request->supervisor_id,
                'status' => 'active'
            ]);

            // Update opportunity volunteer count
            $application->opportunity->increment('current_volunteers');

            DB::commit();

            // Send notification to volunteer
            $this->notificationService->notifyVolunteerApplicationAccepted($application);

            return redirect()->route('admin.volunteering.applications.show', $application)
                ->with('success', 'Application accepted successfully! The volunteer has been notified.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to accept application: ' . $e->getMessage());
        }
    }

    /**
     * Reject volunteer application
     */
    public function reject(Request $request, VolunteerApplication $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        if ($application->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending applications can be rejected.');
        }

        try {
            $application->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewer_notes' => $request->rejection_reason
            ]);

            // Send notification to volunteer
            $this->notificationService->notifyVolunteerApplicationRejected($application);

            return redirect()->route('admin.volunteering.applications.show', $application)
                ->with('success', 'Application rejected. The volunteer has been notified.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reject application: ' . $e->getMessage());
        }
    }

    /**
     * Bulk action on applications
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:accept,reject,delete',
            'application_ids' => 'required|array|min:1',
            'application_ids.*' => 'exists:volunteer_applications,id',
            'rejection_reason' => 'required_if:action,reject|string|max:1000',
            'start_date' => 'required_if:action,accept|date|after_or_equal:today',
            'supervisor_id' => 'nullable|exists:users,id'
        ]);

        $applications = VolunteerApplication::whereIn('id', $request->application_ids)
            ->whereHas('opportunity', function ($q) {
                $q->where('organization_id', auth()->user()->organization_id);
            })
            ->get();

        if ($applications->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid applications found.'
            ], 400);
        }

        $successCount = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($applications as $application) {
                if ($application->status !== 'pending') {
                    $errors[] = "Application #{$application->id} is not pending.";
                    continue;
                }

                try {
                    switch ($request->action) {
                        case 'accept':
                            $application->update([
                                'status' => 'accepted',
                                'reviewed_at' => now(),
                                'reviewed_by' => Auth::id()
                            ]);

                            // Create assignment
                            VolunteerAssignment::create([
                                'application_id' => $application->id,
                                'start_date' => $request->start_date,
                                'supervisor_id' => $request->supervisor_id,
                                'status' => 'active'
                            ]);

                            $application->opportunity->increment('current_volunteers');
                            $this->notificationService->notifyVolunteerApplicationAccepted($application);
                            break;

                        case 'reject':
                            $application->update([
                                'status' => 'rejected',
                                'reviewed_at' => now(),
                                'reviewed_by' => Auth::id(),
                                'reviewer_notes' => $request->rejection_reason
                            ]);

                            $this->notificationService->notifyVolunteerApplicationRejected($application);
                            break;

                        case 'delete':
                            $application->delete();
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Failed to process application #{$application->id}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully processed {$successCount} applications.",
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export applications to CSV
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $status = $request->get('status', 'all');
        $opportunityId = $request->get('opportunity_id');

        $organizationOpportunities = VolunteeringOpportunity::where('organization_id', auth()->user()->organization_id)
            ->pluck('id');

        $query = VolunteerApplication::with([
            'user',
            'opportunity',
            'reviewer'
        ])
        ->whereIn('opportunity_id', $organizationOpportunities);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($opportunityId) {
            $query->where('opportunity_id', $opportunityId);
        }

        $applications = $query->orderBy('applied_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="volunteer_applications_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($applications) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'Application ID',
                'Volunteer Name',
                'Email',
                'Phone',
                'Opportunity',
                'Status',
                'Applied Date',
                'Reviewed Date',
                'Reviewer',
                'Motivation',
                'Experience',
                'Availability'
            ]);

            // CSV data
            foreach ($applications as $application) {
                fputcsv($handle, [
                    $application->id,
                    $application->user->full_name,
                    $application->user->email,
                    $application->user->phone_number,
                    $application->opportunity->title,
                    ucfirst($application->status),
                    $application->applied_at->format('Y-m-d H:i:s'),
                    $application->reviewed_at?->format('Y-m-d H:i:s'),
                    $application->reviewer?->full_name,
                    $application->motivation,
                    $application->relevant_experience,
                    is_array($application->availability) ? implode(', ', $application->availability) : $application->availability
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Get application statistics for dashboard
     */
    public function statistics(): JsonResponse
    {
        $organizationOpportunities = VolunteeringOpportunity::where('organization_id', auth()->user()->organization_id)
            ->pluck('id');

        $statistics = [
            'total_applications' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->count(),
            'pending_applications' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->where('status', 'pending')->count(),
            'accepted_applications' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->where('status', 'accepted')->count(),
            'rejected_applications' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)->where('status', 'rejected')->count(),
            'recent_applications' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)
                ->where('applied_at', '>=', now()->subDays(7))
                ->count(),
            'applications_this_month' => VolunteerApplication::whereIn('opportunity_id', $organizationOpportunities)
                ->whereMonth('applied_at', now()->month)
                ->whereYear('applied_at', now()->year)
                ->count(),
        ];

        return response()->json($statistics);
    }
}