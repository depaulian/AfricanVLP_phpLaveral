<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ForumPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'thread_id',
        'content',
        'author_id',
        'parent_post_id',
        'is_solution',
        'status'
    ];

    protected $casts = [
        'is_solution' => 'boolean',
        'upvotes' => 'integer',
        'downvotes' => 'integer'
    ];

    /**
     * Get the thread that owns the post.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    /**
     * Get the author of the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the parent post if this is a reply.
     */
    public function parentPost(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'parent_post_id');
    }

    /**
     * Get all replies to this post.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'parent_post_id');
    }

    /**
     * Get all attachments for the post.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ForumAttachment::class, 'post_id');
    }

    /**
     * Get all votes for the post.
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(ForumVote::class, 'voteable');
    }

    /**
     * Scope a query to only include active posts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include solution posts.
     */
    public function scopeSolutions($query)
    {
        return $query->where('is_solution', true);
    }

    /**
     * Scope a query to only include top-level posts (not replies).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_post_id');
    }

    /**
     * Scope a query to order by vote score.
     */
    public function scopeByVoteScore($query, $direction = 'desc')
    {
        return $query->orderByRaw('(upvotes - downvotes) ' . $direction);
    }

    /**
     * Get the vote score (upvotes - downvotes).
     */
    public function getVoteScoreAttribute(): int
    {
        return $this->upvotes - $this->downvotes;
    }

    /**
     * Check if the post is marked as a solution.
     */
    public function isSolution(): bool
    {
        return $this->is_solution;
    }

    /**
     * Check if the post is a reply to another post.
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_post_id);
    }

    /**
     * Get the user's vote on this post.
     */
    public function getUserVote(User $user)
    {
        return $this->votes()->where('user_id', $user->id)->first();
    }

    /**
     * Check if user has voted on this post.
     */
    public function hasUserVoted(User $user): bool
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the user's vote type on this post.
     */
    public function getUserVoteType(User $user): ?string
    {
        $vote = $this->getUserVote($user);
        return $vote ? $vote->vote_type : null;
    }

    /**
     * Mark this post as the solution.
     */
    public function markAsSolution(): void
    {
        // Unmark any existing solution in the thread
        $this->thread->posts()->where('is_solution', true)->update(['is_solution' => false]);
        
        // Mark this post as solution
        $this->update(['is_solution' => true]);
    }

    /**
     * Unmark this post as the solution.
     */
    public function unmarkAsSolution(): void
    {
        $this->update(['is_solution' => false]);
    }
}