<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\VolunteerApplication;
use App\Models\VolunteerAssignment;
use App\Models\City;
use App\Models\Country;
use App\Services\VolunteeringService;
use App\Services\VolunteerMatchingService;
use App\Services\VolunteerTimeTrackingService;
use App\Http\Requests\VolunteerApplicationRequest;
use App\Http\Requests\VolunteerTimeLogRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VolunteeringController extends Controller
{
    public function __construct(
        private VolunteeringService $volunteeringService,
        private VolunteerMatchingService $matchingService,
        private VolunteerTimeTrackingService $timeTrackingService,
        private \App\Services\VolunteeringCacheService $cacheService,
        private \App\Services\VolunteeringPerformanceService $performanceService
    ) {}

    /**
     * Display opportunity discovery page with performance optimizations
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'search', 'category_id', 'location_type', 'experience_level',
            'city_id', 'country_id', 'skills', 'featured', 'sort_by', 'sort_order'
        ]);

        // Use performance service for optimized listing
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 12);
        
        $opportunitiesData = $this->performanceService->getOptimizedOpportunityList($filters, $page, $perPage);
        $opportunities = $opportunitiesData['data'] ?? [];
        $pagination = $opportunitiesData['pagination'] ?? [];

        // Get cached featured opportunities
        $featuredOpportunities = $this->cacheService->getCachedFeaturedOpportunities();
        if (!$featuredOpportunities) {
            $this->cacheService->cacheFeaturedOpportunities();
            $featuredOpportunities = $this->cacheService->getCachedFeaturedOpportunities();
        }
        
        // Get cached filter options
        $categories = $this->cacheService->getCachedCategoriesWithCounts();
        if (!$categories) {
            $this->cacheService->cacheCategoriesWithCounts();
            $categories = $this->cacheService->getCachedCategoriesWithCounts();
        }

        // Use minimal queries for cities and countries
        $cities = \Illuminate\Support\Facades\Cache::remember('cities_list', 3600, function () {
            return City::select('id', 'name')->orderBy('name')->get();
        });
        
        $countries = \Illuminate\Support\Facades\Cache::remember('countries_list', 3600, function () {
            return Country::select('id', 'name')->orderBy('name')->get();
        });

        // Get recommended opportunities for authenticated users
        $recommendedOpportunities = collect();
        if (Auth::check()) {
            $recommendedOpportunities = $this->matchingService->getRecommendedOpportunities(Auth::user(), 6);
        }

        return view('client.volunteering.index', compact(
            'opportunities',
            'featuredOpportunities',
            'recommendedOpportunities',
            'categories',
            'cities',
            'countries',
            'filters'
        ));
    }

    /**
     * Display opportunity details
     */
    public function show(VolunteeringOpportunity $opportunity): View
    {
        $opportunity->load([
            'organization',
            'category',
            'role',
            'city',
            'country',
            'creator'
        ]);

        // Check if user has already applied
        $hasApplied = false;
        $userApplication = null;
        if (Auth::check()) {
            $userApplication = VolunteerApplication::where('opportunity_id', $opportunity->id)
                ->where('user_id', Auth::id())
                ->first();
            $hasApplied = $userApplication !== null;
        }

        // Get similar opportunities
        $similarOpportunities = VolunteeringOpportunity::with(['organization', 'category', 'city'])
            ->where('category_id', $opportunity->category_id)
            ->where('id', '!=', $opportunity->id)
            ->active()
            ->acceptingApplications()
            ->limit(4)
            ->get();

        // Get opportunity statistics
        $statistics = $this->volunteeringService->getOpportunityStatistics($opportunity);

        // Get match score for authenticated users
        $matchScore = null;
        if (Auth::check()) {
            $matchScore = $this->matchingService->calculateMatchScore(Auth::user(), $opportunity);
        }

        return view('client.volunteering.show', compact(
            'opportunity',
            'hasApplied',
            'userApplication',
            'similarOpportunities',
            'statistics',
            'matchScore'
        ));
    }

    /**
     * Show application form
     */
    public function apply(VolunteeringOpportunity $opportunity): View
    {
        // Check if user has already applied
        $existingApplication = VolunteerApplication::where('opportunity_id', $opportunity->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingApplication) {
            return redirect()->route('client.volunteering.show', $opportunity)
                ->with('error', 'You have already applied for this opportunity.');
        }

        // Check if opportunity is accepting applications
        if (!$opportunity->is_accepting_applications) {
            return redirect()->route('client.volunteering.show', $opportunity)
                ->with('error', 'This opportunity is no longer accepting applications.');
        }

        $opportunity->load(['organization', 'category', 'role']);

        return view('client.volunteering.apply', compact('opportunity'));
    }

    /**
     * Submit volunteer application
     */
    public function submitApplication(VolunteerApplicationRequest $request, VolunteeringOpportunity $opportunity): RedirectResponse
    {
        // Check if user has already applied
        $existingApplication = VolunteerApplication::where('opportunity_id', $opportunity->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingApplication) {
            return redirect()->route('client.volunteering.show', $opportunity)
                ->with('error', 'You have already applied for this opportunity.');
        }

        // Check if opportunity is accepting applications
        if (!$opportunity->is_accepting_applications) {
            return redirect()->route('client.volunteering.show', $opportunity)
                ->with('error', 'This opportunity is no longer accepting applications.');
        }

        try {
            DB::beginTransaction();

            $application = $this->volunteeringService->submitApplication(
                $opportunity,
                Auth::user(),
                $request->validated()
            );

            DB::commit();

            // Send notification to organization
            $this->volunteeringService->notifyOrganizationOfNewApplication($application);

            return redirect()->route('client.volunteering.show', $opportunity)
                ->with('success', 'Your application has been submitted successfully! You will be notified when the organization reviews your application.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to submit application: ' . $e->getMessage());
        }
    }

    /**
     * Withdraw volunteer application
     */
    public function withdrawApplication(VolunteerApplication $application): RedirectResponse
    {
        // Check if user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if application can be withdrawn
        if ($application->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'You can only withdraw pending applications.');
        }

        try {
            $application->update(['status' => 'withdrawn']);

            return redirect()->back()
                ->with('success', 'Your application has been withdrawn successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to withdraw application: ' . $e->getMessage());
        }
    }

    /**
     * Display user's applications
     */
    public function myApplications(Request $request): View
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $query = VolunteerApplication::with([
            'opportunity.organization',
            'opportunity.category',
            'opportunity.city',
            'assignment'
        ])
        ->where('user_id', $user->id)
        ->orderBy('applied_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->paginate(10);

        // Get application statistics
        $statistics = [
            'total' => VolunteerApplication::where('user_id', $user->id)->count(),
            'pending' => VolunteerApplication::where('user_id', $user->id)->where('status', 'pending')->count(),
            'accepted' => VolunteerApplication::where('user_id', $user->id)->where('status', 'accepted')->count(),
            'rejected' => VolunteerApplication::where('user_id', $user->id)->where('status', 'rejected')->count(),
            'withdrawn' => VolunteerApplication::where('user_id', $user->id)->where('status', 'withdrawn')->count(),
        ];

        return view('client.volunteering.applications', compact('applications', 'statistics', 'status'));
    }

    /**
     * Show application details
     */
    public function showApplication(VolunteerApplication $application): View
    {
        // Check if user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $application->load([
            'opportunity.organization',
            'opportunity.category',
            'opportunity.city',
            'opportunity.country',
            'reviewer',
            'assignment.supervisor',
            'assignment.timeLogs'
        ]);

        // Get application timeline
        $timeline = $this->volunteeringService->getApplicationTimeline($application);

        return view('client.volunteering.application-details', compact('application', 'timeline'));
    }

    /**
     * Display volunteer dashboard
     */
    public function dashboard(): View
    {
        $user = Auth::user();

        // Get user's applications
        $applications = $this->volunteeringService->getUserApplications($user);
        $pendingApplications = $applications->where('status', 'pending');
        $acceptedApplications = $applications->where('status', 'accepted');

        // Get user's assignments
        $assignments = $this->volunteeringService->getUserAssignments($user);
        $activeAssignments = $assignments->where('status', 'active');
        $completedAssignments = $assignments->where('status', 'completed');

        // Get volunteer hours summary
        $hoursSummary = $this->timeTrackingService->getVolunteerHoursSummary($user);

        // Get recommended opportunities
        $recommendedOpportunities = $this->matchingService->getRecommendedOpportunities($user, 4);

        return view('client.volunteering.dashboard', compact(
            'applications',
            'pendingApplications',
            'acceptedApplications',
            'assignments',
            'activeAssignments',
            'completedAssignments',
            'hoursSummary',
            'recommendedOpportunities'
        ));
    }

    /**
     * Display assignment details
     */
    public function assignment(VolunteerAssignment $assignment): View
    {
        // Check if user owns this assignment
        if ($assignment->application->user_id !== Auth::id()) {
            abort(403);
        }

        $assignment->load([
            'application.opportunity.organization',
            'application.opportunity.category',
            'supervisor',
            'timeLogs'
        ]);

        // Get time logs for this assignment
        $timeLogs = $this->timeTrackingService->getTimeLogsForAssignment($assignment);
        $pendingTimeLogs = $timeLogs->where('supervisor_approved', false);
        $approvedTimeLogs = $timeLogs->where('supervisor_approved', true);

        // Generate time report
        $timeReport = $this->timeTrackingService->generateTimeReport($assignment);

        return view('client.volunteering.assignment', compact(
            'assignment',
            'timeLogs',
            'pendingTimeLogs',
            'approvedTimeLogs',
            'timeReport'
        ));
    }

    /**
     * Show hour logging form
     */
    public function logHours(VolunteerAssignment $assignment): View
    {
        // Check if user owns this assignment
        if ($assignment->application->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if assignment is active
        if (!$assignment->isActive()) {
            return redirect()->route('client.volunteering.assignment', $assignment)
                ->with('error', 'Cannot log hours for inactive assignment.');
        }

        $assignment->load('application.opportunity');

        return view('client.volunteering.log-hours', compact('assignment'));
    }

    /**
     * Submit logged hours
     */
    public function submitHours(Request $request, VolunteerAssignment $assignment): RedirectResponse
    {
        // Check if user owns this assignment
        if ($assignment->application->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'activity_description' => 'required|string|min:10|max:500',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $timeLog = $this->timeTrackingService->logTime(
                $assignment,
                $request->only([
                    'date',
                    'start_time',
                    'end_time',
                    'activity_description',
                    'notes'
                ])
            );

            return redirect()->route('client.volunteering.assignment', $assignment)
                ->with('success', 'Hours logged successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Search opportunities via AJAX
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $filters = $request->only(['category_id', 'location_type']);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $opportunities = $this->volunteeringService->searchOpportunities($query, $filters, 10);

        return response()->json([
            'opportunities' => $opportunities->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'title' => $opportunity->title,
                    'organization' => $opportunity->organization->name,
                    'category' => $opportunity->category->name,
                    'location' => $opportunity->location_display,
                    'url' => route('client.volunteering.show', $opportunity)
                ];
            })
        ]);
    }

    /**
     * Get opportunities by category via AJAX
     */
    public function byCategory(VolunteeringCategory $category): JsonResponse
    {
        $opportunities = VolunteeringOpportunity::with(['organization', 'city', 'country'])
            ->where('category_id', $category->id)
            ->active()
            ->acceptingApplications()
            ->limit(12)
            ->get();

        return response()->json([
            'category' => $category->name,
            'opportunities' => $opportunities->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'title' => $opportunity->title,
                    'organization' => $opportunity->organization->name,
                    'location' => $opportunity->location_display,
                    'description' => \Str::limit($opportunity->description, 150),
                    'url' => route('client.volunteering.show', $opportunity),
                    'image' => $opportunity->image_url
                ];
            })
        ]);
    }

    /**
     * Get user's match explanation for an opportunity
     */
    public function matchExplanation(VolunteeringOpportunity $opportunity): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $explanation = $this->matchingService->getMatchExplanation(Auth::user(), $opportunity);

        return response()->json($explanation);
    }

    /**
     * Download volunteer certificate
     */
    public function downloadCertificate(VolunteerAssignment $assignment)
    {
        // Check if user owns this assignment
        if ($assignment->application->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if assignment is completed and certificate is issued
        if (!$assignment->isCompleted() || !$assignment->certificate_issued) {
            return redirect()->back()
                ->with('error', 'Certificate not available for this assignment.');
        }

        // Generate and download certificate
        // This would typically use a PDF generation service
        return response()->download(
            storage_path("certificates/volunteer_{$assignment->id}.pdf"),
            "volunteer_certificate_{$assignment->id}.pdf"
        );
    }

    /**
     * Complete assignment
     */
    public function completeAssignment(Request $request, VolunteerAssignment $assignment): RedirectResponse
    {
        // Check if user owns this assignment
        if ($assignment->application->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'completion_notes' => 'nullable|string|max:1000',
            'request_certificate' => 'boolean'
        ]);

        try {
            $this->volunteeringService->completeAssignment($assignment, [
                'completion_notes' => $request->completion_notes,
                'issue_certificate' => $request->boolean('request_certificate')
            ]);

            return redirect()->route('client.volunteering.dashboard')
                ->with('success', 'Assignment completed successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}