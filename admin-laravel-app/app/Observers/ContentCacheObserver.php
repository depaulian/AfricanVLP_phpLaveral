<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\Event;
use App\Models\News;
use App\Models\Organization;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Resource;
use App\Models\Slider;
use Illuminate\Support\Facades\Cache;

class ContentCacheObserver
{
    /**
     * Handle the model "saved" event.
     */
    public function saved($model): void
    {
        $this->flushTagsFor($model);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted($model): void
    {
        $this->flushTagsFor($model);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored($model): void
    {
        $this->flushTagsFor($model);
    }

    /**
     * Handle the model "forceDeleted" event.
     */
    public function forceDeleted($model): void
    {
        $this->flushTagsFor($model);
    }

    private function flushTagsFor($model): void
    {
        $tags = ['homepage'];
        $keys = ['homepage_data'];

        if ($model instanceof Slider) {
            $tags = array_merge($tags, ['sliders']);
            $keys = array_merge($keys, ['homepage_sliders']);
        } elseif ($model instanceof Page || $model instanceof PageSection) {
            $tags = array_merge($tags, ['pages', 'sections']);
            // Home page sections
            $keys = array_merge($keys, ['page_sections_home']);
        } elseif ($model instanceof News) {
            $tags = array_merge($tags, ['featured', 'content']);
            $keys = array_merge($keys, ['homepage_featured_content']);
        } elseif ($model instanceof Event) {
            $tags = array_merge($tags, ['featured', 'content', 'statistics']);
            $keys = array_merge($keys, ['homepage_featured_content', 'homepage_statistics']);
        } elseif ($model instanceof Resource) {
            $tags = array_merge($tags, ['featured', 'content']);
            $keys = array_merge($keys, ['homepage_featured_content']);
        } elseif ($model instanceof Blog) {
            $tags = array_merge($tags, ['featured', 'content']);
            $keys = array_merge($keys, ['homepage_featured_content']);
        } elseif ($model instanceof Organization) {
            $tags = array_merge($tags, ['featured', 'content', 'statistics']);
            $keys = array_merge($keys, ['homepage_featured_content', 'homepage_statistics']);
        }

        // Flush the related cache tags
        try {
            Cache::tags(array_unique($tags))->flush();
        } catch (\Throwable $e) {
            // Silently ignore if cache store doesn't support tags in some environments
        }

        // Fallback: also forget specific keys (works with non-taggable stores like 'file')
        $keys = array_unique($keys);
        foreach ($keys as $key) {
            try {
                Cache::forget($key);
            } catch (\Throwable $e) {
                // Ignore cache driver issues
            }
        }
    }
}
