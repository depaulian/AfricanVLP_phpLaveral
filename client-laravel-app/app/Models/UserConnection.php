<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_id',
        'following_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who is following
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Get the user being followed
     */
    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'following_id');
    }

    /**
     * Scope for accepted connections
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for pending connections
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for blocked connections
     */
    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', 'blocked');
    }

    /**
     * Scope for connections involving a specific user
     */
    public function scopeInvolvingUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('follower_id', $userId)
              ->orWhere('following_id', $userId);
        });
    }

    /**
     * Scope for mutual connections between two users
     */
    public function scopeMutual(Builder $query, int $userId1, int $userId2): Builder
    {
        return $query->where(function ($q) use ($userId1, $userId2) {
            $q->where('follower_id', $userId1)->where('following_id', $userId2);
        })->orWhere(function ($q) use ($userId1, $userId2) {
            $q->where('follower_id', $userId2)->where('following_id', $userId1);
        });
    }

    /**
     * Check if connection is mutual
     */
    public function isMutual(): bool
    {
        return self::where('follower_id', $this->following_id)
            ->where('following_id', $this->follower_id)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Get the other user in the connection
     */
    public function getOtherUser(int $currentUserId): ?User
    {
        if ($this->follower_id === $currentUserId) {
            return $this->following;
        } elseif ($this->following_id === $currentUserId) {
            return $this->follower;
        }
        
        return null;
    }

    /**
     * Accept a pending connection
     */
    public function accept(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update(['status' => 'accepted']);
    }

    /**
     * Block a connection
     */
    public function block(): bool
    {
        return $this->update(['status' => 'blocked']);
    }

    /**
     * Unblock a connection
     */
    public function unblock(): bool
    {
        if ($this->status !== 'blocked') {
            return false;
        }

        return $this->update(['status' => 'accepted']);
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'accepted' => 'Connected',
            'pending' => 'Pending',
            'blocked' => 'Blocked',
            default => 'Unknown'
        };
    }

    /**
     * Get connection type from perspective of a user
     */
    public function getConnectionType(int $userId): string
    {
        if ($this->follower_id === $userId) {
            return 'following';
        } elseif ($this->following_id === $userId) {
            return 'follower';
        }
        
        return 'unknown';
    }

    /**
     * Check if users are connected
     */
    public static function areConnected(int $userId1, int $userId2): bool
    {
        return self::where(function ($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId1)
                  ->where('following_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId2)
                  ->where('following_id', $userId1);
        })->where('status', 'accepted')->exists();
    }

    /**
     * Get connection status between two users
     */
    public static function getConnectionStatus(int $userId1, int $userId2): ?string
    {
        $connection = self::where(function ($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId1)
                  ->where('following_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId2)
                  ->where('following_id', $userId1);
        })->first();

        return $connection ? $connection->status : null;
    }

    /**
     * Create or update connection
     */
    public static function createConnection(int $followerId, int $followingId, string $status = 'accepted'): self
    {
        return self::updateOrCreate(
            [
                'follower_id' => $followerId,
                'following_id' => $followingId,
            ],
            [
                'status' => $status,
            ]
        );
    }

    /**
     * Remove connection
     */
    public static function removeConnection(int $userId1, int $userId2): bool
    {
        return self::where(function ($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId1)
                  ->where('following_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('follower_id', $userId2)
                  ->where('following_id', $userId1);
        })->delete() > 0;
    }

    /**
     * Get mutual connections between two users
     */
    public static function getMutualConnections(int $userId1, int $userId2): \Illuminate\Database\Eloquent\Collection
    {
        $user1Connections = self::where('follower_id', $userId1)
            ->where('status', 'accepted')
            ->pluck('following_id');

        $user2Connections = self::where('follower_id', $userId2)
            ->where('status', 'accepted')
            ->pluck('following_id');

        $mutualIds = $user1Connections->intersect($user2Connections);

        return User::whereIn('id', $mutualIds)->get();
    }
}