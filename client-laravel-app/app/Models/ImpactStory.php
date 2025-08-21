<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ImpactStory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'author_id',
        'volunteer_id',
        'organization_id',
        'featured_volunteers',
        'impact_metrics',
        'featured_image',
        'gallery',
        'tags',
        'story_type',
        'story_date',
        'location',
        'beneficiary_info',
        'is_published',
        'is_featured',
        'allow_comments',
        'views_count',
        'likes_count',
        'shares_count',
        'published_at',
        'published_by',
        'seo_data',
    ];

    protected $casts = [
        'featured_volunteers' => 'array',
        'impact_metrics' => 'array',
        'gallery' => 'array',
        'tags' => 'array',
        'story_date' => 'date',
        'beneficiary_info' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'shares_count' => 'integer',
        'published_at' => 'datetime',
        'seo_data' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($story) {
            if (empty($story->slug)) {
                $story->slug = Str::slug($story->title);
            }
        });

        static::updating(function ($story) {
            if ($story->isDirty('title') && empty($story->slug)) {
                $story->slug = Str::slug($story->title);
            }
        });
    }

    /**
     * Get the author of the story
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the featured volunteer
     */
    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'volunteer_id');
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who published the story
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Scope for published stories
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for featured stories
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for stories by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('story_type', $type);
    }

    /**
     * Scope for recent stories
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('story_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for popular stories
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('views_count')
                    ->orderByDesc('likes_count')
                    ->limit($limit);
    }

    /**
     * Get route key name for model binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get story type display name
     */
    public function getStoryTypeDisplayAttribute(): string
    {
        return match ($this->story_type) {
            'success' => 'Success Story',
            'challenge' => 'Overcoming Challenge',
            'innovation' => 'Innovation Story',
            'milestone' => 'Milestone Achievement',
            'testimonial' => 'Testimonial',
            default => ucfirst($this->story_type) . ' Story',
        };
    }

    /**
     * Get story type icon
     */
    public function getStoryTypeIconAttribute(): string
    {
        return match ($this->story_type) {
            'success' => 'fas fa-trophy',
            'challenge' => 'fas fa-mountain',
            'innovation' => 'fas fa-lightbulb',
            'milestone' => 'fas fa-flag-checkered',
            'testimonial' => 'fas fa-quote-left',
            default => 'fas fa-book',
        };
    }

    /**
     * Get story type color
     */
    public function getStoryTypeColorAttribute(): string
    {
        return match ($this->story_type) {
            'success' => 'text-green-600',
            'challenge' => 'text-orange-600',
            'innovation' => 'text-blue-600',
            'milestone' => 'text-purple-600',
            'testimonial' => 'text-indigo-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Get reading time estimate
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
    }

    /**
     * Get excerpt from content
     */
    public function getExcerptAttribute(): string
    {
        if ($this->summary) {
            return $this->summary;
        }

        $content = strip_tags($this->content);
        return strlen($content) > 200 
            ? substr($content, 0, 200) . '...'
            : $content;
    }

    /**
     * Get featured image URL
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($this->featured_image, FILTER_VALIDATE_URL)) {
            return $this->featured_image;
        }

        // Otherwise, assume it's a storage path
        return asset('storage/' . $this->featured_image);
    }

    /**
     * Get gallery URLs
     */
    public function getGalleryUrlsAttribute(): array
    {
        if (!$this->gallery) {
            return [];
        }

        return array_map(function ($image) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }
            return asset('storage/' . $image);
        }, $this->gallery);
    }

    /**
     * Check if story can be published
     */
    public function canBePublished(): bool
    {
        return !$this->is_published && 
               !empty($this->title) && 
               !empty($this->content) && 
               !empty($this->summary);
    }

    /**
     * Publish the story
     */
    public function publish(User $publisher): bool
    {
        if (!$this->canBePublished()) {
            return false;
        }

        $this->update([
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $publisher->id,
        ]);

        return true;
    }

    /**
     * Unpublish the story
     */
    public function unpublish(): bool
    {
        if (!$this->is_published) {
            return false;
        }

        $this->update([
            'is_published' => false,
            'published_at' => null,
            'published_by' => null,
        ]);

        return true;
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment likes count
     */
    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    /**
     * Increment shares count
     */
    public function incrementShares(): void
    {
        $this->increment('shares_count');
    }

    /**
     * Get time since story date
     */
    public function getTimeSinceStoryAttribute(): string
    {
        return $this->story_date->diffForHumans();
    }

    /**
     * Get time since published
     */
    public function getTimeSincePublishedAttribute(): ?string
    {
        return $this->published_at?->diffForHumans();
    }

    /**
     * Check if story is recent
     */
    public function isRecent(int $days = 7): bool
    {
        return $this->story_date->isAfter(now()->subDays($days));
    }

    /**
     * Get SEO title
     */
    public function getSeoTitleAttribute(): string
    {
        return $this->seo_data['title'] ?? $this->title;
    }

    /**
     * Get SEO description
     */
    public function getSeoDescriptionAttribute(): string
    {
        return $this->seo_data['description'] ?? $this->excerpt;
    }

    /**
     * Get SEO keywords
     */
    public function getSeoKeywordsAttribute(): array
    {
        $keywords = $this->seo_data['keywords'] ?? [];
        
        // Add tags as keywords if no SEO keywords are set
        if (empty($keywords) && $this->tags) {
            $keywords = $this->tags;
        }

        return $keywords;
    }

    /**
     * Get related impact metrics data
     */
    public function getImpactMetricsDataAttribute(): array
    {
        if (!$this->impact_metrics) {
            return [];
        }

        $metricsData = [];
        foreach ($this->impact_metrics as $metricData) {
            if (isset($metricData['metric_id'])) {
                $metric = ImpactMetric::find($metricData['metric_id']);
                if ($metric) {
                    $metricsData[] = [
                        'metric' => $metric,
                        'value' => $metricData['value'] ?? 0,
                        'formatted_value' => $metric->formatValue($metricData['value'] ?? 0),
                    ];
                }
            }
        }

        return $metricsData;
    }

    /**
     * Get featured volunteers data
     */
    public function getFeaturedVolunteersDataAttribute(): array
    {
        if (!$this->featured_volunteers) {
            return [];
        }

        $volunteers = [];
        foreach ($this->featured_volunteers as $volunteerId) {
            $volunteer = User::find($volunteerId);
            if ($volunteer) {
                $volunteers[] = $volunteer;
            }
        }

        return $volunteers;
    }

    /**
     * Get story URL
     */
    public function getUrlAttribute(): string
    {
        return route('client.volunteering.impact.story', $this->slug);
    }

    /**
     * Get share URL for social media
     */
    public function getShareUrlAttribute(): string
    {
        return $this->url;
    }

    /**
     * Get engagement score (for sorting/ranking)
     */
    public function getEngagementScoreAttribute(): float
    {
        return ($this->views_count * 1) + 
               ($this->likes_count * 5) + 
               ($this->shares_count * 10);
    }
}