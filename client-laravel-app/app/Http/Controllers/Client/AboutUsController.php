<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\CacheService;
use Illuminate\Support\Collection;

class AboutUsController extends Controller
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Display the about us page.
     */
    public function index()
    {
        // Get about page data with caching
        $data = $this->cacheService->remember(
            'about_page_data',
            CacheService::LONG_TTL,
            function () {
                return $this->getAboutPageData();
            },
            ['about', 'pages']
        );

        return view('client.about.index', $data);
    }

    /**
     * Get about page data.
     */
    private function getAboutPageData(): array
    {
        return [
            'page_sections' => $this->getPageSectionsData('about'),
            'statistics' => $this->getAboutStatistics(),
        ];
    }

    /**
     * Get page sections for about page.
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
                'id', 'section_type', 'title', 'subtitle', 'content', 
                'image_url', 'settings', 'position', 'status'
            ]);

        // Normalize fields for the Blade view
        return $sections->map(function ($section) {
            $section->image = $section->image_url ?? null;
            
            $settings = [];
            if (isset($section->settings) && is_string($section->settings)) {
                $decoded = json_decode($section->settings, true);
                $settings = is_array($decoded) ? $decoded : [];
            } elseif (isset($section->settings) && is_array($section->settings)) {
                $settings = $section->settings;
            }
            
            $section->settings = $settings;
            
            return $section;
        });
    }

    /**
     * Get statistics for about page.
     */
    private function getAboutStatistics(): array
    {
        return [
            'volunteers' => \App\Models\User::where('status', 'active')->count(),
            'organizations' => \App\Models\Organization::where('status', 'active')->count(),
            'countries' => 55, // AU member states
            'projects' => \App\Models\Resource::where('status', 'published')->count(),
        ];
    }
}