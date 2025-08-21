<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityCategory;
use App\Models\OpportunityApplication;
use App\Models\Organization;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OpportunityController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of opportunities for authenticated users.
     */
    public function index(Request $request)
    {
        $query = Opportunity::active()->with(['organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Experience level filter
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        // Location filter
        if ($request->filled('location')) {
            $query->byLocation($request->location);
        }

        // Remote allowed filter
        if ($request->filled('remote_allowed')) {
            $query->remoteAllowed();
        }

        // Featured filter
        if ($request->filled('featured')) {
            $query->featured();
        }

        // Sort
        $sortBy = $request->get('sort', 'application_deadline');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $opportunities = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = OpportunityCategory::active()->ordered()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        // Get featured opportunities for sidebar
        $featuredOpportunities = Opportunity::active()->featured()->take(5)->get();

        // Get urgent opportunities (deadline within 7 days)
        $urgentOpportunities = Opportunity::active()
            ->where('application_deadline', '<=', now()->addDays(7))
            ->orderBy('application_deadline', 'asc')
            ->take(5)
            ->get();

        return view('client.opportunities.index', compact(
            'opportunities', 
            'categories', 
            'organizations', 
            'featuredOpportunities', 
            'urgentOpportunities'
        ));
    }

    /**
     * Display the specified opportunity.
     */
    public function show(Opportunity $opportunity)
    {
        // Check if opportunity is active
        if (!$opportunity->isActive()) {
            abort(404);
        }

        // Increment views
        $opportunity->incrementViews();

        // Load relationships
        $opportunity->load(['organization', 'category']);

        // Check if user has already applied
        $hasApplied = false;
        $userApplication = null;
        if (Auth::check()) {
            $userApplication = OpportunityApplication::where('opportunity_id', $opportunity->id)
                ->where('user_id', Auth::id())
                ->first();
            $hasApplied = !is_null($userApplication);
        }

        // Get related opportunities
        $relatedOpportunities = Opportunity::active()
            ->where('id', '!=', $opportunity->id)
            ->when($opportunity->category_id, function($query) use ($opportunity) {
                $query->where('category_id', $opportunity->category_id);
            })
            ->when($opportunity->organization_id, function($query) use ($opportunity) {
                $query->orWhere('organization_id', $opportunity->organization_id);
            })
            ->take(4)
            ->get();

        return view('client.opportunities.show', compact(
            'opportunity', 
            'relatedOpportunities', 
            'hasApplied', 
            'userApplication'
        ));
    }

    /**
     * Display opportunities by category.
     */
    public function category(OpportunityCategory $category, Request $request)
    {
        if (!$category->isActive()) {
            abort(404);
        }

        $query = Opportunity::active()->byCategory($category->id)->with(['organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Sort
        $sortBy = $request->get('sort', 'application_deadline');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $opportunities = $query->paginate(12)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        // Get other categories
        $categories = OpportunityCategory::active()->where('id', '!=', $category->id)->ordered()->get();

        return view('client.opportunities.category', compact(
            'opportunities', 
            'category', 
            'organizations', 
            'categories'
        ));
    }

    /**
     * Display opportunities by type.
     */
    public function type(Request $request, $type)
    {
        $validTypes = ['volunteer', 'internship', 'job', 'fellowship', 'scholarship', 'grant', 'competition'];
        
        if (!in_array($type, $validTypes)) {
            abort(404);
        }

        $query = Opportunity::active()->byType($type)->with(['organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'application_deadline');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $opportunities = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = OpportunityCategory::active()->ordered()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        return view('client.opportunities.type', compact(
            'opportunities', 
            'type', 
            'categories', 
            'organizations'
        ));
    }

    /**
     * Show application form for an opportunity.
     */
    public function apply(Opportunity $opportunity)
    {
        // Check if opportunity is active and accepting applications
        if (!$opportunity->isActive() || !$opportunity->isAcceptingApplications()) {
            abort(404);
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('message', 'Please login to apply for this opportunity.');
        }

        // Check if user has already applied
        $existingApplication = OpportunityApplication::where('opportunity_id', $opportunity->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingApplication) {
            return redirect()->route('opportunities.show', $opportunity->slug)
                ->with('error', 'You have already applied for this opportunity.');
        }

        $opportunity->load(['organization', 'category']);

        return view('client.opportunities.apply', compact('opportunity'));
    }

    /**
     * Store application for an opportunity.
     */
    public function storeApplication(Request $request, Opportunity $opportunity)
    {
        // Check if opportunity is active and accepting applications
        if (!$opportunity->isActive() || !$opportunity->isAcceptingApplications()) {
            abort(404);
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Check if user has already applied
        $existingApplication = OpportunityApplication::where('opportunity_id', $opportunity->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingApplication) {
            return redirect()->route('opportunities.show', $opportunity->slug)
                ->with('error', 'You have already applied for this opportunity.');
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'cover_letter' => 'sometimes|file|mimes:pdf,doc,docx|max:2048',
            'additional_documents.*' => 'sometimes|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $applicationData = [
                'opportunity_id' => $opportunity->id,
                'user_id' => Auth::id(),
                'message' => $request->input('message'),
                'applied_at' => now()
            ];

            // Handle resume upload
            if ($request->hasFile('resume')) {
                $resumeResult = $this->fileUploadService->uploadFile(
                    $request->file('resume'),
                    'applications/resumes',
                    ['folder' => 'applications/resumes']
                );

                if ($resumeResult['success']) {
                    $applicationData['resume_path'] = $resumeResult['file_path'];
                }
            }

            // Handle cover letter upload
            if ($request->hasFile('cover_letter')) {
                $coverLetterResult = $this->fileUploadService->uploadFile(
                    $request->file('cover_letter'),
                    'applications/cover-letters',
                    ['folder' => 'applications/cover-letters']
                );

                if ($coverLetterResult['success']) {
                    $applicationData['cover_letter_path'] = $coverLetterResult['file_path'];
                }
            }

            // Handle additional documents
            $additionalDocs = [];
            if ($request->hasFile('additional_documents')) {
                foreach ($request->file('additional_documents') as $file) {
                    $docResult = $this->fileUploadService->uploadFile(
                        $file,
                        'applications/documents',
                        ['folder' => 'applications/documents']
                    );

                    if ($docResult['success']) {
                        $additionalDocs[] = [
                            'original_name' => $file->getClientOriginalName(),
                            'file_path' => $docResult['file_path']
                        ];
                    }
                }
            }

            if (!empty($additionalDocs)) {
                $applicationData['additional_documents'] = $additionalDocs;
            }

            // Create application
            OpportunityApplication::create($applicationData);

            // Update opportunity application count
            $opportunity->incrementApplications();

            DB::commit();

            return redirect()->route('opportunities.show', $opportunity->slug)
                ->with('success', 'Your application has been submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opportunity application error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to submit application. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display user's applications.
     */
    public function myApplications(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $query = OpportunityApplication::where('user_id', Auth::id())
            ->with(['opportunity.organization', 'opportunity.category']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('opportunity', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'applied_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $applications = $query->paginate(12)->withQueryString();

        return view('client.opportunities.my-applications', compact('applications'));
    }

    /**
     * Withdraw an application.
     */
    public function withdrawApplication(OpportunityApplication $application)
    {
        if (!Auth::check() || $application->user_id !== Auth::id()) {
            abort(403);
        }

        if ($application->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'You can only withdraw pending applications.');
        }

        try {
            DB::beginTransaction();

            $application->update(['status' => 'withdrawn']);
            $application->opportunity->decrementApplications();

            DB::commit();

            return redirect()->back()
                ->with('success', 'Application withdrawn successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Application withdrawal error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to withdraw application. Please try again.');
        }
    }

    /**
     * Display public opportunities (for non-authenticated users).
     */
    public function publicIndex(Request $request)
    {
        $query = Opportunity::active()->featured()->with(['organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $opportunities = $query->orderBy('application_deadline', 'asc')->paginate(9)->withQueryString();

        // Get categories for filter
        $categories = OpportunityCategory::active()->ordered()->get();

        return view('client.opportunities.public', compact('opportunities', 'categories'));
    }

    /**
     * Display public opportunity details.
     */
    public function publicShow(Opportunity $opportunity)
    {
        // Check if opportunity is active and featured (for public access)
        if (!$opportunity->isActive() || !$opportunity->isFeatured()) {
            abort(404);
        }

        // Increment views
        $opportunity->incrementViews();

        // Load relationships
        $opportunity->load(['organization', 'category']);

        // Get related featured opportunities
        $relatedOpportunities = Opportunity::active()
            ->featured()
            ->where('id', '!=', $opportunity->id)
            ->when($opportunity->category_id, function($query) use ($opportunity) {
                $query->where('category_id', $opportunity->category_id);
            })
            ->take(3)
            ->get();

        return view('client.opportunities.public-show', compact('opportunity', 'relatedOpportunities'));
    }

    /**
     * Search opportunities with advanced filters.
     */
    public function search(Request $request)
    {
        $query = Opportunity::active()->with(['organization', 'category']);

        // Search functionality
        if ($request->filled('q')) {
            $search = $request->q;
            $query->search($search);
        }

        // Advanced filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->filled('location')) {
            $query->byLocation($request->location);
        }

        if ($request->filled('remote_allowed')) {
            $query->remoteAllowed();
        }

        if ($request->filled('deadline_from')) {
            $query->where('application_deadline', '>=', $request->deadline_from);
        }

        if ($request->filled('deadline_to')) {
            $query->where('application_deadline', '<=', $request->deadline_to);
        }

        // Sort
        $sortBy = $request->get('sort', 'application_deadline');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $opportunities = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = OpportunityCategory::active()->ordered()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        return view('client.opportunities.search', compact('opportunities', 'categories', 'organizations'));
    }
}
