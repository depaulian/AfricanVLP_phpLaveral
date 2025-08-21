<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityCategory;
use App\Models\Organization;
use App\Models\OpportunityApplication;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OpportunityController extends Controller
{
    /**
     * Display a listing of opportunities
     */
    public function index(Request $request): JsonResponse
    {
        $query = Opportunity::with(['organization', 'category']);

        // Apply filters
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->input('experience_level'));
        }

        if ($request->filled('remote_allowed')) {
            $query->where('remote_allowed', $request->boolean('remote_allowed'));
        }

        if ($request->filled('location')) {
            $query->byLocation($request->input('location'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->search($search);
        }

        if ($request->filled('deadline_from')) {
            $query->where('application_deadline', '>=', $request->input('deadline_from'));
        }

        if ($request->filled('deadline_to')) {
            $query->where('application_deadline', '<=', $request->input('deadline_to'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $opportunities = $query->paginate($perPage)->withQueryString();

        // Get filter options
        $organizations = Organization::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $categories = OpportunityCategory::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'opportunities' => $opportunities,
                'filters' => [
                    'organizations' => $organizations,
                    'categories' => $categories
                ]
            ]
        ]);
    }

    /**
     * Store a newly created opportunity
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'sometimes|string',
            'responsibilities' => 'sometimes|string',
            'benefits' => 'sometimes|string',
            'organization_id' => 'required|exists:organizations,id',
            'category_id' => 'sometimes|exists:opportunity_categories,id',
            'type' => 'required|in:volunteer,internship,job,fellowship,scholarship,grant,competition',
            'location' => 'sometimes|string|max:255',
            'remote_allowed' => 'sometimes|boolean',
            'duration' => 'sometimes|string|max:255',
            'time_commitment' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'application_deadline' => 'required|date|after:now',
            'status' => 'sometimes|in:draft,active,paused,closed,archived',
            'featured' => 'sometimes|boolean',
            'contact_email' => 'sometimes|email',
            'contact_phone' => 'sometimes|string|max:20',
            'external_url' => 'sometimes|url',
            'skills_required' => 'sometimes|array',
            'experience_level' => 'sometimes|in:entry,intermediate,senior,executive',
            'language_requirements' => 'sometimes|array',
            'age_requirements' => 'sometimes|string|max:255',
            'education_requirements' => 'sometimes|string|max:255',
            'max_applicants' => 'sometimes|integer|min:1',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            
            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            $opportunity = Opportunity::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Opportunity created successfully',
                'data' => $opportunity->load(['organization', 'category'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opportunity creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Opportunity creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified opportunity
     */
    public function show(Opportunity $opportunity): JsonResponse
    {
        $opportunity->load(['organization', 'category', 'applications.user']);

        return response()->json([
            'success' => true,
            'data' => $opportunity
        ]);
    }

    /**
     * Update the specified opportunity
     */
    public function update(Request $request, Opportunity $opportunity): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'requirements' => 'sometimes|string',
            'responsibilities' => 'sometimes|string',
            'benefits' => 'sometimes|string',
            'organization_id' => 'sometimes|exists:organizations,id',
            'category_id' => 'sometimes|exists:opportunity_categories,id',
            'type' => 'sometimes|in:volunteer,internship,job,fellowship,scholarship,grant,competition',
            'location' => 'sometimes|string|max:255',
            'remote_allowed' => 'sometimes|boolean',
            'duration' => 'sometimes|string|max:255',
            'time_commitment' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'application_deadline' => 'sometimes|date',
            'status' => 'sometimes|in:draft,active,paused,closed,archived',
            'featured' => 'sometimes|boolean',
            'contact_email' => 'sometimes|email',
            'contact_phone' => 'sometimes|string|max:20',
            'external_url' => 'sometimes|url',
            'skills_required' => 'sometimes|array',
            'experience_level' => 'sometimes|in:entry,intermediate,senior,executive',
            'language_requirements' => 'sometimes|array',
            'age_requirements' => 'sometimes|string|max:255',
            'education_requirements' => 'sometimes|string|max:255',
            'max_applicants' => 'sometimes|integer|min:1',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $opportunity->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Opportunity updated successfully',
                'data' => $opportunity->load(['organization', 'category'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opportunity update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Opportunity update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified opportunity
     */
    public function destroy(Opportunity $opportunity): JsonResponse
    {
        try {
            DB::beginTransaction();

            $opportunity->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Opportunity deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opportunity deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Opportunity deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations on opportunities
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,feature,unfeature,pause,close,archive,delete',
            'opportunity_ids' => 'required|array|min:1',
            'opportunity_ids.*' => 'exists:opportunities,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $opportunityIds = $request->input('opportunity_ids');
            $action = $request->input('action');
            $affected = 0;

            switch ($action) {
                case 'activate':
                    $affected = Opportunity::whereIn('id', $opportunityIds)
                        ->update(['status' => 'active']);
                    break;

                case 'deactivate':
                    $affected = Opportunity::whereIn('id', $opportunityIds)
                        ->update(['status' => 'draft']);
                    break;

                case 'feature':
                    $affected = Opportunity::whereIn('id', $opportunityIds)
                        ->update(['featured' => true]);
                    break;

                case 'unfeature':
                    $affected = Opportunity::whereIn('id', $opportunityIds)
                        ->update(['featured' => false]);
                    break;

                case 'pause':
                    $affected = Opportunity::whereIn('id', $opportunityIds)
                        ->update(['status' => 'paused']);
                    break;

                case 'close':
                    $affected = Opportunity::whereIn('id', $opportunityIds)
                        ->update(['status' => 'closed']);
                    break;

                case 'archive':
                    $affected = Opportunity::whereIn('id', $opportunityIds)
                        ->update(['status' => 'archived']);
                    break;

                case 'delete':
                    $affected = Opportunity::whereIn('id', $opportunityIds)->delete();
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk {$action} completed successfully",
                'affected_count' => $affected
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bulk {$action} error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => "Bulk {$action} failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get opportunity statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_opportunities' => Opportunity::count(),
            'active_opportunities' => Opportunity::where('status', 'active')->count(),
            'draft_opportunities' => Opportunity::where('status', 'draft')->count(),
            'featured_opportunities' => Opportunity::where('featured', true)->count(),
            'expired_opportunities' => Opportunity::where('application_deadline', '<', now())->count(),
            'total_applications' => OpportunityApplication::count(),
            'pending_applications' => OpportunityApplication::where('status', 'pending')->count(),
            'total_views' => Opportunity::sum('views_count'),
            'opportunities_this_month' => Opportunity::whereMonth('created_at', now()->month)
                                                   ->whereYear('created_at', now()->year)
                                                   ->count(),
            'top_categories' => OpportunityCategory::withCount('opportunities')
                                                  ->orderBy('opportunities_count', 'desc')
                                                  ->take(5)
                                                  ->get(),
            'top_organizations' => Organization::withCount('opportunities')
                                              ->orderBy('opportunities_count', 'desc')
                                              ->take(5)
                                              ->get(['id', 'name', 'opportunities_count']),
            'by_type' => Opportunity::select('type', DB::raw('count(*) as count'))
                                   ->groupBy('type')
                                   ->get(),
            'by_experience_level' => Opportunity::select('experience_level', DB::raw('count(*) as count'))
                                               ->groupBy('experience_level')
                                               ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Duplicate an opportunity
     */
    public function duplicate(Opportunity $opportunity): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newOpportunity = $opportunity->replicate();
            $newOpportunity->title = $opportunity->title . ' (Copy)';
            $newOpportunity->slug = Str::slug($newOpportunity->title);
            $newOpportunity->status = 'draft';
            $newOpportunity->featured = false;
            $newOpportunity->application_deadline = now()->addDays(30);
            $newOpportunity->views_count = 0;
            $newOpportunity->applications_count = 0;
            $newOpportunity->current_applicants = 0;
            $newOpportunity->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Opportunity duplicated successfully',
                'data' => $newOpportunity->load(['organization', 'category'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opportunity duplication error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Opportunity duplication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manage opportunity applications
     */
    public function applications(Request $request, Opportunity $opportunity): JsonResponse
    {
        $query = $opportunity->applications()->with(['user']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'applied_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $applications = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * Update application status
     */
    public function updateApplicationStatus(Request $request, Opportunity $opportunity, OpportunityApplication $application): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,reviewed,accepted,rejected,withdrawn',
            'admin_notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $application->update([
                'status' => $request->input('status'),
                'admin_notes' => $request->input('admin_notes'),
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
                'data' => $application->load(['user'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Application status update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Application status update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
