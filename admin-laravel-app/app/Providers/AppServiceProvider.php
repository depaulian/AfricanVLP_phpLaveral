<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Services\CacheService;
use App\Observers\ContentCacheObserver;
use App\Models\Slider;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\News;
use App\Models\Event;
use App\Models\Resource;
use App\Models\Blog;
use App\Models\Organization;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind CacheService as singleton for dependency injection
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL compatibility
        Schema::defaultStringLength(191);

        // Register content cache observer for homepage-related models
        Slider::observe(ContentCacheObserver::class);
        Page::observe(ContentCacheObserver::class);
        PageSection::observe(ContentCacheObserver::class);
        News::observe(ContentCacheObserver::class);
        Event::observe(ContentCacheObserver::class);
        Resource::observe(ContentCacheObserver::class);
        Blog::observe(ContentCacheObserver::class);
        Organization::observe(ContentCacheObserver::class);
    }
}