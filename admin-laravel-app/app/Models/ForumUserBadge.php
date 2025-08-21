<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumUserBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'forum_badge_id',
        'earned_at',
        'earning_context',
        'is_featured',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'earning_context' => 'array',
        'is_featured' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(ForumBadge::class, 'forum_badge_id');
    }

    /**
     * Scope for featured badges
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for recent badges
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('earned_at', '>=', now()->subDays($days));
    }

    /**
     * Get user's featured badges
     */
    public static function getFeaturedForUser(int $userId, int $limit = 3)
    {
        return self::with('badge')
            ->where('user_id', $userId)
            ->where('is_featured', true)
            ->orderBy('earned_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's recent badges
     */
    public static function getRecentForUser(int $userId, int $days = 30, int $limit = 10)
    {
        return self::with('badge')
            ->where('user_id', $userId)
            ->where('earned_at', '>=', now()->subDays($days))
            ->orderBy('earned_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(): void
    {
        $this->is_featured = !$this->is_featured;
        $this->save();
    }
}