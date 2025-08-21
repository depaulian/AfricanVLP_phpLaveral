<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumReputationHistory extends Model
{
    use HasFactory;

    protected $table = 'forum_reputation_history';

    protected $fillable = [
        'user_id',
        'action',
        'points_change',
        'points_before',
        'points_after',
        'source_type',
        'source_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Action types with descriptions
    public const ACTIONS = [
        'post_created' => 'Created a post',
        'thread_created' => 'Started a thread',
        'vote_received' => 'Received an upvote',
        'solution_marked' => 'Solution was accepted',
        'badge_earned' => 'Earned a badge',
        'daily_activity' => 'Daily activity bonus',
        'consecutive_days' => 'Consecutive days bonus',
        'manual_adjustment' => 'Manual adjustment',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for positive point changes
     */
    public function scopePositive($query)
    {
        return $query->where('points_change', '>', 0);
    }

    /**
     * Scope for negative point changes
     */
    public function scopeNegative($query)
    {
        return $query->where('points_change', '<', 0);
    }

    /**
     * Scope for specific actions
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for recent history
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get user's reputation history
     */
    public static function getForUser(int $userId, int $limit = 50)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's recent gains
     */
    public static function getRecentGains(int $userId, int $days = 7)
    {
        return self::where('user_id', $userId)
            ->where('points_change', '>', 0)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('points_change');
    }

    /**
     * Get action description
     */
    public function getActionDescription(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /**
     * Get formatted points change
     */
    public function getFormattedPointsChange(): string
    {
        $prefix = $this->points_change > 0 ? '+' : '';
        return $prefix . $this->points_change;
    }

    /**
     * Get summary statistics for user
     */
    public static function getUserSummary(int $userId, int $days = 30): array
    {
        $history = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $totalGains = $history->where('points_change', '>', 0)->sum('points_change');
        $totalLosses = abs($history->where('points_change', '<', 0)->sum('points_change'));
        $netChange = $totalGains - $totalLosses;

        $actionCounts = $history->groupBy('action')->map->count();

        return [
            'total_gains' => $totalGains,
            'total_losses' => $totalLosses,
            'net_change' => $netChange,
            'total_activities' => $history->count(),
            'action_breakdown' => $actionCounts->toArray(),
            'average_per_day' => $days > 0 ? round($netChange / $days, 2) : 0,
        ];
    }
}