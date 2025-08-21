<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'forum_id',
        'title',
        'slug',
        'content',
        'author_id',
        'is_pinned',
        'is_locked',
        'status'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'last_reply_at' => 'datetime',
        'view_count' => 'integer',
        'reply_count' => 'integer'
    ];

    /**
     * Get the forum that owns the thread.
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    /**
     * Get the author of the thread.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get all posts for the thread.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'thread_id');
    }

    /**
     * Get the user who made the last reply.
     */
    public function lastReplyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reply_by');
    }

    /**
     * Scope a query to only include active threads.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include pinned threads.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to only include unlocked threads.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Scope a query to order by latest activity.
     */
    public function scopeLatestActivity($query)
    {
        return $query->orderBy('is_pinned', 'desc')
                    ->orderBy('last_reply_at', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Increment the view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Check if the thread is locked.
     */
    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    /**
     * Check if the thread is pinned.
     */
    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    /**
     * Get the first post (original post) of the thread.
     */
    public function firstPost()
    {
        return $this->posts()->oldest()->first();
    }

    /**
     * Get the latest post in the thread.
     */
    public function latestPost()
    {
        return $this->posts()->latest()->first();
    }

    /**
     * Check if thread has a solution.
     */
    public function hasSolution(): bool
    {
        return $this->posts()->where('is_solution', true)->exists();
    }

    /**
     * Get the solution post if exists.
     */
    public function solutionPost()
    {
        return $this->posts()->where('is_solution', true)->first();
    }
}