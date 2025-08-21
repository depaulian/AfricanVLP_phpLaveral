<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\CategoryOfResource;
use App\Models\Organization;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of resources
     */
    public function index(Request $request)
    {
        $query = Resource::with(['organization', 'resourceType', 'files', 'categories']);

        // Apply filters
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->has('resource_type_id')) {
            $query->where('resource_type_id', $request->input('resource_type_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $resources = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $resources
        ]);
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'sometimes|string',
            'organization_id' => 'required|exists:organizations,id',
            'resource_type_id' => 'required|exists:resource_types,id',
            'status' => 'sometimes|in:draft,published,archived',
            'featured' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'author' => 'sometimes|string|max:255',
            'published_date' => 'sometimes|date',
            'language' => 'sometimes|string|max:10',
            'access_level' => 'sometimes|in:public,private,organization',
            'external_url' => 'sometimes|url',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:category_of_resources,id',
            'files' => 'sometimes|array',
            'files.*' => 'file|max:' . (config('cloudinary.max_file_size') / 1024)
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

            // Create resource
            $resource = Resource::create([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'content' => $request->input('content'),
                'organization_id' => $request->input('organization_id'),
                'resource_type_id' => $request->input('resource_type_id'),
                'status' => $request->input('status', 'draft'),
                'featured' => $request->boolean('featured', false),
                'tags' => $request->input('tags', []),
                'author' => $request->input('author'),
                'published_date' => $request->input('published_date'),
                'language' => $request->input('language', 'en'),
                'access_level' => $request->input('access_level', 'public'),
                'external_url' => $request->input('external_url'),
                'download_count' => 0,
                'view_count' => 0
            ]);

            // Attach categories
            if ($request->has('category_ids')) {
                $resource->categories()->attach($request->input('category_ids'));
            }

            // Handle file uploads
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $uploadResult = $this->fileUploadService->uploadMultipleFiles($files, 'resources');

                if ($uploadResult['success'] && !empty($uploadResult['uploaded'])) {
                    foreach ($uploadResult['uploaded'] as $uploadedFile) {
                        ResourceFile::create([
                            'resource_id' => $resource->id,
                            'filename' => pathinfo($uploadedFile['original_filename'], PATHINFO_FILENAME),
                            'original_filename' => $uploadedFile['original_filename'],
                            'file_size' => $uploadedFile['bytes'],
                            'file_type' => $uploadedFile['format'],
                            'file_category' => $this->fileUploadService->getFileCategory($uploadedFile['format']),
                            'mime_type' => $uploadedFile['mime_type'],
                            'cloudinary_public_id' => $uploadedFile['public_id'],
                            'cloudinary_url' => $uploadedFile['url'],
                            'cloudinary_secure_url' => $uploadedFile['secure_url'],
                            'width' => $uploadedFile['width'],
                            'height' => $uploadedFile['height'],
                            'status' => 'active'
                        ]);
                    }
                }
            }

            DB::commit();

            // Load relationships for response
            $resource->load(['organization', 'resourceType', 'files', 'categories']);

            return response()->json([
                'success' => true,
                'message' => 'Resource created successfully',
                'data' => $resource
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resource creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Resource creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource
     */
    public function show(Resource $resource): JsonResponse
    {
        $resource->load(['organization', 'resourceType', 'files', 'categories']);
        
        // Increment view count
        $resource->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => $resource
        ]);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, Resource $resource): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'content' => 'sometimes|string',
            'organization_id' => 'sometimes|exists:organizations,id',
            'resource_type_id' => 'sometimes|exists:resource_types,id',
            'status' => 'sometimes|in:draft,published,archived',
            'featured' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'author' => 'sometimes|string|max:255',
            'published_date' => 'sometimes|date',
            'language' => 'sometimes|string|max:10',
            'access_level' => 'sometimes|in:public,private,organization',
            'external_url' => 'sometimes|url',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:category_of_resources,id'
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

            // Update resource
            $resource->update($request->only([
                'title', 'description', 'content', 'organization_id', 'resource_type_id',
                'status', 'featured', 'tags', 'author', 'published_date', 'language',
                'access_level', 'external_url'
            ]));

            // Update categories
            if ($request->has('category_ids')) {
                $resource->categories()->sync($request->input('category_ids'));
            }

            DB::commit();

            // Load relationships for response
            $resource->load(['organization', 'resourceType', 'files', 'categories']);

            return response()->json([
                'success' => true,
                'message' => 'Resource updated successfully',
                'data' => $resource
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resource update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Resource update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Resource $resource): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete associated files from Cloudinary
            foreach ($resource->files as $file) {
                $resourceType = $file->isImage() ? 'image' : ($file->isVideo() ? 'video' : 'raw');
                $this->fileUploadService->deleteFile($file->cloudinary_public_id, $resourceType);
            }

            // Delete the resource (this will also delete associated files due to foreign key constraints)
            $resource->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Resource deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resource deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Resource deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get resource types for dropdown
     */
    public function getResourceTypes(): JsonResponse
    {
        $resourceTypes = ResourceType::active()->get();
        
        return response()->json([
            'success' => true,
            'data' => $resourceTypes
        ]);
    }

    /**
     * Get resource categories for dropdown
     */
    public function getCategories(): JsonResponse
    {
        $categories = CategoryOfResource::active()->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Download a resource file
     */
    public function downloadFile(ResourceFile $file): JsonResponse
    {
        try {
            // Increment download count
            $file->incrementDownloadCount();
            $file->resource->incrementDownloadCount();

            return response()->json([
                'success' => true,
                'message' => 'Download initiated',
                'data' => [
                    'download_url' => $file->getFileUrl(),
                    'filename' => $file->original_filename
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('File download error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display resource files management interface
     */
    public function files(Request $request)
    {
        $query = ResourceFile::with(['resource']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('file_category', $request->input('category'));
        }

        if ($request->filled('resource_id')) {
            $query->where('resource_id', $request->input('resource_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $files = $query->paginate($perPage)->withQueryString();

        // Get resources for filter dropdown
        $resources = Resource::select('id', 'title')->orderBy('title')->get();

        // Get file statistics
        $stats = [
            'total_files' => ResourceFile::count(),
            'total_size' => ResourceFile::sum('file_size'),
            'images_count' => ResourceFile::where('file_category', 'images')->count(),
            'documents_count' => ResourceFile::where('file_category', 'documents')->count(),
            'videos_count' => ResourceFile::where('file_category', 'videos')->count(),
            'audio_count' => ResourceFile::where('file_category', 'audio')->count(),
        ];

        return view('admin.resources.files', compact('files', 'resources', 'stats'));
    }
}