<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VolunteeringAnalytic;
use App\Models\ScheduledReport;
use App\Models\Organization;
use App\Services\VolunteeringAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VolunteeringAnalyticsController extends Controller
{
    public function __construct(
        private VolunteeringAnalyticsService $analyticsService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display analytics dashboard
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has access to organization analytics
        $organizationId = $request->get('organization_id');
        if ($organizationId && !$this->canAccessOrganization($user, $organizationId)) {
            abort(403, 'You do not have access to this organization\'s analytics.');
        }
        
        $filters = $request->only(['period', 'metric_category', 'comparison_period']);
        $filters['period'] = $filters['period'] ?? 'last_30_days';
        
        $dashboardData = $this->analyticsService->getDashboardData($organizationId, $filters);
        
        // Get available organizations for filter
        $organizations = $this->getUserOrganizations($user);
        
        // Get available periods
        $periods = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
        ];
        
        // Get metric categories
        $metricCategories = [
            'performance' => 'Performance Metrics',
            'engagement' => 'Engagement Metrics',
            'impact' => 'Impact Metrics',
            'retention' => 'Retention Metrics',
            'satisfaction' => 'Satisfaction Metrics',
            'growth' => 'Growth Metrics',
        ];
        
        return view('client.volunteering.analytics.dashboard', compact(
            'dashboardData',
            'filters',
            'organizations',
            'periods',
            'metricCategories',
            'organizationId'
        ));
    }

    /**
     * Display detailed metrics view
     */
    public function metrics(Request $request)
    {
        $user = Auth::user();
        $organizationId = $request->get('organization_id');
        
        if ($organizationId && !$this->canAccessOrganization($user, $organizationId)) {
            abort(403);
        }
        
        $filters = $request->only(['period', 'metric_type', 'metric_category']);
        $filters['period'] = $filters['period'] ?? 'last_30_days';
        
        $query = VolunteeringAnalytic::query()
            ->with('organization')
            ->when($organizationId, fn($q) => $q->forOrganization($organizationId))
            ->when($filters['metric_type'], fn($q) => $q->metricType($filters['metric_type']))
            ->when($filters['metric_category'], fn($q) => $q->category($filters['metric_category']));
        
        // Apply date range based on period
        $dateRange = $this->parsePeriod($filters['period']);
        $query->dateRange($dateRange[0], $dateRange[1]);
        
        $metrics = $query->orderBy('calculated_at', 'desc')->paginate(20);
        
        // Get available filters
        $organizations = $this->getUserOrganizations($user);
        $metricTypes = VolunteeringAnalytic::distinct()->pluck('metric_type')->sort();
        $metricCategories = VolunteeringAnalytic::distinct()->pluck('metric_category')->sort();
        
        $periods = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
        ];
        
        return view('client.volunteering.analytics.metrics', compact(
            'metrics',
            'filters',
            'organizations',
            'metricTypes',
            'metricCategories',
            'periods',
            'organizationId'
        ));
    }

    /**
     * Display organization comparison view
     */
    public function comparison(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'organization_ids' => 'required|array|min:2|max:5',
            'organization_ids.*' => 'exists:organizations,id',
            'metrics' => 'required|array|min:1',
            'metrics.*' => 'string',
            'period' => 'string|in:last_7_days,last_30_days,this_month,last_month,this_quarter,last_quarter,this_year,last_year',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $organizationIds = $request->get('organization_ids', []);
        $metrics = $request->get('metrics', ['volunteer_count', 'hours_logged', 'impact_score']);
        $period = $request->get('period', 'last_30_days');
        
        // Check access to all organizations
        foreach ($organizationIds as $orgId) {
            if (!$this->canAccessOrganization($user, $orgId)) {
                abort(403, 'You do not have access to one or more selected organizations.');
            }
        }
        
        $dateRange = $this->parsePeriod($period);
        $comparisonData = $this->analyticsService->getOrganizationComparison(
            $organizationIds,
            $metrics,
            $dateRange[0],
            $dateRange[1]
        );
        
        $availableOrganizations = $this->getUserOrganizations($user);
        $availableMetrics = [
            'volunteer_count' => 'Active Volunteers',
            'hours_logged' => 'Hours Logged',
            'impact_score' => 'Impact Score',
            'satisfaction_score' => 'Satisfaction Score',
            'completion_rate' => 'Completion Rate',
            'retention_rate' => 'Retention Rate',
        ];
        
        $periods = [
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
        ];
        
        return view('client.volunteering.analytics.comparison', compact(
            'comparisonData',
            'organizationIds',
            'metrics',
            'period',
            'availableOrganizations',
            'availableMetrics',
            'periods'
        ));
    }

    /**
     * Generate and download report
     */
    public function generateReport(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:volunteer_performance,impact_summary,engagement_metrics,comparative_analysis,custom',
            'organization_id' => 'nullable|exists:organizations,id',
            'format' => 'required|in:pdf,excel,csv,html',
            'config' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $organizationId = $request->get('organization_id');
        if ($organizationId && !$this->canAccessOrganization($user, $organizationId)) {
            abort(403);
        }
        
        $reportType = $request->get('report_type');
        $format = $request->get('format');
        $config = $request->get('config');
        
        try {
            $reportData = $this->analyticsService->generateReportData($reportType, $config, $organizationId);
            
            // Generate report based on format
            switch ($format) {
                case 'pdf':
                    return $this->generatePdfReport($reportData, $reportType);
                case 'excel':
                    return $this->generateExcelReport($reportData, $reportType);
                case 'csv':
                    return $this->generateCsvReport($reportData, $reportType);
                case 'html':
                    return $this->generateHtmlReport($reportData, $reportType);
                default:
                    throw new \InvalidArgumentException('Unsupported format');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate report: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display scheduled reports management
     */
    public function scheduledReports(Request $request)
    {
        $user = Auth::user();
        $organizationId = $request->get('organization_id');
        
        if ($organizationId && !$this->canAccessOrganization($user, $organizationId)) {
            abort(403);
        }
        
        $query = ScheduledReport::with(['organization', 'creator'])
            ->where('created_by', $user->id);
        
        if ($organizationId) {
            $query->forOrganization($organizationId);
        }
        
        $reports = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $organizations = $this->getUserOrganizations($user);
        
        return view('client.volunteering.analytics.scheduled-reports', compact(
            'reports',
            'organizations',
            'organizationId'
        ));
    }

    /**
     * Create scheduled report
     */
    public function createScheduledReport(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'organization_id' => 'nullable|exists:organizations,id',
            'report_type' => 'required|in:volunteer_performance,impact_summary,engagement_metrics,comparative_analysis,custom',
            'report_config' => 'required|array',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'format' => 'required|in:pdf,excel,csv,html',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $organizationId = $request->get('organization_id');
        if ($organizationId && !$this->canAccessOrganization($user, $organizationId)) {
            abort(403);
        }
        
        $report = ScheduledReport::create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'organization_id' => $organizationId,
            'created_by' => $user->id,
            'report_type' => $request->get('report_type'),
            'report_config' => $request->get('report_config'),
            'frequency' => $request->get('frequency'),
            'recipients' => $request->get('recipients'),
            'format' => $request->get('format'),
            'next_generation_at' => now()->addDay(), // Schedule first generation for tomorrow
        ]);
        
        return redirect()->route('client.volunteering.analytics.scheduled-reports')
            ->with('success', 'Scheduled report created successfully.');
    }

    /**
     * Update scheduled report
     */
    public function updateScheduledReport(Request $request, ScheduledReport $report)
    {
        $user = Auth::user();
        
        if ($report->created_by !== $user->id) {
            abort(403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'report_config' => 'required|array',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'format' => 'required|in:pdf,excel,csv,html',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $report->update([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'report_config' => $request->get('report_config'),
            'frequency' => $request->get('frequency'),
            'recipients' => $request->get('recipients'),
            'format' => $request->get('format'),
            'is_active' => $request->get('is_active', true),
        ]);
        
        // Recalculate next generation time if frequency changed
        if ($request->get('frequency') !== $report->getOriginal('frequency')) {
            $report->update([
                'next_generation_at' => $report->calculateNextGeneration(),
            ]);
        }
        
        return redirect()->route('client.volunteering.analytics.scheduled-reports')
            ->with('success', 'Scheduled report updated successfully.');
    }

    /**
     * Delete scheduled report
     */
    public function deleteScheduledReport(ScheduledReport $report)
    {
        $user = Auth::user();
        
        if ($report->created_by !== $user->id) {
            abort(403);
        }
        
        $report->delete();
        
        return redirect()->route('client.volunteering.analytics.scheduled-reports')
            ->with('success', 'Scheduled report deleted successfully.');
    }

    /**
     * Get analytics data via API
     */
    public function apiData(Request $request)
    {
        $user = Auth::user();
        $organizationId = $request->get('organization_id');
        
        if ($organizationId && !$this->canAccessOrganization($user, $organizationId)) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        $filters = $request->only(['period', 'metric_category', 'comparison_period']);
        $filters['period'] = $filters['period'] ?? 'last_30_days';
        
        try {
            $data = $this->analyticsService->getDashboardData($organizationId, $filters);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch analytics data'], 500);
        }
    }

    /**
     * Check if user can access organization analytics
     */
    private function canAccessOrganization($user, $organizationId): bool
    {
        if (!$organizationId) {
            return true; // Global analytics
        }
        
        // Check if user belongs to organization or has admin role
        return $user->organization_id === $organizationId || 
               $user->hasRole('admin') || 
               $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    /**
     * Get organizations user has access to
     */
    private function getUserOrganizations($user): Collection
    {
        if ($user->hasRole('admin')) {
            return Organization::all();
        }
        
        $organizations = collect();
        
        // Add user's primary organization
        if ($user->organization) {
            $organizations->push($user->organization);
        }
        
        // Add organizations user is associated with
        $userOrganizations = $user->organizations()->get();
        $organizations = $organizations->merge($userOrganizations)->unique('id');
        
        return $organizations;
    }

    /**
     * Parse period string into date range
     */
    private function parsePeriod(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'last_quarter' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * Generate PDF report
     */
    private function generatePdfReport(array $data, string $reportType)
    {
        // Implementation would use a PDF library like DomPDF or wkhtmltopdf
        // For now, return a placeholder response
        return response()->json(['message' => 'PDF generation not implemented yet'], 501);
    }

    /**
     * Generate Excel report
     */
    private function generateExcelReport(array $data, string $reportType)
    {
        // Implementation would use Laravel Excel package
        // For now, return a placeholder response
        return response()->json(['message' => 'Excel generation not implemented yet'], 501);
    }

    /**
     * Generate CSV report
     */
    private function generateCsvReport(array $data, string $reportType)
    {
        // Implementation would generate CSV data
        // For now, return a placeholder response
        return response()->json(['message' => 'CSV generation not implemented yet'], 501);
    }

    /**
     * Generate HTML report
     */
    private function generateHtmlReport(array $data, string $reportType)
    {
        // Implementation would generate HTML report
        // For now, return a placeholder response
        return response()->json(['message' => 'HTML generation not implemented yet'], 501);
    }
}