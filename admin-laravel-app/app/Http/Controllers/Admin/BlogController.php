<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Organization;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of blogs
     */
    public function index(Request $request): JsonResponse
    {
        $query = Blog::with(['author', 'organization', 'category']);

        // Apply filters
        if ($request->filled('author_id')) {
            $query->where('author_id', $request->input('author_id'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        if ($request->filled('language')) {
            $query->where('language', $request->input('language'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->search($search);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $blogs = $query->paginate($perPage)->withQueryString();

        // Get filter options
        $authors = User::select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
                        ->orderBy('name')
                        ->get();
        $organizations = Organization::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $categories = BlogCategory::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'blogs' => $blogs,
                'filters' => [
                    'authors' => $authors,
                    'organizations' => $organizations,
                    'categories' => $categories
                ]
            ]
        ]);
    }

    /**
     * Store a newly created blog
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'sometimes|string|max:500',
            'featured_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author_id' => 'sometimes|exists:users,id',
            'organization_id' => 'sometimes|exists:organizations,id',
            'category_id' => 'sometimes|exists:blog_categories,id',
            'status' => 'sometimes|in:draft,published,archived',
            'featured' => 'sometimes|boolean',
            'published_at' => 'sometimes|date',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array',
            'language' => 'sometimes|string|max:10'
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
                    'blogs/images',
                    [
                        'folder' => 'blogs',
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

            $blog = Blog::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog created successfully',
                'data' => $blog->load(['author', 'organization', 'category'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Blog creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified blog
     */
    public function show(Blog $blog): JsonResponse
    {
        $blog->load(['author', 'organization', 'category']);

        return response()->json([
            'success' => true,
            'data' => $blog
        ]);
    }

    /**
     * Update the specified blog
     */
    public function update(Request $request, Blog $blog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'sometimes|string|max:500',
            'featured_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author_id' => 'sometimes|exists:users,id',
            'organization_id' => 'sometimes|exists:organizations,id',
            'category_id' => 'sometimes|exists:blog_categories,id',
            'status' => 'sometimes|in:draft,published,archived',
            'featured' => 'sometimes|boolean',
            'published_at' => 'sometimes|date',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array',
            'language' => 'sometimes|string|max:10'
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
                if ($blog->featured_image) {
                    $this->fileUploadService->deleteFile($blog->featured_image);
                }

                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('featured_image'),
                    'blogs/images',
                    [
                        'folder' => 'blogs',
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
            if (isset($data['status']) && $data['status'] === 'published' && !$blog->published_at && !isset($data['published_at'])) {
                $data['published_at'] = now();
            }

            $blog->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog updated successfully',
                'data' => $blog->load(['author', 'organization', 'category'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Blog update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified blog
     */
    public function destroy(Blog $blog): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete featured image if exists
            if ($blog->featured_image) {
                $this->fileUploadService->deleteFile($blog->featured_image);
            }

            $blog->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Blog deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations on blogs
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:publish,unpublish,feature,unfeature,archive,delete',
            'blog_ids' => 'required|array|min:1',
            'blog_ids.*' => 'exists:blogs,id'
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

            $blogIds = $request->input('blog_ids');
            $action = $request->input('action');
            $affected = 0;

            switch ($action) {
                case 'publish':
                    $affected = Blog::whereIn('id', $blogIds)
                        ->update([
                            'status' => 'published',
                            'published_at' => now()
                        ]);
                    break;

                case 'unpublish':
                    $affected = Blog::whereIn('id', $blogIds)
                        ->update(['status' => 'draft']);
                    break;

                case 'feature':
                    $affected = Blog::whereIn('id', $blogIds)
                        ->update(['featured' => true]);
                    break;

                case 'unfeature':
                    $affected = Blog::whereIn('id', $blogIds)
                        ->update(['featured' => false]);
                    break;

                case 'archive':
                    $affected = Blog::whereIn('id', $blogIds)
                        ->update(['status' => 'archived']);
                    break;

                case 'delete':
                    $blogs = Blog::whereIn('id', $blogIds)->get();
                    foreach ($blogs as $blog) {
                        if ($blog->featured_image) {
                            $this->fileUploadService->deleteFile($blog->featured_image);
                        }
                    }
                    $affected = Blog::whereIn('id', $blogIds)->delete();
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
     * Get blog statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_blogs' => Blog::count(),
            'published_blogs' => Blog::where('status', 'published')->count(),
            'draft_blogs' => Blog::where('status', 'draft')->count(),
            'featured_blogs' => Blog::where('featured', true)->count(),
            'total_views' => Blog::sum('views_count'),
            'total_likes' => Blog::sum('likes_count'),
            'total_comments' => Blog::sum('comments_count'),
            'blogs_this_month' => Blog::whereMonth('created_at', now()->month)
                                     ->whereYear('created_at', now()->year)
                                     ->count(),
            'top_categories' => BlogCategory::withCount('blogs')
                                           ->orderBy('blogs_count', 'desc')
                                           ->take(5)
                                           ->get(),
            'top_authors' => User::withCount('blogs')
                                ->orderBy('blogs_count', 'desc')
                                ->take(5)
                                ->get(['id', 'name', 'blogs_count'])
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Duplicate a blog
     */
    public function duplicate(Blog $blog): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newBlog = $blog->replicate();
            $newBlog->title = $blog->title . ' (Copy)';
            $newBlog->slug = Str::slug($newBlog->title);
            $newBlog->status = 'draft';
            $newBlog->featured = false;
            $newBlog->published_at = null;
            $newBlog->views_count = 0;
            $newBlog->likes_count = 0;
            $newBlog->comments_count = 0;
            $newBlog->author_id = Auth::id();
            $newBlog->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog duplicated successfully',
                'data' => $newBlog->load(['author', 'organization', 'category'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog duplication error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Blog duplication failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
