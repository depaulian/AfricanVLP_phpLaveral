<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Organization;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of blogs for authenticated users.
     */
    public function index(Request $request)
    {
        $query = Blog::published()->with(['author', 'organization', 'category']);

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

        // Author filter
        if ($request->filled('author_id')) {
            $query->where('author_id', $request->author_id);
        }

        // Language filter
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        // Featured filter
        if ($request->filled('featured')) {
            $query->featured();
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $blogs = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = BlogCategory::active()->ordered()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        // Get featured blogs for sidebar
        $featuredBlogs = Blog::published()->featured()->take(5)->get();

        // Get recent blogs
        $recentBlogs = Blog::published()->orderBy('published_at', 'desc')->take(5)->get();

        return view('client.blog.index', compact('blogs', 'categories', 'organizations', 'featuredBlogs', 'recentBlogs'));
    }

    /**
     * Display the specified blog article.
     */
    public function show(Blog $blog)
    {
        // Check if blog is published
        if (!$blog->isPublished()) {
            abort(404);
        }

        // Increment views
        $blog->incrementViews();

        // Load relationships
        $blog->load(['author', 'organization', 'category']);

        // Get related blogs
        $relatedBlogs = Blog::published()
                          ->where('id', '!=', $blog->id)
                          ->when($blog->category_id, function($query) use ($blog) {
                              $query->where('category_id', $blog->category_id);
                          })
                          ->when($blog->organization_id, function($query) use ($blog) {
                              $query->orWhere('organization_id', $blog->organization_id);
                          })
                          ->take(4)
                          ->get();

        // Get next and previous blogs
        $nextBlog = Blog::published()
                       ->where('published_at', '>', $blog->published_at)
                       ->orderBy('published_at', 'asc')
                       ->first();

        $previousBlog = Blog::published()
                           ->where('published_at', '<', $blog->published_at)
                           ->orderBy('published_at', 'desc')
                           ->first();

        return view('client.blog.show', compact('blog', 'relatedBlogs', 'nextBlog', 'previousBlog'));
    }

    /**
     * Display blogs by category.
     */
    public function category(BlogCategory $category, Request $request)
    {
        if (!$category->isActive()) {
            abort(404);
        }

        $query = Blog::published()->byCategory($category->id)->with(['author', 'organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $blogs = $query->paginate(12)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        // Get other categories
        $categories = BlogCategory::active()->where('id', '!=', $category->id)->ordered()->get();

        return view('client.blog.category', compact('blogs', 'category', 'organizations', 'categories'));
    }

    /**
     * Display blogs by tag.
     */
    public function tagged(Request $request, $tag)
    {
        $query = Blog::published()->with(['author', 'organization', 'category']);

        // Filter by tag
        $query->whereJsonContains('tags', $tag);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $blogs = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = BlogCategory::active()->ordered()->get();

        return view('client.blog.tagged', compact('blogs', 'tag', 'categories'));
    }

    /**
     * Display public blogs (for non-authenticated users).
     */
    public function publicIndex(Request $request)
    {
        $query = Blog::published()->featured()->with(['author', 'organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $blogs = $query->orderBy('published_at', 'desc')->paginate(9)->withQueryString();

        // Get categories for filter
        $categories = BlogCategory::active()->ordered()->get();

        return view('client.blog.public', compact('blogs', 'categories'));
    }

    /**
     * Display public blog article.
     */
    public function publicShow(Blog $blog)
    {
        // Check if blog is published and featured (for public access)
        if (!$blog->isPublished() || !$blog->isFeatured()) {
            abort(404);
        }

        // Increment views
        $blog->incrementViews();

        // Load relationships
        $blog->load(['author', 'organization', 'category']);

        // Get related featured blogs
        $relatedBlogs = Blog::published()
                          ->featured()
                          ->where('id', '!=', $blog->id)
                          ->when($blog->category_id, function($query) use ($blog) {
                              $query->where('category_id', $blog->category_id);
                          })
                          ->take(3)
                          ->get();

        return view('client.blog.public-show', compact('blog', 'relatedBlogs'));
    }

    /**
     * Display blogs by author.
     */
    public function author(Request $request, $authorId)
    {
        $query = Blog::published()->byAuthor($authorId)->with(['author', 'organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $blogs = $query->paginate(12)->withQueryString();

        // Get author info
        $author = \App\Models\User::findOrFail($authorId);

        // Get filter options
        $categories = BlogCategory::active()->ordered()->get();

        return view('client.blog.author', compact('blogs', 'author', 'categories'));
    }

    /**
     * Display blogs by organization.
     */
    public function organization(Request $request, $organizationId)
    {
        $query = Blog::published()->byOrganization($organizationId)->with(['author', 'organization', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $blogs = $query->paginate(12)->withQueryString();

        // Get organization info
        $organization = Organization::findOrFail($organizationId);

        // Get filter options
        $categories = BlogCategory::active()->ordered()->get();

        return view('client.blog.organization', compact('blogs', 'organization', 'categories'));
    }

    /**
     * Get blog archive by year/month.
     */
    public function archive(Request $request, $year, $month = null)
    {
        $query = Blog::published()->with(['author', 'organization', 'category']);

        // Filter by year and month
        $query->whereYear('published_at', $year);
        if ($month) {
            $query->whereMonth('published_at', $month);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $blogs = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = BlogCategory::active()->ordered()->get();

        // Get archive info
        $archiveDate = $month ? 
            \Carbon\Carbon::createFromDate($year, $month, 1) : 
            \Carbon\Carbon::createFromDate($year, 1, 1);

        return view('client.blog.archive', compact('blogs', 'categories', 'archiveDate', 'year', 'month'));
    }

    /**
     * Search blogs with advanced filters.
     */
    public function search(Request $request)
    {
        $query = Blog::published()->with(['author', 'organization', 'category']);

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

        if ($request->filled('author_id')) {
            $query->where('author_id', $request->author_id);
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        if ($request->filled('date_from')) {
            $query->where('published_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('published_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $blogs = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = BlogCategory::active()->ordered()->get();
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();

        return view('client.blog.search', compact('blogs', 'categories', 'organizations'));
    }
}
