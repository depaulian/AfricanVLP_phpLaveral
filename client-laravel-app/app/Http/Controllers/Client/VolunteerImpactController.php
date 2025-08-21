<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ImpactMetric;
use App\Models\VolunteerImpactRecord;
use App\Models\BeneficiaryFeedback;
use App\Models\ImpactStory;
use App\Models\Organization;
use App\Models\VolunteerAssignment;
use App\Models\VolunteerApplication;
use App\Services\VolunteerImpactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VolunteerImpactController extends Controller
{
    public function __construct(
        private VolunteerImpactService $impactService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display impact dashboard
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        
        $filters = $request->only(['start_date', 'end_date', 'organization_id', 'metric_category']);
        
        // Set default date range if not provided
        if (!isset($filters['start_date'])) {
            $filters['start_date'] = now()->subYear()->toDateString();
        }
        if (!isset($filters['end_date'])) {
            $filters['end_date'] = now()->toDateString();
        }
        
        $dashboardData = $this->impactService->getUserImpactDashboard($user, $filters);
        
        // Get available organizations for filter
        $organizations = Organization::whereHas('volunteerApplications', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get();
        
        // Get available metric categories
        $metricCategories = ImpactMetric::active()
            ->distinct()
            ->pluck('category')
            ->map(function ($category) {
                return [
                    'value' => $category,
                    'label' => ucfirst($category) . ' Impact'
                ];
            });
        
        return view('client.volunteering.impact.dashboard', compact(
            'dashboardData',
            'filters',
            'organizations',
            'metricCategories'
        ));
    }

    /**
     * Display impact records
     */
    public function records(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->impactRecords()->with(['impactMetric', 'organization', 'assignment']);
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }
        
        if ($request->filled('metric_id')) {
            $query->where('impact_metric_id', $request->metric_id);
        }
        
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }
        
        if ($request->filled('start_date')) {
            $query->where('impact_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('impact_date', '<=', $request->end_date);
        }
        
        $records = $query->orderBy('impact_date', 'desc')->paginate(20);
        
        // Get filter options
        $metrics = ImpactMetric::active()->ordered()->get();
        $organizations = Organization::whereHas('volunteerApplications', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get();
        
        return view('client.volunteering.impact.records', compact(
            'records',
            'metrics',
            'organizations'
        ));
    }

    /**
     * Show form to record new impact
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Get user's assignments for context
        $assignments = VolunteerAssignment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['opportunity', 'organization'])
            ->get();
        
        // Get available metrics
        $metrics = ImpactMetric::active()->ordered()->get();
        
        // Pre-fill assignment if provided
        $selectedAssignment = null;
        if ($request->filled('assignment_id')) {
            $selectedAssignment = $assignments->firstWhere('id', $request->assignment_id);
        }
        
        return view('client.volunteering.impact.create', compact(
            'assignments',
            'metrics',
            'selectedAssignment'
        ));
    }

    /**
     * Store new impact record
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'nullable|exists:volunteer_assignments,id',
            'organization_id' => 'required|exists:organizations,id',
            'impact_metric_id' => 'required|exists:impact_metrics,id',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'impact_date' => 'required|date|before_or_equal:today',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        // Verify user has access to the assignment/organization
        if ($request->filled('assignment_id')) {
            $assignment = VolunteerAssignment::where('id', $request->assignment_id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$assignment) {
                return back()->withErrors(['assignment_id' => 'Invalid assignment selected.'])->withInput();
            }
        }
        
        // Verify organization access
        $hasAccess = VolunteerApplication::where('user_id', $user->id)
            ->where('organization_id', $request->organization_id)
            ->whereIn('status', ['approved', 'active'])
            ->exists();
        
        if (!$hasAccess) {
            return back()->withErrors(['organization_id' => 'You do not have access to this organization.'])->withInput();
        }
        
        try {
            $impactData = [
                'user_id' => $user->id,
                'assignment_id' => $request->assignment_id,
                'organization_id' => $request->organization_id,
                'impact_metric_id' => $request->impact_metric_id,
                'value' => $request->value,
                'description' => $request->description,
                'impact_date' => $request->impact_date,
                'metadata' => [],
            ];
            
            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    // Store file and add to metadata
                    $path = $file->store('impact-attachments', 'public');
                    $attachments[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
                $impactData['metadata']['attachments'] = $attachments;
            }
            
            $impactRecord = $this->impactService->recordImpact($impactData);
            
            return redirect()->route('client.volunteering.impact.records')
                ->with('success', 'Impact record submitted successfully and is pending verification.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to record impact. Please try again.'])->withInput();
        }
    }

    /**
     * Show impact record details
     */
    public function show(VolunteerImpactRecord $record)
    {
        $user = Auth::user();
        
        // Ensure user owns this record
        if ($record->user_id !== $user->id) {
            abort(403);
        }
        
        $record->load(['impactMetric', 'organization', 'assignment.opportunity', 'verifier']);
        
        return view('client.volunteering.impact.show', compact('record'));
    }

    /**
     * Show form to edit impact record
     */
    public function edit(VolunteerImpactRecord $record)
    {
        $user = Auth::user();
        
        // Ensure user owns this record and it's editable
        if ($record->user_id !== $user->id) {
            abort(403);
        }
        
        if (!in_array($record->verification_status, ['pending', 'rejected'])) {
            return redirect()->route('client.volunteering.impact.show', $record)
                ->with('error', 'This impact record cannot be edited.');
        }
        
        $assignments = VolunteerAssignment::where('user_id', $user->id)
            ->with(['opportunity', 'organization'])
            ->get();
        
        $metrics = ImpactMetric::active()->ordered()->get();
        
        return view('client.volunteering.impact.edit', compact(
            'record',
            'assignments',
            'metrics'
        ));
    }

    /**
     * Update impact record
     */
    public function update(Request $request, VolunteerImpactRecord $record)
    {
        $user = Auth::user();
        
        // Ensure user owns this record and it's editable
        if ($record->user_id !== $user->id) {
            abort(403);
        }
        
        if (!in_array($record->verification_status, ['pending', 'rejected'])) {
            return redirect()->route('client.volunteering.impact.show', $record)
                ->with('error', 'This impact record cannot be edited.');
        }
        
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'nullable|exists:volunteer_assignments,id',
            'impact_metric_id' => 'required|exists:impact_metrics,id',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'impact_date' => 'required|date|before_or_equal:today',
            'attachments.*' => 'nullable|file|max:10240',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            $updateData = [
                'assignment_id' => $request->assignment_id,
                'impact_metric_id' => $request->impact_metric_id,
                'value' => $request->value,
                'description' => $request->description,
                'impact_date' => $request->impact_date,
                'verification_status' => 'pending', // Reset to pending after edit
                'verified_by' => null,
                'verified_at' => null,
                'verification_notes' => null,
            ];
            
            // Handle new attachments
            if ($request->hasFile('attachments')) {
                $metadata = $record->metadata ?? [];
                $existingAttachments = $metadata['attachments'] ?? [];
                
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('impact-attachments', 'public');
                    $existingAttachments[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
                
                $metadata['attachments'] = $existingAttachments;
                $updateData['metadata'] = $metadata;
            }
            
            $record->update($updateData);
            
            return redirect()->route('client.volunteering.impact.show', $record)
                ->with('success', 'Impact record updated successfully.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update impact record. Please try again.'])->withInput();
        }
    }

    /**
     * Submit beneficiary feedback
     */
    public function submitFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'volunteer_id' => 'required|exists:users,id',
            'assignment_id' => 'nullable|exists:volunteer_assignments,id',
            'organization_id' => 'required|exists:organizations,id',
            'beneficiary_name' => 'required_unless:is_anonymous,1|string|max:255',
            'beneficiary_email' => 'nullable|email|max:255',
            'beneficiary_phone' => 'nullable|string|max:20',
            'beneficiary_type' => 'required|in:individual,family,group,community,organization',
            'feedback_text' => 'required|string|max:2000',
            'satisfaction_rating' => 'nullable|integer|min:1|max:5',
            'impact_rating' => 'nullable|integer|min:1|max:5',
            'volunteer_rating' => 'nullable|integer|min:1|max:5',
            'improvement_suggestions' => 'nullable|string|max:1000',
            'would_recommend' => 'nullable|boolean',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            $feedback = $this->impactService->recordBeneficiaryFeedback($request->all());
            
            return back()->with('success', 'Thank you for your feedback! It has been submitted for review.');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to submit feedback. Please try again.'])->withInput();
        }
    }

    /**
     * Display impact stories
     */
    public function stories(Request $request)
    {
        $query = ImpactStory::where('is_published', true)
            ->with(['author', 'organization', 'volunteer']);
        
        // Apply filters
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }
        
        if ($request->filled('story_type')) {
            $query->where('story_type', $request->story_type);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        
        $stories = $query->orderBy('published_at', 'desc')->paginate(12);
        
        // Get featured stories
        $featuredStories = ImpactStory::where('is_published', true)
            ->where('is_featured', true)
            ->with(['author', 'organization', 'volunteer'])
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();
        
        // Get filter options
        $organizations = Organization::whereHas('impactStories', function ($q) {
            $q->where('is_published', true);
        })->get();
        
        $storyTypes = [
            'success' => 'Success Stories',
            'challenge' => 'Overcoming Challenges',
            'innovation' => 'Innovation Stories',
            'milestone' => 'Milestone Achievements',
            'testimonial' => 'Testimonials',
        ];
        
        return view('client.volunteering.impact.stories', compact(
            'stories',
            'featuredStories',
            'organizations',
            'storyTypes'
        ));
    }

    /**
     * Show individual impact story
     */
    public function showStory(ImpactStory $story)
    {
        if (!$story->is_published) {
            abort(404);
        }
        
        $story->load(['author', 'organization', 'volunteer']);
        
        // Increment view count
        $story->increment('views_count');
        
        // Get related stories
        $relatedStories = ImpactStory::where('is_published', true)
            ->where('id', '!=', $story->id)
            ->where(function ($q) use ($story) {
                $q->where('organization_id', $story->organization_id)
                  ->orWhere('story_type', $story->story_type);
            })
            ->with(['author', 'organization'])
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();
        
        return view('client.volunteering.impact.story', compact('story', 'relatedStories'));
    }

    /**
     * Generate impact report
     */
    public function report(Request $request)
    {
        $user = Auth::user();
        
        $filters = $request->only(['start_date', 'end_date', 'organization_id', 'metric_category']);
        $filters['user_id'] = $user->id; // Limit to current user
        
        // Set default date range if not provided
        if (!isset($filters['start_date'])) {
            $filters['start_date'] = now()->subYear()->toDateString();
        }
        if (!isset($filters['end_date'])) {
            $filters['end_date'] = now()->toDateString();
        }
        
        $reportData = $this->impactService->generateImpactReport($filters);
        
        return view('client.volunteering.impact.report', compact('reportData', 'filters'));
    }

    /**
     * Export impact data
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        $filters = $request->only(['start_date', 'end_date', 'organization_id', 'metric_category', 'format']);
        $filters['user_id'] = $user->id;
        
        $format = $filters['format'] ?? 'csv';
        
        // Generate filename
        $filename = 'volunteer_impact_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        
        // Get impact records
        $query = $user->impactRecords()->verified()->with(['impactMetric', 'organization']);
        
        if (isset($filters['start_date'])) {
            $query->where('impact_date', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('impact_date', '<=', $filters['end_date']);
        }
        
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        if (isset($filters['metric_category'])) {
            $query->whereHas('impactMetric', function ($q) use ($filters) {
                $q->where('category', $filters['metric_category']);
            });
        }
        
        $records = $query->orderBy('impact_date', 'desc')->get();
        
        if ($format === 'csv') {
            return $this->exportToCsv($records, $filename);
        } elseif ($format === 'pdf') {
            return $this->exportToPdf($records, $filename);
        }
        
        return back()->with('error', 'Invalid export format.');
    }

    /**
     * Export records to CSV
     */
    private function exportToCsv($records, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Date',
                'Organization',
                'Metric',
                'Category',
                'Value',
                'Unit',
                'Description',
                'Status',
                'Verified Date',
            ]);
            
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->impact_date->format('Y-m-d'),
                    $record->organization->name,
                    $record->impactMetric->name,
                    $record->impactMetric->category,
                    $record->value,
                    $record->impactMetric->unit,
                    $record->description,
                    $record->verification_status,
                    $record->verified_at ? $record->verified_at->format('Y-m-d H:i:s') : '',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export records to PDF
     */
    private function exportToPdf($records, $filename)
    {
        // This would require a PDF library like DomPDF or similar
        // For now, return a simple response
        return back()->with('error', 'PDF export is not yet implemented.');
    }
}