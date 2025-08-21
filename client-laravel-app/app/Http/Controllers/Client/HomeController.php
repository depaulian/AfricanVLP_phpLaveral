<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\News;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Resource;
use App\Models\Blog;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Display the homepage.
     */
    public function index()
    {
        // Get homepage data with caching
        $data = $this->cacheService->remember(
            'homepage_data',
            CacheService::MEDIUM_TTL,
            function () {
                return $this->getHomepageData();
            },
            ['homepage', 'sliders', 'pages']
        );

        return view('client.home.index', $data);
    }

    /**
     * Get sliders for homepage via AJAX.
     */
    public function getSliders()
    {
        $sliders = $this->cacheService->remember(
            'homepage_sliders',
            CacheService::LONG_TTL,
            function () {
                return $this->getActiveSliders();
            },
            ['sliders']
        );

        return response()->json([
            'success' => true,
            'sliders' => $sliders
        ]);
    }

    /**
     * Get page sections for homepage.
     */
    public function getPageSections(Request $request)
    {
        $pageSlug = $request->input('page', 'home');
        
        $sections = $this->cacheService->remember(
            "page_sections_{$pageSlug}",
            CacheService::LONG_TTL,
            function () use ($pageSlug) {
                return $this->getPageSectionsData($pageSlug);
            },
            ['pages', 'sections']
        );

        return response()->json([
            'success' => true,
            'sections' => $sections
        ]);
    }

    /**
     * Get featured content for homepage.
     */
    public function getFeaturedContent()
    {
        $content = $this->cacheService->remember(
            'homepage_featured_content',
            CacheService::MEDIUM_TTL,
            function () {
                return $this->getFeaturedContentData();
            },
            ['featured', 'content']
        );

        return response()->json([
            'success' => true,
            'content' => $content
        ]);
    }

    /**
     * Get statistics for homepage.
     */
    public function getStatistics()
    {
        $stats = $this->cacheService->remember(
            'homepage_statistics',
            CacheService::MEDIUM_TTL,
            function () {
                return $this->getHomepageStatistics();
            },
            ['statistics']
        );

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Get all homepage data.
     */
    private function getHomepageData(): array
    {
        return [
            'sliders' => $this->getActiveSliders(),
            'page_sections' => $this->getPageSectionsData('home'),
            'featured_content' => $this->getFeaturedContentData(),
            'statistics' => $this->getHomepageStatistics(),
            'recent_news' => $this->getRecentNews(),
            'upcoming_events' => $this->getUpcomingEvents(),
            'featured_organizations' => $this->getFeaturedOrganizations(),
        ];
    }

    /**
     * Get active sliders for homepage.
     */
    private function getActiveSliders(): Collection
    {
        // Get homepage
        $homepage = Page::where('slug', 'home')
            ->where('status', 'published')
            ->first();

        if (!$homepage) {
            return collect();
        }

        $sliders = Slider::where('page_id', $homepage->id)
            ->where('status', 'active')
            ->ordered()
            ->get([
                'id', 'title', 'subtitle', 'description', 'image_url',
                'link_url', 'link_text', 'position', 'show_overlay',
                'text_position', 'animation_type'
            ]);

        // Map DB fields to the names expected by the Blade view
        return $sliders->map(function ($slider) {
            // Provide aliases without losing original fields
            $slider->image = $slider->image_url;
            $slider->button_text = $slider->link_text;
            $slider->button_url = $slider->link_url;
            return $slider;
        });
    }

    /**
     * Get page sections data.
     */
    private function getPageSectionsData(string $pageSlug): Collection
    {
        $page = Page::where('slug', $pageSlug)
            ->where('status', 'published')
            ->first();

        if (!$page) {
            return collect();
        }

        $sections = PageSection::where('page_id', $page->id)
            ->where('status', 'active')
            ->ordered()
            ->get([
                'id', 'title', 'content', 'section_type', 'position',
                'background_image', 'background_color', 'text_color',
                'css_classes', 'custom_html', 'image', 'settings'
            ]);

        // Normalize fields for the Blade view
        return $sections->map(function ($section) {
            // If the table uses background_image instead of image
            if (empty($section->image) && !empty($section->background_image)) {
                $section->image = $section->background_image;
            }
            // Ensure settings is an array if it's JSON or null
            if (isset($section->settings) && is_string($section->settings)) {
                $decoded = json_decode($section->settings, true);
                $section->settings = is_array($decoded) ? $decoded : [];
            } elseif (!isset($section->settings) || is_null($section->settings)) {
                $section->settings = [];
            }
            return $section;
        });
    }

    /**
     * Get featured content data.
     */
    private function getFeaturedContentData(): array
    {
        $data = [
            'news' => News::where('status', 'published')
                ->where('featured', true)
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get(['id', 'title', 'excerpt', 'featured_image', 'slug', 'published_at']),
            
            'events' => Event::where('status', 'active')
                ->where('featured', true)
                ->where('start_date', '>', now())
                ->orderBy('start_date')
                ->limit(3)
                ->get(['id', 'title', 'description', 'start_date', 'location', 'slug']),
            
            'resources' => Resource::where('status', 'published')
                ->where('featured', true)
                ->orderBy('views', 'desc')
                ->limit(3)
                ->get(['id', 'title', 'description', 'slug', 'views', 'downloads']),

            'blogs' => Blog::where('status', 'published')
                ->where('featured', true)
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get(['id', 'title', 'content', 'featured_image', 'slug', 'published_at']),
        ];

        // Also include featured organizations so the Blade can render them under featured_content
        $data['organizations'] = $this->getFeaturedOrganizations();

        return $data;
    }

    /**
     * Get homepage statistics.
     */
    private function getHomepageStatistics(): array
    {
        $totalUsers = \App\Models\User::where('status', 'active')->count();
        $totalOrganizations = Organization::where('status', 'active')->count();
        $totalEvents = Event::where('status', 'active')->count();
        $totalResources = Resource::where('status', 'published')->count();
        $upcomingEvents = Event::where('status', 'active')->where('start_date', '>', now())->count();
        $activeVolunteers = \App\Models\User::where('status', 'active')->whereNotNull('volunteering_interests')->count();

        // Preserve original keys and add the ones expected by the Blade
        return [
            // Original keys
            'total_users' => $totalUsers,
            'total_organizations' => $totalOrganizations,
            'total_events' => $totalEvents,
            'total_resources' => $totalResources,
            'upcoming_events' => $upcomingEvents,
            'active_volunteers' => $activeVolunteers,

            // Keys used by the Blade view
            'volunteers' => $totalUsers, // or $activeVolunteers
            'organizations' => $totalOrganizations,
            'countries' => 54, // static unless you track per-country data
            'projects' => $totalResources, // treat resources as projects for the homepage metric
        ];
    }

    /**
     * Get recent news.
     */
    private function getRecentNews()
    {
        return News::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(6)
            ->get([
                'id', 'title', 'excerpt', 'featured_image', 'slug',
                'published_at', 'views'
            ]);
    }

    /**
     * Get upcoming events.
     */
    private function getUpcomingEvents()
    {
        return Event::where('status', 'active')
            ->where('start_date', '>', now())
            ->orderBy('start_date')
            ->limit(6)
            ->with(['organization:id,name,logo'])
            ->get([
                'id', 'title', 'description', 'start_date', 'end_date',
                'location', 'organization_id', 'slug', 'max_participants'
            ]);
    }

    /**
     * Get featured organizations.
     */
    private function getFeaturedOrganizations()
    {
        return Organization::where('status', 'active')
            ->where('featured', true)
            ->withCount(['users', 'events'])
            ->orderBy('users_count', 'desc')
            ->limit(6)
            ->get([
                'id', 'name', 'description', 'logo', 'category',
                'location', 'slug'
            ]);
    }

    /**
     * Search homepage content.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:all,news,events,organizations,resources'
        ]);

        $query = $request->input('q');
        $type = $request->input('type', 'all');

        $results = $this->cacheService->remember(
            "homepage_search_{$type}_" . md5($query),
            CacheService::SHORT_TTL,
            function () use ($query, $type) {
                return $this->performSearch($query, $type);
            },
            ['search']
        );

        return response()->json([
            'success' => true,
            'query' => $query,
            'type' => $type,
            'results' => $results
        ]);
    }

    /**
     * Perform search across different content types.
     */
    private function performSearch(string $query, string $type): array
    {
        $results = [];

        if ($type === 'all' || $type === 'news') {
            $results['news'] = News::where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('published_at', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'excerpt', 'slug', 'published_at']);
        }

        if ($type === 'all' || $type === 'events') {
            $results['events'] = Event::where('status', 'active')
                ->where('start_date', '>', now())
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->orderBy('start_date')
                ->limit(5)
                ->get(['id', 'title', 'description', 'start_date', 'location', 'slug']);
        }

        if ($type === 'all' || $type === 'organizations') {
            $results['organizations'] = Organization::where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'description', 'logo', 'category', 'slug']);
        }

        if ($type === 'all' || $type === 'resources') {
            $results['resources'] = Resource::where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->orderBy('views', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'description', 'slug', 'views']);
        }

        return $results;
    }
}