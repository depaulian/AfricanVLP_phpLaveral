<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Slider;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Display a specific page by slug
     */
    public function show(string $slug): View
    {
        $page = $this->cacheService->remember(
            "page_{$slug}",
            CacheService::LONG_TTL,
            function () use ($slug) {
                return Page::with(['pageSections' => function ($query) {
                    $query->where('status', 'active')->orderBy('position');
                }, 'sliders' => function ($query) {
                    $query->where('status', 'active')->orderBy('position');
                }])
                ->where('slug', $slug)
                ->where('status', 'published')
                ->firstOrFail();
            },
            ['pages', "page_{$slug}"]
        );

        // Get additional data based on page type
        $additionalData = $this->getAdditionalPageData($page);

        return view($page->getTemplatePath(), array_merge([
            'page' => $page,
            'sections' => $page->pageSections,
            'sliders' => $page->sliders,
        ], $additionalData));
    }

    /**
     * Display the about page
     */
    public function about(): View
    {
        return $this->show('about');
    }

    /**
     * Display the contact page
     */
    public function contact(): View
    {
        return $this->show('contact');
    }

    /**
     * Display the privacy policy page
     */
    public function privacy(): View
    {
        return $this->show('privacy-policy');
    }

    /**
     * Display the terms of service page
     */
    public function terms(): View
    {
        return $this->show('terms-of-service');
    }

    /**
     * Get page sections via AJAX
     */
    public function getSections(string $slug): JsonResponse
    {
        $sections = $this->cacheService->remember(
            "page_sections_{$slug}",
            CacheService::LONG_TTL,
            function () use ($slug) {
                $page = Page::where('slug', $slug)
                    ->where('status', 'published')
                    ->first();

                if (!$page) {
                    return [];
                }

                return PageSection::where('page_id', $page->id)
                    ->where('status', 'active')
                    ->orderBy('position')
                    ->get();
            },
            ['pages', 'sections']
        );

        return response()->json([
            'success' => true,
            'sections' => $sections
        ]);
    }

    /**
     * Get page sliders via AJAX
     */
    public function getSliders(string $slug): JsonResponse
    {
        $sliders = $this->cacheService->remember(
            "page_sliders_{$slug}",
            CacheService::LONG_TTL,
            function () use ($slug) {
                $page = Page::where('slug', $slug)
                    ->where('status', 'published')
                    ->first();

                if (!$page) {
                    return [];
                }

                return Slider::where('page_id', $page->id)
                    ->where('status', 'active')
                    ->orderBy('position')
                    ->get();
            },
            ['pages', 'sliders']
        );

        return response()->json([
            'success' => true,
            'sliders' => $sliders
        ]);
    }

    /**
     * Search pages
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100'
        ]);

        $query = $request->input('q');

        $results = $this->cacheService->remember(
            "page_search_" . md5($query),
            CacheService::SHORT_TTL,
            function () use ($query) {
                return Page::where('status', 'published')
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'LIKE', "%{$query}%")
                          ->orWhere('content', 'LIKE', "%{$query}%")
                          ->orWhere('meta_description', 'LIKE', "%{$query}%");
                    })
                    ->orderBy('title')
                    ->limit(10)
                    ->get(['id', 'title', 'slug', 'meta_description']);
            },
            ['search', 'pages']
        );

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $results
        ]);
    }

    /**
     * Get sitemap data
     */
    public function sitemap(): JsonResponse
    {
        $pages = $this->cacheService->remember(
            'sitemap_pages',
            CacheService::LONG_TTL,
            function () {
                return Page::where('status', 'published')
                    ->orderBy('title')
                    ->get(['id', 'title', 'slug', 'modified']);
            },
            ['pages', 'sitemap']
        );

        return response()->json([
            'success' => true,
            'pages' => $pages->map(function ($page) {
                return [
                    'title' => $page->title,
                    'url' => $page->url,
                    'last_modified' => $page->modified->toISOString(),
                ];
            })
        ]);
    }

    /**
     * Get additional data based on page type/slug
     */
    protected function getAdditionalPageData(Page $page): array
    {
        $data = [];

        switch ($page->slug) {
            case 'about':
                $data = $this->getAboutPageData();
                break;
            case 'contact':
                $data = $this->getContactPageData();
                break;
            case 'home':
            case 'homepage':
                $data = $this->getHomepageData();
                break;
        }

        return $data;
    }

    /**
     * Get about page specific data
     */
    protected function getAboutPageData(): array
    {
        return $this->cacheService->remember(
            'about_page_data',
            CacheService::MEDIUM_TTL,
            function () {
                return [
                    'team_stats' => [
                        'total_users' => \App\Models\User::count(),
                        'total_organizations' => \App\Models\Organization::where('status', 'active')->count(),
                        'total_events' => \App\Models\Event::where('status', 'active')->count(),
                        'total_volunteers' => \App\Models\User::where('user_type', 'volunteer')->count(),
                    ],
                    'featured_organizations' => \App\Models\Organization::where('status', 'active')
                        ->where('featured', true)
                        ->limit(6)
                        ->get(['id', 'name', 'description', 'logo', 'slug']),
                ];
            },
            ['about', 'statistics']
        );
    }

    /**
     * Get contact page specific data
     */
    protected function getContactPageData(): array
    {
        return [
            'contact_info' => [
                'email' => config('app.contact_email', 'info@example.com'),
                'phone' => config('app.contact_phone', '+1234567890'),
                'address' => config('app.contact_address', '123 Main St, City, Country'),
                'social_media' => [
                    'facebook' => config('app.facebook_url'),
                    'twitter' => config('app.twitter_url'),
                    'linkedin' => config('app.linkedin_url'),
                    'instagram' => config('app.instagram_url'),
                ],
            ],
        ];
    }

    /**
     * Get homepage specific data
     */
    protected function getHomepageData(): array
    {
        return $this->cacheService->remember(
            'homepage_additional_data',
            CacheService::MEDIUM_TTL,
            function () {
                return [
                    'recent_news' => \App\Models\News::where('status', 'published')
                        ->orderBy('published_at', 'desc')
                        ->limit(3)
                        ->get(['id', 'title', 'excerpt', 'slug', 'published_at', 'featured_image']),
                    'upcoming_events' => \App\Models\Event::where('status', 'active')
                        ->where('start_date', '>', now())
                        ->orderBy('start_date')
                        ->limit(3)
                        ->get(['id', 'title', 'description', 'start_date', 'location', 'slug']),
                    'featured_organizations' => \App\Models\Organization::where('status', 'active')
                        ->where('featured', true)
                        ->limit(3)
                        ->get(['id', 'name', 'description', 'logo', 'slug']),
                ];
            },
            ['homepage', 'featured']
        );
    }

    /**
     * Handle contact form submission
     */
    public function submitContact(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        try {
            // Here you would typically send an email or store the contact message
            // For now, we'll just log it
            \Log::info('Contact form submission', [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // You could also create a ContactMessage model and store it in the database
            // ContactMessage::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message. We will get back to you soon!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sorry, there was an error sending your message. Please try again later.'
            ], 500);
        }
    }
}
