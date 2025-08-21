<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'trackable_type',
        'trackable_id',
        'metadata',
        'ip_address',
        'user_agent',
        'session_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trackable model (forum, thread, or post)
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for specific event type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific trackable type
     */
    public function scopeForTrackableType($query, string $type)
    {
        return $query->where('trackable_type', $type);
    }

    /**
     * Get available event types
     */
    public static function getEventTypes(): array
    {
        return [
            'forum_view' => 'Forum View',
            'thread_view' => 'Thread View',
            'post_view' => 'Post View',
            'thread_create' => 'Thread Created',
            'post_create' => 'Post Created',
            'vote_cast' => 'Vote Cast',
            'search_performed' => 'Search Performed',
            'attachment_download' => 'Attachment Downloaded',
            'user_mention' => 'User Mentioned',
            'solution_marked' => 'Solution Marked',
            'thread_subscribed' => 'Thread Subscribed',
            'forum_subscribed' => 'Forum Subscribed',
        ];
    }

    /**
     * Track an event
     */
    public static function track(
        string $eventType,
        $trackable = null,
        ?User $user = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $sessionId = null
    ): self {
        return self::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'trackable_type' => $trackable ? get_class($trackable) : null,
            'trackable_id' => $trackable?->id,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'session_id' => $sessionId ?? session()->getId(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get popular content by event type
     */
    public static function getPopularContent(string $eventType, string $trackableType, int $limit = 10, int $days = 30)
    {
        return self::where('event_type', $eventType)
            ->where('trackable_type', $trackableType)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('trackable_id, COUNT(*) as event_count')
            ->groupBy('trackable_id')
            ->orderByDesc('event_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user activity summary
     */
    public static function getUserActivitySummary(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_events' => self::forUser($user->id)->where('created_at', '>=', $startDate)->count(),
            'forums_viewed' => self::forUser($user->id)->ofType('forum_view')->where('created_at', '>=', $startDate)->distinct('trackable_id')->count(),
            'threads_viewed' => self::forUser($user->id)->ofType('thread_view')->where('created_at', '>=', $startDate)->distinct('trackable_id')->count(),
            'threads_created' => self::forUser($user->id)->ofType('thread_create')->where('created_at', '>=', $startDate)->count(),
            'posts_created' => self::forUser($user->id)->ofType('post_create')->where('created_at', '>=', $startDate)->count(),
            'votes_cast' => self::forUser($user->id)->ofType('vote_cast')->where('created_at', '>=', $startDate)->count(),
            'searches_performed' => self::forUser($user->id)->ofType('search_performed')->where('created_at', '>=', $startDate)->count(),
        ];
    }

    /**
     * Get daily activity for a date range
     */
    public static function getDailyActivity(string $eventType, $startDate, $endDate): array
    {
        return self::ofType($eventType)
            ->dateRange($startDate, $endDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Get hourly activity for a specific date
     */
    public static function getHourlyActivity(string $eventType, $date): array
    {
        return self::ofType($eventType)
            ->whereDate('created_at', $date)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Get top users by activity
     */
    public static function getTopUsers(string $eventType, int $limit = 10, int $days = 30): array
    {
        return self::ofType($eventType)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as event_count')
            ->groupBy('user_id')
            ->orderByDesc('event_count')
            ->limit($limit)
            ->with('user:id,name,email')
            ->get()
            ->toArray();
    }
}