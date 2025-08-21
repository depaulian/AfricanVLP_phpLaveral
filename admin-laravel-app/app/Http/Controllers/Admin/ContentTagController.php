<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentTag;
use App\Models\TaggedContent;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContentTagController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display content tags dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = ContentTag::with(['createdBy', 'parent'])
            ->withCount(['taggedContent', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'featured':
                    $query->where('is_featured', true);
                    break;
                case 'unused':
                    $query->where('usage_count', 0);
                    break;
                case 'popular':
                    $query->where('usage_count', '>', 10);
                    break;
            }
        }

        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tags = $query->paginate(50);
        
        // Get filter options
        $types = ContentTag::getAvailableTypes();
        $parentTags = ContentTag::whereNull('parent_id')->orderBy('name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'tags' => $tags,
                'types' => $types,
                'parent_tags' => $parentTags,
                'stats' => $this->getTagStats(),
            ]);
        }

        return view('admin.content-tags.index', compact('tags', 'types', 'parentTags'));
    }

    /**
     * Show create tag form
     */
    public function create(): View
    {
        $types = ContentTag::getAvailableTypes();
        $colors = ContentTag::getAvailableColors();
        $parentTags = ContentTag::whereNull('parent_id')->active()->orderBy('name')->get();

        return view('admin.content-tags.create', compact('types', 'colors', 'parentTags'));
    }

    /**
     * Store new tag
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:content_tags,name',
            'description' => 'sometimes|nullable|string|max:500',
            'type' => 'required|in:' . implode(',', ContentTag::getAvailableTypes()),
            'color' => 'sometimes|nullable|in:' . implode(',', ContentTag::getAvailableColors()),
            'icon' => 'sometimes|nullable|string|max:50',
            'parent_id' => 'sometimes|nullable|exists:content_tags,id',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $tag = ContentTag::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'color' => $request->color,
                'icon' => $request->icon,
                'parent_id' => $request->parent_id,
                'sort_order' => $request->get('sort_order', 0),
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'created_by' => auth()->id(),
            ]);

            // Log the creation
            $this->activityLogService->log(
                'create',
                $tag,
                auth()->user(),
                "Created content tag: {$tag->name} ({$tag->type})"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Content tag created successfully',
                    'tag' => $tag->load(['createdBy', 'parent'])
                ]);
            }

            return redirect()->route('admin.content-tags.show', $tag)
                            ->with('success', 'Content tag created successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create content tag'], 500);
            }
            
            return back()->with('error', 'Failed to create content tag');
        }
    }

    /**
     * Show specific tag
     */
    public function show(ContentTag $contentTag): View|JsonResponse
    {
        $contentTag->load(['createdBy', 'parent', 'children']);
        
        // Get tagged content statistics
        $taggedStats = [
            'total' => $contentTag->taggedContent()->count(),
            'by_type' => $contentTag->taggedContent()
                ->selectRaw('taggable_type, COUNT(*) as count')
                ->groupBy('taggable_type')
                ->pluck('count', 'taggable_type')
                ->toArray(),
            'recent' => $contentTag->taggedContent()
                ->where('tagged_at', '>=', Carbon::now()->subDays(30))
                ->count(),
        ];

        // Get recent tagged content
        $recentTagged = $contentTag->taggedContent()
            ->with(['taggable', 'taggedBy'])
            ->orderBy('tagged_at', 'desc')
            ->limit(10)
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'tag' => $contentTag,
                'tagged_stats' => $taggedStats,
                'recent_tagged' => $recentTagged,
            ]);
        }

        return view('admin.content-tags.show', compact('contentTag', 'taggedStats', 'recentTagged'));
    }

    /**
     * Show edit tag form
     */
    public function edit(ContentTag $contentTag): View
    {
        $contentTag->load(['createdBy', 'parent']);
        $types = ContentTag::getAvailableTypes();
        $colors = ContentTag::getAvailableColors();
        $parentTags = ContentTag::whereNull('parent_id')
            ->where('id', '!=', $contentTag->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('admin.content-tags.edit', compact('contentTag', 'types', 'colors', 'parentTags'));
    }

    /**
     * Update tag
     */
    public function update(Request $request, ContentTag $contentTag): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:content_tags,name,' . $contentTag->id,
            'description' => 'sometimes|nullable|string|max:500',
            'type' => 'sometimes|in:' . implode(',', ContentTag::getAvailableTypes()),
            'color' => 'sometimes|nullable|in:' . implode(',', ContentTag::getAvailableColors()),
            'icon' => 'sometimes|nullable|string|max:50',
            'parent_id' => 'sometimes|nullable|exists:content_tags,id',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        // Prevent circular parent relationship
        if ($request->filled('parent_id') && $request->parent_id == $contentTag->id) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_id', 'A tag cannot be its own parent.');
            });
        }

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $oldData = $contentTag->toArray();

        $contentTag->update($request->only([
            'name', 'description', 'type', 'color', 'icon', 'parent_id', 
            'sort_order', 'is_active', 'is_featured'
        ]));

        // Log the update
        $this->activityLogService->log(
            'update',
            $contentTag,
            auth()->user(),
            "Updated content tag: {$contentTag->name}",
            ['old_data' => $oldData, 'new_data' => $contentTag->fresh()->toArray()]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Content tag updated successfully',
                'tag' => $contentTag->fresh()->load(['createdBy', 'parent'])
            ]);
        }

        return redirect()->route('admin.content-tags.show', $contentTag)
                        ->with('success', 'Content tag updated successfully');
    }

    /**
     * Delete tag
     */
    public function destroy(ContentTag $contentTag): JsonResponse|RedirectResponse
    {
        // Check if tag has children
        if ($contentTag->hasChildren()) {
            $error = 'Cannot delete tag with child tags. Please delete or reassign child tags first.';
            if (request()->expectsJson()) {
                return response()->json(['error' => $error], 422);
            }
            return back()->with('error', $error);
        }

        DB::beginTransaction();
        try {
            $name = $contentTag->name;

            // Remove all tagged content associations
            $contentTag->taggedContent()->delete();

            $contentTag->delete();

            // Log the deletion
            $this->activityLogService->log(
                'delete',
                $contentTag,
                auth()->user(),
                "Deleted content tag: {$name}"
            );

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Content tag deleted successfully']);
            }

            return redirect()->route('admin.content-tags.index')
                            ->with('success', 'Content tag deleted successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete content tag'], 500);
            }
            
            return back()->with('error', 'Failed to delete content tag');
        }
    }

    /**
     * Bulk operations on tags
     */
    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,feature,unfeature,delete',
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:content_tags,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        $tags = ContentTag::whereIn('id', $request->tag_ids)->get();
        $action = $request->action;
        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($tags as $tag) {
                switch ($action) {
                    case 'activate':
                        $tag->update(['is_active' => true]);
                        break;
                    case 'deactivate':
                        $tag->update(['is_active' => false]);
                        break;
                    case 'feature':
                        $tag->update(['is_featured' => true]);
                        break;
                    case 'unfeature':
                        $tag->update(['is_featured' => false]);
                        break;
                    case 'delete':
                        if (!$tag->hasChildren()) {
                            $tag->taggedContent()->delete();
                            $tag->delete();
                        }
                        break;
                }
                $count++;
            }

            // Log the bulk action
            $this->activityLogService->log(
                'bulk_action',
                null,
                auth()->user(),
                "Performed bulk {$action} on {$count} content tags"
            );

            DB::commit();

            $message = "Successfully performed {$action} on {$count} content tags";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bulk action failed'], 500);
            }
            
            return back()->with('error', 'Bulk action failed');
        }
    }

    /**
     * Reorder tags
     */
    public function reorder(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'tag_orders' => 'required|array',
            'tag_orders.*.id' => 'required|exists:content_tags,id',
            'tag_orders.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            foreach ($request->tag_orders as $order) {
                ContentTag::where('id', $order['id'])
                    ->update(['sort_order' => $order['sort_order']]);
            }

            // Log the reorder
            $this->activityLogService->log(
                'reorder',
                null,
                auth()->user(),
                "Reordered " . count($request->tag_orders) . " content tags"
            );

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tags reordered successfully']);
            }

            return back()->with('success', 'Tags reordered successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to reorder tags'], 500);
            }
            
            return back()->with('error', 'Failed to reorder tags');
        }
    }

    /**
     * Search tags for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = $request->get('type');
        $limit = min($request->get('limit', 20), 50);

        $tags = ContentTag::search($query, $limit);

        if ($type) {
            $tags = $tags->where('type', $type);
        }

        return response()->json([
            'tags' => $tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'type' => $tag->type,
                    'color' => $tag->display_color,
                    'icon' => $tag->display_icon,
                    'usage_count' => $tag->usage_count,
                ];
            })
        ]);
    }

    /**
     * Get popular tags
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);
        $type = $request->get('type');

        $query = ContentTag::popular($limit);

        if ($type) {
            $query = $query->where('type', $type);
        }

        $tags = $query->get();

        return response()->json(['tags' => $tags]);
    }

    /**
     * Get trending tags
     */
    public function trending(Request $request): JsonResponse
    {
        $days = min($request->get('days', 30), 365);
        $limit = min($request->get('limit', 10), 50);
        $type = $request->get('type');

        $query = ContentTag::trending($days, $limit);

        if ($type) {
            $query = $query->where('type', $type);
        }

        $tags = $query->get();

        return response()->json(['tags' => $tags]);
    }

    /**
     * Get tag statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getTagStats());
    }

    /**
     * Export tags to CSV
     */
    public function export(Request $request)
    {
        $query = ContentTag::with(['createdBy', 'parent'])
            ->withCount(['taggedContent', 'children']);

        // Apply same filters as index
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'featured':
                    $query->where('is_featured', true);
                    break;
            }
        }

        $tags = $query->get();

        $filename = 'content_tags_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($tags) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'ID', 'Name', 'Slug', 'Description', 'Type', 'Color', 'Icon',
                'Parent', 'Sort Order', 'Active', 'Featured', 'Usage Count',
                'Children Count', 'Created By', 'Created At'
            ]);

            foreach ($tags as $tag) {
                fputcsv($file, [
                    $tag->id,
                    $tag->name,
                    $tag->slug,
                    $tag->description,
                    $tag->type,
                    $tag->color,
                    $tag->icon,
                    $tag->parent?->name,
                    $tag->sort_order,
                    $tag->is_active ? 'Yes' : 'No',
                    $tag->is_featured ? 'Yes' : 'No',
                    $tag->usage_count,
                    $tag->children_count,
                    $tag->createdBy?->name,
                    $tag->created?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get tag statistics for dashboard
     */
    private function getTagStats(): array
    {
        $total = ContentTag::count();
        $active = ContentTag::where('is_active', true)->count();
        $featured = ContentTag::where('is_featured', true)->count();
        $unused = ContentTag::where('usage_count', 0)->count();

        // Type distribution
        $typeDistribution = ContentTag::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Usage statistics
        $usageStats = [
            'most_used' => ContentTag::orderBy('usage_count', 'desc')->limit(5)->get(),
            'least_used' => ContentTag::where('usage_count', '>', 0)->orderBy('usage_count')->limit(5)->get(),
            'trending' => ContentTag::trending(30, 5)->get(),
        ];

        // Recent activity
        $recentTags = ContentTag::where('created', '>=', Carbon::now()->subDays(7))->count();
        $recentTagging = TaggedContent::where('tagged_at', '>=', Carbon::now()->subDays(7))->count();

        return [
            'total' => $total,
            'active' => $active,
            'featured' => $featured,
            'unused' => $unused,
            'type_distribution' => $typeDistribution,
            'usage_stats' => $usageStats,
            'recent_tags' => $recentTags,
            'recent_tagging' => $recentTagging,
            'activity_rate' => $total > 0 ? round((($total - $unused) / $total) * 100, 2) : 0,
        ];
    }
}
