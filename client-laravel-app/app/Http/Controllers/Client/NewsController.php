<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Organization;
use App\Models\Region;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of news for authenticated users.
     */
    public function index(Request $request)
    {
        $query = News::published()->with(['organization', 'region']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Organization filter
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Region filter
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // Featured filter
        if ($request->filled('featured')) {
            $query->featured();
        }

        // Sort
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $news = $query->paginate(12)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();
        $regions = Region::orderBy('name')->get();

        // Get featured news for sidebar
        $featuredNews = News::published()->featured()->take(5)->get();

        return view('client.news.index', compact('news', 'organizations', 'regions', 'featuredNews'));
    }

    /**
     * Display the specified news article.
     */
    public function show(News $news)
    {
        // Check if news is published
        if (!$news->isPublished()) {
            abort(404);
        }

        // Increment views
        $news->incrementViews();

        // Load relationships
        $news->load(['organization', 'region']);

        // Get related news
        $relatedNews = News::published()
                          ->where('id', '!=', $news->id)
                          ->when($news->organization_id, function($query) use ($news) {
                              $query->where('organization_id', $news->organization_id);
                          })
                          ->take(4)
                          ->get();

        return view('client.news.show', compact('news', 'relatedNews'));
    }

    /**
     * Display news by tag.
     */
    public function tagged(Request $request, $tag)
    {
        // This would require a tags system implementation
        // For now, we'll redirect to index with search
        return redirect()->route('news.index', ['search' => $tag]);
    }

    /**
     * Display public news (for non-authenticated users).
     */
    public function publicIndex(Request $request)
    {
        $query = News::published()->with(['organization', 'region']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Only show featured news for public
        $query->featured();

        $news = $query->orderBy('published_at', 'desc')->paginate(9)->withQueryString();

        return view('client.news.public', compact('news'));
    }

    /**
     * Display public news article.
     */
    public function publicShow(News $news)
    {
        // Check if news is published and featured (for public access)
        if (!$news->isPublished() || !$news->isFeatured()) {
            abort(404);
        }

        // Increment views
        $news->incrementViews();

        // Load relationships
        $news->load(['organization', 'region']);

        // Get related featured news
        $relatedNews = News::published()
                          ->featured()
                          ->where('id', '!=', $news->id)
                          ->take(3)
                          ->get();

        return view('client.news.public-show', compact('news', 'relatedNews'));
    }
}