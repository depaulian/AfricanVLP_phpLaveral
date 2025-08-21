<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'author_id',
        'organization_id',
        'category_id',
        'status',
        'featured',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'tags',
        'views_count',
        'likes_count',
        'comments_count',
        'language',
        'reading_time'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'featured' => 'boolean',
        'tags' => 'array',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'reading_time' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    // Boot method to generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
            
            // Calculate reading time (average 200 words per minute)
            if ($blog->content) {
                $wordCount = str_word_count(strip_tags($blog->content));
                $blog->reading_time = max(1, ceil($wordCount / 200));
            }
        });

        static::updating(function ($blog) {
            if ($blog->isDirty('title') && empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
            
            // Recalculate reading time if content changed
            if ($blog->isDirty('content') && $blog->content) {
                $wordCount = str_word_count(strip_tags($blog->content));
                $blog->reading_time = max(1, ceil($wordCount / 200));
            }
        });
    }

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class);
    }

    public function likes()
    {
        return $this->hasMany(BlogLike::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('meta_keywords', 'like', "%{$search}%");
        });
    }

    // Helper methods
    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at <= now();
    }

    public function isFeatured()
    {
        return $this->featured;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getFeaturedImageUrl()
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }
        return asset('images/default-blog.jpg');
    }

    public function getExcerpt($length = 150)
    {
        if ($this->excerpt) {
            return Str::limit($this->excerpt, $length);
        }
        
        return Str::limit(strip_tags($this->content), $length);
    }

    public function getReadingTimeAttribute($value)
    {
        return $value ?: 1;
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function incrementLikes()
    {
        $this->increment('likes_count');
    }

    public function incrementComments()
    {
        $this->increment('comments_count');
    }

    public function decrementComments()
    {
        $this->decrement('comments_count');
    }

    // URL helpers
    public function getUrl()
    {
        return route('blog.show', $this->slug);
    }

    public function getAdminUrl()
    {
        return route('admin.blogs.show', $this->id);
    }
}
