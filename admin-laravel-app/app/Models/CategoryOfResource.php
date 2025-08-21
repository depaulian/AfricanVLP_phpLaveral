<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CategoryOfResource extends Model
{
    protected $table = 'category_of_resources';
    
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'status'
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime'
    ];

    /**
     * Get the resources in this category.
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_categories', 'category_id', 'resource_id');
    }

    /**
     * Check if the category is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}