<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\Auditable;

class TaggedContent extends Model
{
    use HasFactory, Auditable;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'content_tag_id',
        'taggable_type',
        'taggable_id',
        'tagged_by',
        'tagged_at',
        'metadata',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tagged_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($taggedContent) {
            if (empty($taggedContent->tagged_at)) {
                $taggedContent->tagged_at = now();
            }
        });
    }

    /**
     * Get the content tag
     */
    public function contentTag(): BelongsTo
    {
        return $this->belongsTo(ContentTag::class);
    }

    /**
     * Get the user who tagged the content
     */
    public function taggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }

    /**
     * Get the taggable model (polymorphic)
     */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for specific tag
     */
    public function scopeForTag($query, int $tagId)
    {
        return $query->where('content_tag_id', $tagId);
    }

    /**
     * Scope for specific content type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('taggable_type', $type);
    }

    /**
     * Scope for recent tagging
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('tagged_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for tagged by specific user
     */
    public function scopeTaggedBy($query, int $userId)
    {
        return $query->where('tagged_by', $userId);
    }
}
