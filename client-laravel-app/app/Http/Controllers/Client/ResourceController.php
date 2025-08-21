<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\CategoryOfResource;
use App\Models\Organization;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    /**
     * Display a listing of resources for authenticated users.
     */
    public function index(Request $request)
    {
        $query = Resource::published()->with(['organization', 'resourceType', 'categories']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Resource type filter
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->resource_type_id);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_of_resources.id', $request->category_id);
            });
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Language filter
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        // Featured filter
        if ($request->filled('featured')) {
            $query->featured();
        }

        // Access level filter (only show public and member resources for authenticated users)
        if (auth()->check()) {
            $query->whereIn('access_level', ['public', 'members_only']);
        } else {
            $query->where('access_level', 'public');
        }

        // Sort
        $sortBy = $request->get('sort', 'published_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $resources = $query->paginate(12)->withQueryString();

        // Get filter options
        $resourceTypes = ResourceType::active()->get();
        $categories = CategoryOfResource::active()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        // Get featured resources for sidebar
        $featuredResources = Resource::published()->featured()->take(5)->get();

        // Get recent resources
        $recentResources = Resource::published()->orderBy('published_date', 'desc')->take(5)->get();

        return view('client.resources.index', compact(
            'resources', 
            'resourceTypes', 
            'categories', 
            'organizations', 
            'featuredResources', 
            'recentResources'
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource)
    {
        // Check if resource is published
        if (!$resource->isPublished()) {
            abort(404);
        }

        // Check access level
        if ($resource->access_level === 'members_only' && !auth()->check()) {
            return redirect()->route('login')->with('message', 'Please login to access this resource.');
        }

        if ($resource->access_level === 'admin_only') {
            abort(403);
        }

        // Increment views
        $resource->incrementViews();

        // Load relationships
        $resource->load(['organization', 'resourceType', 'categories', 'files']);

        // Get related resources
        $relatedResources = Resource::published()
            ->where('id', '!=', $resource->id)
            ->when($resource->resource_type_id, function($query) use ($resource) {
                $query->where('resource_type_id', $resource->resource_type_id);
            })
            ->when($resource->organization_id, function($query) use ($resource) {
                $query->orWhere('organization_id', $resource->organization_id);
            })
            ->take(4)
            ->get();

        return view('client.resources.show', compact('resource', 'relatedResources'));
    }

    /**
     * Display resources by type.
     */
    public function type(ResourceType $resourceType, Request $request)
    {
        if (!$resourceType->isActive()) {
            abort(404);
        }

        $query = Resource::published()->where('resource_type_id', $resourceType->id)->with(['organization', 'resourceType', 'categories']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_of_resources.id', $request->category_id);
            });
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Access level filter
        if (auth()->check()) {
            $query->whereIn('access_level', ['public', 'members_only']);
        } else {
            $query->where('access_level', 'public');
        }

        // Sort
        $sortBy = $request->get('sort', 'published_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $resources = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = CategoryOfResource::active()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        // Get other resource types
        $resourceTypes = ResourceType::active()->where('id', '!=', $resourceType->id)->get();

        return view('client.resources.type', compact(
            'resources', 
            'resourceType', 
            'categories', 
            'organizations', 
            'resourceTypes'
        ));
    }

    /**
     * Display resources by category.
     */
    public function category(CategoryOfResource $category, Request $request)
    {
        if (!$category->isActive()) {
            abort(404);
        }

        $query = Resource::published()
            ->whereHas('categories', function($q) use ($category) {
                $q->where('category_of_resources.id', $category->id);
            })
            ->with(['organization', 'resourceType', 'categories']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Resource type filter
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->resource_type_id);
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Access level filter
        if (auth()->check()) {
            $query->whereIn('access_level', ['public', 'members_only']);
        } else {
            $query->where('access_level', 'public');
        }

        // Sort
        $sortBy = $request->get('sort', 'published_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $resources = $query->paginate(12)->withQueryString();

        // Get filter options
        $resourceTypes = ResourceType::active()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        // Get other categories
        $categories = CategoryOfResource::active()->where('id', '!=', $category->id)->get();

        return view('client.resources.category', compact(
            'resources', 
            'category', 
            'resourceTypes', 
            'organizations', 
            'categories'
        ));
    }

    /**
     * Download a resource file.
     */
    public function download(Resource $resource, $fileId)
    {
        // Check if resource is published
        if (!$resource->isPublished()) {
            abort(404);
        }

        // Check access level
        if ($resource->access_level === 'members_only' && !auth()->check()) {
            abort(403, 'Please login to download this resource.');
        }

        if ($resource->access_level === 'admin_only') {
            abort(403);
        }

        // Find the file
        $file = $resource->files()->findOrFail($fileId);

        // Increment download count
        $file->incrementDownloadCount();
        $resource->incrementDownloadCount();

        // Return download response
        return response()->download(storage_path('app/' . $file->file_path), $file->original_filename);
    }

    /**
     * Display public resources (for non-authenticated users).
     */
    public function publicIndex(Request $request)
    {
        $query = Resource::published()->featured()->where('access_level', 'public')->with(['organization', 'resourceType', 'categories']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Resource type filter
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->resource_type_id);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_of_resources.id', $request->category_id);
            });
        }

        $resources = $query->orderBy('published_date', 'desc')->paginate(9)->withQueryString();

        // Get filter options
        $resourceTypes = ResourceType::active()->get();
        $categories = CategoryOfResource::active()->get();

        return view('client.resources.public', compact('resources', 'resourceTypes', 'categories'));
    }

    /**
     * Display public resource details.
     */
    public function publicShow(Resource $resource)
    {
        // Check if resource is published, featured, and public
        if (!$resource->isPublished() || !$resource->isFeatured() || $resource->access_level !== 'public') {
            abort(404);
        }

        // Increment views
        $resource->incrementViews();

        // Load relationships
        $resource->load(['organization', 'resourceType', 'categories', 'files']);

        // Get related featured public resources
        $relatedResources = Resource::published()
            ->featured()
            ->where('access_level', 'public')
            ->where('id', '!=', $resource->id)
            ->when($resource->resource_type_id, function($query) use ($resource) {
                $query->where('resource_type_id', $resource->resource_type_id);
            })
            ->take(3)
            ->get();

        return view('client.resources.public-show', compact('resource', 'relatedResources'));
    }

    /**
     * Display resources by organization.
     */
    public function organization(Request $request, $organizationId)
    {
        $organization = Organization::findOrFail($organizationId);

        $query = Resource::published()->where('organization_id', $organizationId)->with(['organization', 'resourceType', 'categories']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Resource type filter
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->resource_type_id);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_of_resources.id', $request->category_id);
            });
        }

        // Access level filter
        if (auth()->check()) {
            $query->whereIn('access_level', ['public', 'members_only']);
        } else {
            $query->where('access_level', 'public');
        }

        // Sort
        $sortBy = $request->get('sort', 'published_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $resources = $query->paginate(12)->withQueryString();

        // Get filter options
        $resourceTypes = ResourceType::active()->get();
        $categories = CategoryOfResource::active()->get();

        return view('client.resources.organization', compact('resources', 'organization', 'resourceTypes', 'categories'));
    }

    /**
     * Search resources with advanced filters.
     */
    public function search(Request $request)
    {
        $query = Resource::published()->with(['organization', 'resourceType', 'categories']);

        // Search functionality
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Advanced filters
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->resource_type_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_of_resources.id', $request->category_id);
            });
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        if ($request->filled('date_from')) {
            $query->where('published_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('published_date', '<=', $request->date_to);
        }

        // Access level filter
        if (auth()->check()) {
            $query->whereIn('access_level', ['public', 'members_only']);
        } else {
            $query->where('access_level', 'public');
        }

        // Sort
        $sortBy = $request->get('sort', 'published_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $resources = $query->paginate(12)->withQueryString();

        // Get filter options
        $resourceTypes = ResourceType::active()->get();
        $categories = CategoryOfResource::active()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        return view('client.resources.search', compact('resources', 'resourceTypes', 'categories', 'organizations'));
    }
}
