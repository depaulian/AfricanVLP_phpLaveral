<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Organization;
use App\Models\Region;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of news
     */
    public function index(Request $request): JsonResponse
    {
        $query = News::with(['organization', 'region', 'author']);

        // Apply filters
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->input('region_id'));
        }

        if ($request->filled('author_id')) {
            $query->where('author_id', $request->input('author_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('published_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('published_at', '<=', $request->input('date_to'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $news = $query->paginate($perPage)->withQueryString();

        // Get filter options
        $organizations = Organization::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $regions = Region::select('id', 'name')->orderBy('name')->get();
        $authors = User::select('id', 'first_name')->orderBy('first_name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'news' => $news,
                'filters' => [
                    'organizations' => $organizations,
                    'regions' => $regions,
                    'authors' => $authors
                ]
            ]
        ]);
    }

    /**
     * Store a newly created news article
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author_id' => 'sometimes|exists:users,id',
            'organization_id' => 'sometimes|exists:organizations,id',
            'region_id' => 'sometimes|exists:regions,id',
            'status' => 'sometimes|in:draft,published,archived',
            'featured' => 'sometimes|boolean',
            'published_at' => 'sometimes|date',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array',
            'language' => 'sometimes|string|max:10',
            'external_url' => 'sometimes|url'
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
            
            // Set author to current user if not provided
            if (!isset($data['author_id'])) {
                $data['author_id'] = Auth::id();
            }

            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('featured_image'),
                    'news/images',
                    [
                        'folder' => 'news',
                        'transformation' => [
                            'width' => 1200,
                            'height' => 630,
                            'crop' => 'fill',
                            'quality' => 'auto'
                        ]
                    ]
                );

                if ($uploadResult['success']) {
                    $data['featured_image'] = $uploadResult['file_path'];
                }
            }

            // Set published_at if status is published and not set
            if ($data['status'] === 'published' && !isset($data['published_at'])) {
                $data['published_at'] = now();
            }

            $news = News::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'News article created successfully',
                'data' => $news->load(['organization', 'region', 'author'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('News creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'News creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified news article
     */
    public function show(News $news): JsonResponse
    {
        $news->load(['organization', 'region', 'author']);

        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Update the specified news article
     */
    public function update(Request $request, News $news): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500',
            'content' => 'sometimes|string',
            'featured_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author_id' => 'sometimes|exists:users,id',
            'organization_id' => 'sometimes|exists:organizations,id',
            'region_id' => 'sometimes|exists:regions,id',
            'status' => 'sometimes|in:draft,published,archived',
            'featured' => 'sometimes|boolean',
            'published_at' => 'sometimes|date',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array',
            'language' => 'sometimes|string|max:10',
            'external_url' => 'sometimes|url'
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

            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                // Delete old image if exists
                if ($news->featured_image) {
                    $this->fileUploadService->deleteFile($news->featured_image);
                }

                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('featured_image'),
                    'news/images',
                    [
                        'folder' => 'news',
                        'transformation' => [
                            'width' => 1200,
                            'height' => 630,
                            'crop' => 'fill',
                            'quality' => 'auto'
                        ]
                    ]
                );

                if ($uploadResult['success']) {
                    $data['featured_image'] = $uploadResult['file_path'];
                }
            }

            // Set published_at if status changed to published and not set
            if (isset($data['status']) && $data['status'] === 'published' && !$news->published_at && !isset($data['published_at'])) {
                $data['published_at'] = now();
            }

            $news->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'News article updated successfully',
                'data' => $news->load(['organization', 'region', 'author'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('News update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'News update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified news article
     */
    public function destroy(News $news): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete featured image if exists
            if ($news->featured_image) {
                $this->fileUploadService->deleteFile($news->featured_image);
            }

            $news->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'News article deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('News deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'News deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations on news articles
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:publish,unpublish,feature,unfeature,archive,delete',
            'news_ids' => 'required|array|min:1',
            'news_ids.*' => 'exists:news,id'
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

            $newsIds = $request->input('news_ids');
            $action = $request->input('action');
            $affected = 0;

            switch ($action) {
                case 'publish':
                    $affected = News::whereIn('id', $newsIds)
                        ->update([
                            'status' => 'published',
                            'published_at' => now()
                        ]);
                    break;

                case 'unpublish':
                    $affected = News::whereIn('id', $newsIds)
                        ->update(['status' => 'draft']);
                    break;

                case 'feature':
                    $affected = News::whereIn('id', $newsIds)
                        ->update(['featured' => true]);
                    break;

                case 'unfeature':
                    $affected = News::whereIn('id', $newsIds)
                        ->update(['featured' => false]);
                    break;

                case 'archive':
                    $affected = News::whereIn('id', $newsIds)
                        ->update(['status' => 'archived']);
                    break;

                case 'delete':
                    $newsArticles = News::whereIn('id', $newsIds)->get();
                    foreach ($newsArticles as $newsArticle) {
                        if ($newsArticle->featured_image) {
                            $this->fileUploadService->deleteFile($newsArticle->featured_image);
                        }
                    }
                    $affected = News::whereIn('id', $newsIds)->delete();
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
     * Get news statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_news' => News::count(),
            'published_news' => News::where('status', 'published')->count(),
            'draft_news' => News::where('status', 'draft')->count(),
            'featured_news' => News::where('featured', true)->count(),
            'total_views' => News::sum('views_count'),
            'news_this_month' => News::whereMonth('created_at', now()->month)
                                   ->whereYear('created_at', now()->year)
                                   ->count(),
            'top_organizations' => Organization::withCount('news')
                                              ->orderBy('news_count', 'desc')
                                              ->take(5)
                                              ->get(['id', 'name', 'news_count']),
            'top_regions' => Region::withCount('news')
                                  ->orderBy('news_count', 'desc')
                                  ->take(5)
                                  ->get(['id', 'name', 'news_count']),
            'top_authors' => User::withCount('news')
                                ->orderBy('news_count', 'desc')
                                ->take(5)
                                ->get(['id', 'name', 'news_count']),
            'by_status' => News::select('status', DB::raw('count(*) as count'))
                              ->groupBy('status')
                              ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Duplicate a news article
     */
    public function duplicate(News $news): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newNews = $news->replicate();
            $newNews->title = $news->title . ' (Copy)';
            $newNews->slug = Str::slug($newNews->title);
            $newNews->status = 'draft';
            $newNews->featured = false;
            $newNews->published_at = null;
            $newNews->views_count = 0;
            $newNews->author_id = Auth::id();
            $newNews->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'News article duplicated successfully',
                'data' => $newNews->load(['organization', 'region', 'author'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('News duplication error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'News duplication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get news by organization
     */
    public function byOrganization(Request $request, Organization $organization): JsonResponse
    {
        $query = $organization->news()->with(['region', 'author']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $news = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Get news by region
     */
    public function byRegion(Request $request, Region $region): JsonResponse
    {
        $query = $region->news()->with(['organization', 'author']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $news = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }
}
