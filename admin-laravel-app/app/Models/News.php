<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'organization_id',
        'region_id',
        'title',
        'description',
        'content',
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
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

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
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get excerpt of content.
     */
    public function getExcerptAttribute(): string
    {
        return strlen($this->description) > 150 
            ? substr($this->description, 0, 150) . '...' 
            : $this->description;
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
}