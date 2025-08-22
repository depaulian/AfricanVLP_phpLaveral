<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Auditable;

class ContentTag extends Model
{
    use HasFactory, SoftDeletes, Auditable;



    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'type',
        'parent_id',
        'sort_order',
        'is_active',
        'is_featured',
        'usage_count',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'usage_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Get the user who created this tag
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent tag
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ContentTag::class, 'parent_id');
    }

    /**
     * Get child tags
     */
    public function children(): HasMany
    {
        return $this->hasMany(ContentTag::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get tagged content items (polymorphic)
     */
    public function taggedContent()
    {
        return $this->hasMany(TaggedContent::class);
    }

    /**
     * Get users with this tag
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable', 'tagged_contents');
    }

    /**
     * Get organizations with this tag
     */
    public function organizations(): MorphToMany
    {
        return $this->morphedByMany(Organization::class, 'taggable', 'tagged_contents');
    }

    /**
     * Get events with this tag
     */
    public function events(): MorphToMany
    {
        return $this->morphedByMany(Event::class, 'taggable', 'tagged_contents');
    }

    /**
     * Get resources with this tag
     */
    public function resources(): MorphToMany
    {
        return $this->morphedByMany(Resource::class, 'taggable', 'tagged_contents');
    }

    /**
     * Get forum threads with this tag
     */
    public function forumThreads(): MorphToMany
    {
        return $this->morphedByMany(ForumThread::class, 'taggable', 'tagged_contents');
    }

    /**
     * Check if tag is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if tag is featured
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Check if tag has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if tag is a child (has parent)
     */
    public function isChild(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Get tag hierarchy path
     */
    public function getHierarchyPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get tag color with fallback
     */
    public function getDisplayColorAttribute(): string
    {
        return $this->color ?: $this->getDefaultColorForType();
    }

    /**
     * Get tag icon with fallback
     */
    public function getDisplayIconAttribute(): string
    {
        return $this->icon ?: $this->getDefaultIconForType();
    }

    /**
     * Get default color based on tag type
     */
    protected function getDefaultColorForType(): string
    {
        return match($this->type) {
            'category' => 'blue',
            'skill' => 'green',
            'interest' => 'purple',
            'location' => 'orange',
            'industry' => 'red',
            'topic' => 'yellow',
            'status' => 'gray',
            default => 'blue',
        };
    }

    /**
     * Get default icon based on tag type
     */
    protected function getDefaultIconForType(): string
    {
        return match($this->type) {
            'category' => 'folder',
            'skill' => 'award',
            'interest' => 'heart',
            'location' => 'map-pin',
            'industry' => 'briefcase',
            'topic' => 'message-circle',
            'status' => 'flag',
            default => 'tag',
        };
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement usage count
     */
    public function decrementUsage(): void
    {
        $this->decrement('usage_count');
    }

    /**
     * Get popular tags
     */
    public static function popular(int $limit = 10)
    {
        return static::where('is_active', true)
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get trending tags (popular in recent period)
     */
    public static function trending(int $days = 30, int $limit = 10)
    {
        return static::where('is_active', true)
            ->whereHas('taggedContent', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->withCount(['taggedContent as recent_usage' => function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            }])
            ->orderBy('recent_usage', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search tags by name
     */
    public static function search(string $query, int $limit = 20)
    {
        return static::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Scope for active tags
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured tags
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for tags by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for root tags (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for child tags (has parent)
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope for tags with usage above threshold
     */
    public function scopePopular($query, int $minUsage = 1)
    {
        return $query->where('usage_count', '>=', $minUsage);
    }

    /**
     * Scope for tags by parent
     */
    public function scopeByParent($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Common tag types
     */
    const TYPE_CATEGORY = 'category';
    const TYPE_SKILL = 'skill';
    const TYPE_INTEREST = 'interest';
    const TYPE_LOCATION = 'location';
    const TYPE_INDUSTRY = 'industry';
    const TYPE_TOPIC = 'topic';
    const TYPE_STATUS = 'status';

    /**
     * Get all available tag types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_CATEGORY,
            self::TYPE_SKILL,
            self::TYPE_INTEREST,
            self::TYPE_LOCATION,
            self::TYPE_INDUSTRY,
            self::TYPE_TOPIC,
            self::TYPE_STATUS,
        ];
    }

    /**
     * Get available colors
     */
    public static function getAvailableColors(): array
    {
        return [
            'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink',
            'gray', 'black', 'white', 'indigo', 'teal', 'cyan', 'lime',
        ];
    }

    /**
     * Create or find tag by name
     */
    public static function findOrCreate(string $name, array $attributes = []): self
    {
        $slug = Str::slug($name);
        
        $tag = static::where('slug', $slug)->first();
        
        if (!$tag) {
            $tag = static::create(array_merge([
                'name' => $name,
                'slug' => $slug,
                'type' => self::TYPE_TOPIC,
                'is_active' => true,
                'created_by' => auth()->id(),
            ], $attributes));
        }
        
        return $tag;
    }

    /**
     * Sync tags for a model
     */
    public static function syncForModel($model, array $tagNames): void
    {
        $tags = collect($tagNames)->map(function ($name) {
            return static::findOrCreate(trim($name));
        });

        // Remove old associations
        TaggedContent::where('taggable_type', get_class($model))
            ->where('taggable_id', $model->id)
            ->delete();

        // Create new associations
        foreach ($tags as $tag) {
            TaggedContent::create([
                'content_tag_id' => $tag->id,
                'taggable_type' => get_class($model),
                'taggable_id' => $model->id,
                'tagged_by' => auth()->id(),
            ]);
            
            $tag->incrementUsage();
        }
    }
}
