<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceType extends Model
{
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
     * Get the resources of this type.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Check if the resource type is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope for active resource types.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}