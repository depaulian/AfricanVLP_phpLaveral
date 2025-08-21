<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'voteable_type',
        'voteable_id',
        'vote_type'
    ];

    /**
     * Get the user who made the vote.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the voteable model (post, thread, etc.).
     */
    public function voteable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include upvotes.
     */
    public function scopeUpvotes($query)
    {
        return $query->where('vote_type', 'up');
    }

    /**
     * Scope a query to only include downvotes.
     */
    public function scopeDownvotes($query)
    {
        return $query->where('vote_type', 'down');
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope a query to filter by voteable.
     */
    public function scopeForVoteable($query, $voteableType, $voteableId)
    {
        return $query->where('voteable_type', $voteableType)
                    ->where('voteable_id', $voteableId);
    }

    /**
     * Check if this is an upvote.
     */
    public function isUpvote(): bool
    {
        return $this->vote_type === 'up';
    }

    /**
     * Check if this is a downvote.
     */
    public function isDownvote(): bool
    {
        return $this->vote_type === 'down';
    }

    /**
     * Toggle the vote type.
     */
    public function toggle(): void
    {
        $this->vote_type = $this->vote_type === 'up' ? 'down' : 'up';
        $this->save();
    }

    /**
     * Get the opposite vote type.
     */
    public function getOppositeVoteType(): string
    {
        return $this->vote_type === 'up' ? 'down' : 'up';
    }
}