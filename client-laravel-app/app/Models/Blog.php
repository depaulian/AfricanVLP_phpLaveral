<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'is_featured',
        'reading_time',
        'views_count',
        'likes_count',
        'shares_count',
        'published_at',
        'scheduled_at',
        'author_id',
        'organization_id',
        'blog_category_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'shares_count' => 'integer',
        'reading_time' => 'integer'
    ];

    // Boot method to generate slug and calculate reading time
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($blog) {
            // Generate slug if not provided
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
                
                // Ensure unique slug
                $originalSlug = $blog->slug;
                $count = 1;
                while (static::where('slug', $blog->slug)->exists()) {
                    $blog->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
            
            // Calculate reading time (average 200 words per minute)
            if ($blog->content && !$blog->reading_time) {
                $wordCount = str_word_count(strip_tags($blog->content));
                $blog->reading_time = max(1, ceil($wordCount / 200));
            }
        });

        static::updating(function ($blog) {
            // Regenerate slug if title changed and slug is empty
            if ($blog->isDirty('title') && empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
                
                // Ensure unique slug
                $originalSlug = $blog->slug;
                $count = 1;
                while (static::where('slug', $blog->slug)->where('id', '!=', $blog->id)->exists()) {
                    $blog->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
            
            // Recalculate reading time if content changed
            if ($blog->isDirty('content') && $blog->content) {
                $wordCount = str_word_count(strip_tags($blog->content));
                $blog->reading_time = max(1, ceil($wordCount / 200));
            }
        });
    }

    /**
     * Get the author of the blog.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the organization that owns the blog.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the blog category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * Get the comments for the blog.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    /**
     * Get the likes for the blog.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(BlogLike::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_at', '>', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('blog_category_id', $categoryId);
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->whereFullText(['title', 'content'], $search)
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('meta_keywords', 'like', "%{$search}%");
        });
    }

    // Status check methods
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at <= now();
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at > now();
    }

    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    // Route model binding
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Accessors and Helpers
    public function getFeaturedImageUrl(): string
    {
        if ($this->featured_image) {
            return asset('storage/blogs/' . $this->featured_image);
        }
        return asset('images/default-blog.jpg');
    }

    public function getExcerpt(int $length = 150): string
    {
        if ($this->excerpt) {
            return Str::limit($this->excerpt, $length);
        }
        
        return Str::limit(strip_tags($this->content), $length);
    }

    public function getReadingTime(): int
    {
        return $this->reading_time ?: 1;
    }

    // Counter methods
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    public function incrementShares(): void
    {
        $this->increment('shares_count');
    }

    // URL helpers
    public function getUrl(): string
    {
        return route('blogs.show', $this->slug);
    }

    public function getAdminUrl(): string
    {
        return route('admin.blogs.show', $this->id);
    }

    // Status helpers for UI
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'published' => 'badge-success',
            'draft' => 'badge-secondary',
            'scheduled' => 'badge-info',
            'archived' => 'badge-warning',
            default => 'badge-light'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'published' => 'Published',
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'archived' => 'Archived',
            default => 'Unknown'
        };
    }

    // Meta helpers
    public function getMetaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function getMetaDescription(): string
    {
        return $this->meta_description ?: $this->getExcerpt(160);
    }
}