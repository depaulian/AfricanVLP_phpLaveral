<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResourceCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ResourceCategory::class, 'parent_id');
    }

    /**
     * Get active child categories ordered by sort_order.
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get all descendant categories (recursive).
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(ResourceCategory::class, 'parent_id')->with('descendants');
    }

    /**
     * Get the resources in this category.
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_category_pivot', 'category_id', 'resource_id');
    }

    /**
     * Check if this category is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if this category is a root category (has no parent).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the full path of the category (parent > child > grandchild).
     */
    public function getFullPath(): string
    {
        $path = [];
        $category = $this;
        
        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Get the depth level of this category.
     */
    public function getDepth(): int
    {
        $depth = 0;
        $category = $this->parent;
        
        while ($category) {
            $depth++;
            $category = $category->parent;
        }
        
        return $depth;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeWithChildren($query)
    {
        return $query->with(['children' => function($q) {
            $q->active()->ordered();
        }]);
    }

    /**
     * Get the icon URL or return a default icon.
     */
    public function getIconUrl(): string
    {
        if ($this->icon) {
            return asset('storage/icons/' . $this->icon);
        }
        return asset('images/default-category-icon.svg');
    }

    /**
     * Get the color or return a default color.
     */
    public function getColor(): string
    {
        return $this->color ?: '#6c757d';
    }

    /**
     * Get resources count for this category (including descendants).
     */
    public function getResourcesCount(): int
    {
        $count = $this->resources()->count();
        
        // Add resources from child categories
        foreach ($this->children as $child) {
            $count += $child->getResourcesCount();
        }
        
        return $count;
    }
}