<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'user_id',
        'interaction_type',
        'rating',
        'comment',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'rating' => 'decimal:1',
    ];

    /**
     * Get the resource
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(VolunteerResource::class, 'resource_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for specific interaction type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for interactions by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for interactions on resource
     */
    public function scopeOnResource($query, $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Get interaction type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->interaction_type) {
            'view' => 'Viewed',
            'download' => 'Downloaded',
            'like' => 'Liked',
            'bookmark' => 'Bookmarked',
            'share' => 'Shared',
            'comment' => 'Commented',
            'rating' => 'Rated',
            default => ucfirst($this->interaction_type),
        };
    }
}