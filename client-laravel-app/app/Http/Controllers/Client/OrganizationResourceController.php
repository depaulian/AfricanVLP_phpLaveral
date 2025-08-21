<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\CategoryOfResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class OrganizationResourceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display resources for the organization
     */
    public function index(Organization $organization, Request $request): View
    {
        $this->checkOrganizationAccess($organization);

        $query = $organization->resources()->with(['resourceType', 'categories', 'files']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Resource type filter
        if ($request->filled('resource_type')) {
            $query->where('resource_type_id', $request->resource_type);
        }

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_of_resources.id', $request->category);
            });
        }

        // Featured filter
        if ($request->filled('featured')) {
            $query->where('featured', $request->featured === 'yes');
        }

        $resources = $query->orderBy('created', 'desc')->paginate(12)->withQueryString();

        $statuses = ['draft', 'published', 'archived'];
        $resourceTypes = ResourceType::orderBy('name')->get();
        $categories = CategoryOfResource::orderBy('name')->get();

        return view('client.organization.resources.index', compact(
            'organization', 'resources', 'statuses', 'resourceTypes', 'categories'
        ));
    }

    /**
     * Show the form for creating a new resource
     */
    public function create(Organization $organization): View
    {
        $this->checkOrganizationAdminAccess($organization);

        $resourceTypes = ResourceType::orderBy('name')->get();
        $categories = CategoryOfResource::orderBy('name')->get();
        $accessLevels = ['public', 'members_only', 'admin_only'];
        $languages = ['en', 'fr', 'ar', 'es', 'pt'];

        return view('client.organization.resources.create', compact(
            'organization', 'resourceTypes', 'categories', 'accessLevels', 'languages'
        ));
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'content' => 'nullable|string',
            'resource_type_id' => 'required|exists:resource_types,id',
            'status' => 'required|in:draft,published,archived',
            'featured' => 'boolean',
            'author' => 'nullable|string|max:255',
            'language' => 'required|string|max:5',
            'access_level' => 'required|in:public,members_only,admin_only',
            'external_url' => 'nullable|url|max:500',
            'tags' => 'nullable|string|max:500',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:category_of_resources,id',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $resourceData = $request->only([
            'title', 'description', 'content', 'resource_type_id', 'status',
            'featured', 'author', 'language', 'access_level', 'external_url', 'tags'
        ]);

        $resourceData['organization_id'] = $organization->id;
        $resourceData['download_count'] = 0;
        $resourceData['view_count'] = 0;
        $resourceData['published_date'] = $request->status === 'published' ? now() : null;

        // Convert tags string to array
        if ($request->filled('tags')) {
            $resourceData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $resource = Resource::create($resourceData);

        // Attach categories
        if ($request->filled('categories')) {
            $resource->categories()->attach($request->categories);
        }

        // Handle file uploads
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeResourceFile($resource, $file);
            }
        }

        return redirect()
            ->route('client.organizations.resources.show', [$organization, $resource])
            ->with('success', 'Resource created successfully!');
    }

    /**
     * Display the specified resource
     */
    public function show(Organization $organization, Resource $resource): View
    {
        $this->checkOrganizationAccess($organization);
        $this->checkResourceBelongsToOrganization($resource, $organization);

        $resource->load(['resourceType', 'categories', 'files']);
        
        // Increment view count
        $resource->incrementViewCount();

        $isAdmin = $this->isOrganizationAdmin($organization);

        return view('client.organization.resources.show', compact(
            'organization', 'resource', 'isAdmin'
        ));
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(Organization $organization, Resource $resource): View
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkResourceBelongsToOrganization($resource, $organization);

        $resource->load(['categories', 'files']);
        
        $resourceTypes = ResourceType::orderBy('name')->get();
        $categories = CategoryOfResource::orderBy('name')->get();
        $accessLevels = ['public', 'members_only', 'admin_only'];
        $languages = ['en', 'fr', 'ar', 'es', 'pt'];

        return view('client.organization.resources.edit', compact(
            'organization', 'resource', 'resourceTypes', 'categories', 'accessLevels', 'languages'
        ));
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, Organization $organization, Resource $resource): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkResourceBelongsToOrganization($resource, $organization);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'content' => 'nullable|string',
            'resource_type_id' => 'required|exists:resource_types,id',
            'status' => 'required|in:draft,published,archived',
            'featured' => 'boolean',
            'author' => 'nullable|string|max:255',
            'language' => 'required|string|max:5',
            'access_level' => 'required|in:public,members_only,admin_only',
            'external_url' => 'nullable|url|max:500',
            'tags' => 'nullable|string|max:500',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:category_of_resources,id',
            'new_files' => 'nullable|array',
            'new_files.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $resourceData = $request->only([
            'title', 'description', 'content', 'resource_type_id', 'status',
            'featured', 'author', 'language', 'access_level', 'external_url', 'tags'
        ]);

        // Set published date if status changed to published
        if ($request->status === 'published' && $resource->status !== 'published') {
            $resourceData['published_date'] = now();
        }

        // Convert tags string to array
        if ($request->filled('tags')) {
            $resourceData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $resource->update($resourceData);

        // Update categories
        if ($request->has('categories')) {
            $resource->categories()->sync($request->categories ?? []);
        }

        // Handle new file uploads
        if ($request->hasFile('new_files')) {
            foreach ($request->file('new_files') as $file) {
                $this->storeResourceFile($resource, $file);
            }
        }

        return redirect()
            ->route('client.organizations.resources.show', [$organization, $resource])
            ->with('success', 'Resource updated successfully!');
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Organization $organization, Resource $resource): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkResourceBelongsToOrganization($resource, $organization);

        // Delete associated files
        foreach ($resource->files as $file) {
            if ($file->file_path) {
                Storage::disk('public')->delete($file->file_path);
            }
            $file->delete();
        }

        $resource->delete();

        return redirect()
            ->route('client.organizations.resources.index', $organization)
            ->with('success', 'Resource deleted successfully!');
    }

    /**
     * Download a resource file
     */
    public function download(Organization $organization, Resource $resource, ResourceFile $file): mixed
    {
        $this->checkOrganizationAccess($organization);
        $this->checkResourceBelongsToOrganization($resource, $organization);

        if ($file->resource_id !== $resource->id) {
            abort(403, 'File does not belong to this resource.');
        }

        // Increment download count
        $resource->incrementDownloadCount();

        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            return Storage::disk('public')->download($file->file_path, $file->original_filename);
        }

        abort(404, 'File not found.');
    }

    /**
     * Delete a resource file
     */
    public function deleteFile(Organization $organization, Resource $resource, ResourceFile $file): JsonResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkResourceBelongsToOrganization($resource, $organization);

        if ($file->resource_id !== $resource->id) {
            abort(403, 'File does not belong to this resource.');
        }

        // Delete file from storage
        if ($file->file_path) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return response()->json(['success' => true, 'message' => 'File deleted successfully!']);
    }

    /**
     * Duplicate a resource
     */
    public function duplicate(Organization $organization, Resource $resource): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkResourceBelongsToOrganization($resource, $organization);

        $newResourceData = $resource->toArray();
        unset($newResourceData['id'], $newResourceData['created'], $newResourceData['modified']);
        
        // Modify title and reset counters for the duplicate
        $newResourceData['title'] = 'Copy of ' . $newResourceData['title'];
        $newResourceData['status'] = 'draft';
        $newResourceData['download_count'] = 0;
        $newResourceData['view_count'] = 0;
        $newResourceData['published_date'] = null;

        $newResource = Resource::create($newResourceData);

        // Copy categories
        $newResource->categories()->attach($resource->categories->pluck('id'));

        return redirect()
            ->route('client.organizations.resources.edit', [$organization, $newResource])
            ->with('success', 'Resource duplicated successfully! Please review and update the details.');
    }

    /**
     * Store a resource file
     */
    private function storeResourceFile(Resource $resource, $file): ResourceFile
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Determine file category
        $fileCategory = $this->determineFileCategory($mimeType);

        // Store file
        $filePath = $file->store('resources', 'public');

        return ResourceFile::create([
            'resource_id' => $resource->id,
            'original_filename' => $originalName,
            'file_path' => $filePath,
            'file_size' => $size,
            'file_type' => $extension,
            'mime_type' => $mimeType,
            'file_category' => $fileCategory,
        ]);
    }

    /**
     * Determine file category based on MIME type
     */
    private function determineFileCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'images';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'videos';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv'
        ])) {
            return 'documents';
        } else {
            return 'other';
        }
    }

    /**
     * Check if user has access to the organization
     */
    private function checkOrganizationAccess(Organization $organization): void
    {
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }
    }

    /**
     * Check if user has admin access to the organization
     */
    private function checkOrganizationAdminAccess(Organization $organization): void
    {
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ?->pivot
                                ?->role;

        if ($userRole !== 'admin') {
            abort(403, 'You do not have admin access to this organization.');
        }
    }

    /**
     * Check if user is organization admin
     */
    private function isOrganizationAdmin(Organization $organization): bool
    {
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ?->pivot
                                ?->role;

        return $userRole === 'admin';
    }

    /**
     * Check if resource belongs to organization
     */
    private function checkResourceBelongsToOrganization(Resource $resource, Organization $organization): void
    {
        if ($resource->organization_id !== $organization->id) {
            abort(403, 'This resource does not belong to the specified organization.');
        }
    }
}
