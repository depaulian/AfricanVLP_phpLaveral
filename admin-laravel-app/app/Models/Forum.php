<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Forum extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'organization_id',
        'category',
        'is_private',
        'moderator_ids',
        'status',
        'settings'
    ];

    protected $casts = [
        'moderator_ids' => 'array',
        'settings' => 'array',
        'is_private' => 'boolean',
        'last_activity_at' => 'datetime',
        'post_count' => 'integer',
        'thread_count' => 'integer'
    ];

    /**
     * Get the organization that owns the forum.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all threads for the forum.
     */
    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    /**
     * Get the moderators for the forum.
     */
    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'forum_moderators');
    }

    /**
     * Scope a query to only include forums accessible by a user.
     */
    public function scopeAccessibleBy($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('is_private', false)
              ->orWhereHas('organization.users', function ($orgQuery) use ($user) {
                  $orgQuery->where('users.id', $user->id);
              });
        });
    }

    /**
     * Scope a query to only include active forums.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if user can moderate this forum.
     */
    public function canModerate(User $user): bool
    {
        return in_array($user->id, $this->moderator_ids ?? []) ||
               $user->hasRole('admin');
    }

    /**
     * Get the latest activity timestamp.
     */
    public function getLatestActivityAttribute()
    {
        return $this->last_activity_at ?? $this->updated_at;
    }
}