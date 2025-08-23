<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Boot method to generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
                
                // Ensure unique slug
                $originalSlug = $category->slug;
                $count = 1;
                while (static::where('slug', $category->slug)->exists()) {
                    $category->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
                
                // Ensure unique slug
                $originalSlug = $category->slug;
                $count = 1;
                while (static::where('slug', $category->slug)->where('id', '!=', $category->id)->exists()) {
                    $category->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });
    }

    /**
     * Get the news articles in this category.
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'news_category_id');
    }

    /**
     * Get published news articles in this category.
     */
    public function publishedNews(): HasMany
    {
        return $this->news()->where('status', 'published')->where('published_at', '<=', now());
    }

    /**
     * Get featured news articles in this category.
     */
    public function featuredNews(): HasMany
    {
        return $this->news()->where('is_featured', true);
    }

    /**
     * Check if this category is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the route key name for model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the category URL.
     */
    public function getUrl(): string
    {
        return route('news.category', $this->slug);
    }

    /**
     * Get the admin URL.
     */
    public function getAdminUrl(): string
    {
        return route('admin.news-categories.show', $this->id);
    }

    /**
     * Get the icon URL or return a default icon.
     */
    public function getIconUrl(): string
    {
        if ($this->icon) {
            return asset('storage/icons/' . $this->icon);
        }
        return asset('images/default-news-category-icon.svg');
    }

    /**
     * Get the color or return a default color.
     */
    public function getColor(): string
    {
        return $this->color ?: '#6c757d';
    }

    /**
     * Get news count for this category.
     */
    public function getNewsCount(): int
    {
        return $this->news()->count();
    }

    /**
     * Get published news count for this category.
     */
    public function getPublishedNewsCount(): int
    {
        return $this->publishedNews()->count();
    }

    /**
     * Get the latest news from this category.
     */
    public function getLatestNews(int $limit = 5)
    {
        return $this->publishedNews()
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeWithNewsCount($query)
    {
        return $query->withCount('news');
    }

    public function scopeWithPublishedNewsCount($query)
    {
        return $query->withCount(['news as published_news_count' => function($q) {
            $q->where('status', 'published')->where('published_at', '<=', now());
        }]);
    }

    /**
     * Get category badge HTML for UI display.
     */
    public function getBadgeHtml(): string
    {
        $color = $this->getColor();
        return "<span class='badge' style='background-color: {$color}; color: white;'>{$this->name}</span>";
    }

    /**
     * Get category with icon for UI display.
     */
    public function getDisplayName(): string
    {
        if ($this->icon) {
            return "<i class='{$this->icon}'></i> {$this->name}";
        }
        return $this->name;
    }
}