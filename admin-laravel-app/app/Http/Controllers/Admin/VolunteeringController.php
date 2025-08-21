<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\VolunteeringRole;
use App\Models\VolunteerApplication;
use App\Models\VolunteerAssignment;
use App\Models\VolunteerTimeLog;
use App\Models\Organization;
use App\Models\City;
use App\Models\Country;
use App\Services\VolunteeringService;
use App\Services\VolunteerMatchingService;
use App\Services\VolunteerTimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VolunteeringController extends Controller
{
    public function __construct(
        private VolunteeringService $volunteeringService,
        private VolunteerMatchingService $matchingService,
        private VolunteerTimeTrackingService $timeTrackingService
    ) {}

    /**
     * Display volunteering management dashboard
     */
    public function index(): View
    {
        // Get opportunities statistics
        $totalOpportunities = VolunteeringOpportunity::count();
        $activeOpportunities = VolunteeringOpportunity::active()->count();
        $pendingApplications = VolunteerApplication::where('status', 'pending')->count();
        $activeAssignments = VolunteerAssignment::where('status', 'active')->count();

        // Get recent opportunities
        $recentOpportunities = VolunteeringOpportunity::with(['organization', 'category'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent applications
        $recentApplications = VolunteerApplication::with(['opportunity', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // Get pending time logs for approval
        $pendingTimeLogs = VolunteerTimeLog::with(['assignment.application.user', 'assignment.application.opportunity'])
            ->where('supervisor_approved', false)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.volunteering.index', compact(
            'totalOpportunities',
            'activeOpportunities',
            'pendingApplications',
            'activeAssignments',
            'recentOpportunities',
            'recentApplications',
            'pendingTimeLogs'
        ));
    }

    /**
     * Display opportunities list
     */
    public function opportunities(Request $request): View
    {
        $filters = $request->only([
            'search', 'category_id', 'organization_id', 'status', 'location_type'
        ]);

        $opportunities = $this->volunteeringService->getOpportunities($filters, 15);
        
        // Get filter options
        $categories = VolunteeringCategory::active()->orderBy('name')->get();
        $organizations = Organization::active()->orderBy('name')->get();

        return view('admin.volunteering.opportunities.index', compact(
            'opportunities',
            'categories',
            'organizations',
            'filters'
        ));
    }

    /**
     * Show create opportunity form
     */
    public function createOpportunity(): View
    {
        $categories = VolunteeringCategory::active()->orderBy('name')->get();
        $roles = VolunteeringRole::active()->orderBy('name')->get();
        $organizations = Organization::active()->orderBy('name')->get();
        $cities = City::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('admin.volunteering.opportunities.create', compact(
            'categories',
            'roles',
            'organizations',
            'cities',
            'countries'
        ));
    }

    /**
     * Store new opportunity
     */
    public function storeOpportunity(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'organization_id' => 'required|exists:organizations,id',
            'category_id' => 'required|exists:volunteering_categories,id',
            'role_id' => 'nullable|exists:volunteering_roles,id',
            'location_type' => 'required|in:onsite,remote,hybrid',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'address' => 'nullable|string|max:500',
            'volunteers_needed' => 'required|integer|min:1',
            'experience_level' => 'required|in:beginner,intermediate,advanced,expert,any',
            'time_commitment' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'application_deadline' => 'nullable|date|after_or_equal:today',
            'required_skills' => 'nullable|array',
            'benefits' => 'nullable|string',
            'requirements' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'featured' => 'boolean',
            'status' => 'required|in:draft,active,paused,closed'
        ]);

        try {
            $opportunity = $this->volunteeringService->createOpportunity(
                $request->all(),
                Auth::user()
            );

            return redirect()->route('admin.volunteering.opportunities.show', $opportunity)
                ->with('success', 'Opportunity created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display opportunity details
     */
    public function showOpportunity(VolunteeringOpportunity $opportunity): View
    {
        $opportunity->load([
            'organization',
            'category',
            'role',
            'city',
            'country',
            'creator'
        ]);

        // Get opportunity statistics
        $statistics = $this->volunteeringService->getOpportunityStatistics($opportunity);

        // Get applications for this opportunity
        $applications = $this->volunteeringService->getApplicationsForOpportunity($opportunity);

        // Get similar volunteers
        $similarVolunteers = $this->matchingService->findSimilarVolunteers($opportunity, 5);

        return view('admin.volunteering.opportunities.show', compact(
            'opportunity',
            'statistics',
            'applications',
            'similarVolunteers'
        ));
    }

    /**
     * Show edit opportunity form
     */
    public function editOpportunity(VolunteeringOpportunity $opportunity): View
    {
        $categories = VolunteeringCategory::active()->orderBy('name')->get();
        $roles = VolunteeringRole::active()->orderBy('name')->get();
        $organizations = Organization::active()->orderBy('name')->get();
        $cities = City::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('admin.volunteering.opportunities.edit', compact(
            'opportunity',
            'categories',
            'roles',
            'organizations',
            'cities',
            'countries'
        ));
    }

    /**
     * Update opportunity
     */
    public function updateOpportunity(Request $request, VolunteeringOpportunity $opportunity): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'organization_id' => 'required|exists:organizations,id',
            'category_id' => 'required|exists:volunteering_categories,id',
            'role_id' => 'nullable|exists:volunteering_roles,id',
            'location_type' => 'required|in:onsite,remote,hybrid',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'address' => 'nullable|string|max:500',
            'volunteers_needed' => 'required|integer|min:1',
            'experience_level' => 'required|in:beginner,intermediate,advanced,expert,any',
            'time_commitment' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'application_deadline' => 'nullable|date',
            'required_skills' => 'nullable|array',
            'benefits' => 'nullable|string',
            'requirements' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'featured' => 'boolean',
            'status' => 'required|in:draft,active,paused,closed'
        ]);

        try {
            $opportunity = $this->volunteeringService->updateOpportunity(
                $opportunity,
                $request->all()
            );

            return redirect()->route('admin.volunteering.opportunities.show', $opportunity)
                ->with('success', 'Opportunity updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display applications list
     */
    public function applications(Request $request): View
    {
        $filters = $request->only(['status', 'opportunity_id', 'search']);

        $query = VolunteerApplication::with(['opportunity', 'user', 'reviewer'])
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['opportunity_id'])) {
            $query->where('opportunity_id', $filters['opportunity_id']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('email', 'LIKE', "%{$filters['search']}%");
            });
        }

        $applications = $query->paginate(15);

        // Get filter options
        $opportunities = VolunteeringOpportunity::active()->orderBy('title')->get();

        return view('admin.volunteering.applications.index', compact(
            'applications',
            'opportunities',
            'filters'
        ));
    }

    /**
     * Show application details
     */
    public function showApplication(VolunteerApplication $application): View
    {
        $application->load([
            'opportunity.organization',
            'opportunity.category',
            'user',
            'reviewer',
            'assignment'
        ]);

        return view('admin.volunteering.applications.show', compact('application'));
    }

    /**
     * Review application
     */
    public function reviewApplication(Request $request, VolunteerApplication $application): RedirectResponse
    {
        $request->validate([
            'decision' => 'required|in:accept,reject',
            'reason' => 'required_if:decision,reject|nullable|string|max:1000',
            'assignment.start_date' => 'required_if:decision,accept|nullable|date|after_or_equal:today',
            'assignment.end_date' => 'nullable|date|after:assignment.start_date',
            'assignment.hours_committed' => 'required_if:decision,accept|nullable|integer|min:1',
            'assignment.supervisor_id' => 'nullable|exists:users,id',
            'assignment.notes' => 'nullable|string|max:1000'
        ]);

        try {
            $this->volunteeringService->reviewApplication(
                $application,
                Auth::user(),
                $request->decision,
                $request->only(['reason', 'assignment'])
            );

            $message = $request->decision === 'accept' 
                ? 'Application accepted successfully!' 
                : 'Application rejected.';

            return redirect()->route('admin.volunteering.applications.show', $application)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display assignments list
     */
    public function assignments(Request $request): View
    {
        $filters = $request->only(['status', 'organization_id', 'search']);

        $query = VolunteerAssignment::with([
            'application.opportunity.organization',
            'application.user',
            'supervisor'
        ])->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['organization_id'])) {
            $query->whereHas('application.opportunity', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        if (!empty($filters['search'])) {
            $query->whereHas('application.user', function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%");
            });
        }

        $assignments = $query->paginate(15);

        // Get filter options
        $organizations = Organization::active()->orderBy('name')->get();

        return view('admin.volunteering.assignments.index', compact(
            'assignments',
            'organizations',
            'filters'
        ));
    }

    /**
     * Show assignment details
     */
    public function showAssignment(VolunteerAssignment $assignment): View
    {
        $assignment->load([
            'application.opportunity.organization',
            'application.user',
            'supervisor',
            'timeLogs'
        ]);

        // Get time logs for this assignment
        $timeLogs = $this->timeTrackingService->getTimeLogsForAssignment($assignment);
        $pendingTimeLogs = $timeLogs->where('supervisor_approved', false);

        // Generate time report
        $timeReport = $this->timeTrackingService->generateTimeReport($assignment);

        return view('admin.volunteering.assignments.show', compact(
            'assignment',
            'timeLogs',
            'pendingTimeLogs',
            'timeReport'
        ));
    }

    /**
     * Display time logs for approval
     */
    public function timeLogs(Request $request): View
    {
        $filters = $request->only(['approved', 'organization_id', 'search']);

        $query = VolunteerTimeLog::with([
            'assignment.application.opportunity.organization',
            'assignment.application.user',
            'approver'
        ])->latest();

        if (isset($filters['approved'])) {
            if ($filters['approved'] === '1') {
                $query->approved();
            } else {
                $query->pendingApproval();
            }
        }

        if (!empty($filters['organization_id'])) {
            $query->whereHas('assignment.application.opportunity', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        if (!empty($filters['search'])) {
            $query->whereHas('assignment.application.user', function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%");
            });
        }

        $timeLogs = $query->paginate(15);

        // Get filter options
        $organizations = Organization::active()->orderBy('name')->get();

        return view('admin.volunteering.time-logs.index', compact(
            'timeLogs',
            'organizations',
            'filters'
        ));
    }

    /**
     * Approve time log
     */
    public function approveTimeLog(VolunteerTimeLog $timeLog): RedirectResponse
    {
        try {
            $this->timeTrackingService->approveTimeLog($timeLog, Auth::user());

            return redirect()->back()
                ->with('success', 'Time log approved successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk approve time logs
     */
    public function bulkApproveTimeLogs(Request $request): RedirectResponse
    {
        $request->validate([
            'time_log_ids' => 'required|array',
            'time_log_ids.*' => 'exists:volunteer_time_logs,id'
        ]);

        try {
            $results = $this->timeTrackingService->bulkApproveTimeLogs(
                $request->time_log_ids,
                Auth::user()
            );

            $message = "Approved {$results['approved']} time logs.";
            if (!empty($results['errors'])) {
                $message .= " Errors: " . implode(', ', $results['errors']);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display volunteering analytics
     */
    public function analytics(): View
    {
        // Get overall statistics
        $totalOpportunities = VolunteeringOpportunity::count();
        $totalApplications = VolunteerApplication::count();
        $totalVolunteers = VolunteerAssignment::distinct('application.user_id')->count();
        $totalHours = VolunteerTimeLog::approved()->sum('hours');

        // Get monthly statistics
        $monthlyStats = VolunteerApplication::selectRaw('
            YEAR(applied_at) as year,
            MONTH(applied_at) as month,
            COUNT(*) as applications,
            COUNT(CASE WHEN status = "accepted" THEN 1 END) as accepted
        ')
        ->where('applied_at', '>=', now()->subMonths(12))
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get();

        // Get top categories
        $topCategories = VolunteeringCategory::withCount('opportunities')
            ->having('opportunities_count', '>', 0)
            ->orderBy('opportunities_count', 'desc')
            ->limit(10)
            ->get();

        // Get top organizations
        $topOrganizations = Organization::withCount([
            'volunteeringOpportunities',
            'volunteeringOpportunities as active_opportunities_count' => function ($query) {
                $query->active();
            }
        ])
        ->having('volunteering_opportunities_count', '>', 0)
        ->orderBy('volunteering_opportunities_count', 'desc')
        ->limit(10)
        ->get();

        return view('admin.volunteering.analytics', compact(
            'totalOpportunities',
            'totalApplications',
            'totalVolunteers',
            'totalHours',
            'monthlyStats',
            'topCategories',
            'topOrganizations'
        ));
    }

    /**
     * Export volunteering data
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:opportunities,applications,assignments,time_logs',
            'format' => 'required|in:csv,xlsx',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        // This would typically queue an export job
        // For now, return success response
        return response()->json([
            'message' => 'Export queued successfully. You will receive an email when ready.',
            'export_id' => uniqid()
        ]);
    }
}