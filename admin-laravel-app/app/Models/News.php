<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class News extends Model
{
    protected $fillable = [
        'organization_id',
        'region_id',
        'news_category_id',
        'title',
        'slug',
        'excerpt',
        'description',
        'content',
        'featured_image',
        'image',
        'status',
        'is_featured',
        'views_count',
        'published_at',
        'author',
        'source',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
    ];

    // Boot method to generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title);
                
                // Ensure unique slug
                $originalSlug = $news->slug;
                $count = 1;
                while (static::where('slug', $news->slug)->exists()) {
                    $news->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty('title') && empty($news->slug)) {
                $news->slug = Str::slug($news->title);
                
                // Ensure unique slug
                $originalSlug = $news->slug;
                $count = 1;
                while (static::where('slug', $news->slug)->where('id', '!=', $news->id)->exists()) {
                    $news->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });
    }

    /**
     * Get the organization that owns the news.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the region associated with the news.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the news category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    /**
     * Check if the news is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at <= now();
    }

    /**
     * Check if the news is featured.
     */
    public function isFeatured(): bool
    {
        return $this->is_featured === true;
    }

    /**
     * Get the news image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/news/' . $this->image) : null;
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageUrl(): string
    {
        if ($this->featured_image) {
            return asset('storage/news/' . $this->featured_image);
        }
        if ($this->image) {
            return asset('storage/news/' . $this->image);
        }
        return asset('images/default-news.jpg');
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get excerpt of content.
     */
    public function getExcerpt($length = 150): string
    {
        if ($this->excerpt) {
            return strlen($this->excerpt) > $length 
                ? substr($this->excerpt, 0, $length) . '...' 
                : $this->excerpt;
        }
        
        if ($this->description) {
            return strlen($this->description) > $length 
                ? substr($this->description, 0, $length) . '...' 
                : $this->description;
        }
        
        return strlen($this->content) > $length 
            ? substr(strip_tags($this->content), 0, $length) . '...' 
            : strip_tags($this->content);
    }

    /**
     * Get excerpt attribute (for backwards compatibility).
     */
    public function getExcerptAttribute(): string
    {
        return $this->getExcerpt();
    }

    /**
     * Scope to get published news.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope to get featured news.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the route key name for model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the news URL.
     */
    public function getUrl()
    {
        return route('news.show', $this->slug);
    }

    /**
     * Get the admin URL.
     */
    public function getAdminUrl()
    {
        return route('admin.news.show', $this->id);
    }

    /**
     * Scope to get news for user's organizations.
     */
    public function scopeForUserOrganizations($query, $organizationIds)
    {
        return $query->whereIn('organization_id', $organizationIds)
                    ->orWhereNull('organization_id');
    }

    /**
     * Scope to get news by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('news_category_id', $categoryId);
    }
}