<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'metric_type',
        'entity_type',
        'entity_id',
        'value',
        'breakdown',
    ];

    protected $casts = [
        'date' => 'date',
        'value' => 'integer',
        'breakdown' => 'array',
    ];

    /**
     * Get the entity this metric belongs to
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for specific metric type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for global metrics (no entity)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('entity_type')->whereNull('entity_id');
    }

    /**
     * Scope for specific entity
     */
    public function scopeForEntity($query, $entity)
    {
        return $query->where('entity_type', get_class($entity))
                    ->where('entity_id', $entity->id);
    }

    /**
     * Get available metric types
     */
    public static function getMetricTypes(): array
    {
        return [
            'daily_active_users' => 'Daily Active Users',
            'threads_created' => 'Threads Created',
            'posts_created' => 'Posts Created',
            'votes_cast' => 'Votes Cast',
            'searches_performed' => 'Searches Performed',
            'attachments_uploaded' => 'Attachments Uploaded',
            'forum_views' => 'Forum Views',
            'thread_views' => 'Thread Views',
            'post_views' => 'Post Views',
            'new_users' => 'New Users',
            'returning_users' => 'Returning Users',
            'user_engagement_score' => 'User Engagement Score',
            'content_quality_score' => 'Content Quality Score',
            'moderation_actions' => 'Moderation Actions',
            'reports_submitted' => 'Reports Submitted',
        ];
    }

    /**
     * Record a metric value
     */
    public static function record(
        string $metricType,
        int $value,
        $date = null,
        $entity = null,
        array $breakdown = []
    ): self {
        $date = $date ?? now()->toDateString();
        
        return self::updateOrCreate(
            [
                'date' => $date,
                'metric_type' => $metricType,
                'entity_type' => $entity ? get_class($entity) : null,
                'entity_id' => $entity?->id,
            ],
            [
                'value' => $value,
                'breakdown' => $breakdown,
            ]
        );
    }

    /**
     * Get metric trend over time
     */
    public static function getTrend(
        string $metricType,
        $startDate,
        $endDate,
        $entity = null
    ): array {
        $query = self::ofType($metricType)->dateRange($startDate, $endDate);
        
        if ($entity) {
            $query->forEntity($entity);
        } else {
            $query->global();
        }
        
        return $query->orderBy('date')
                    ->pluck('value', 'date')
                    ->toArray();
    }

    /**
     * Get total for a metric type
     */
    public static function getTotal(
        string $metricType,
        $startDate = null,
        $endDate = null,
        $entity = null
    ): int {
        $query = self::ofType($metricType);
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        if ($entity) {
            $query->forEntity($entity);
        } else {
            $query->global();
        }
        
        return $query->sum('value');
    }

    /**
     * Get average for a metric type
     */
    public static function getAverage(
        string $metricType,
        $startDate = null,
        $endDate = null,
        $entity = null
    ): float {
        $query = self::ofType($metricType);
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        if ($entity) {
            $query->forEntity($entity);
        } else {
            $query->global();
        }
        
        return $query->avg('value') ?? 0;
    }

    /**
     * Get growth rate between two periods
     */
    public static function getGrowthRate(
        string $metricType,
        $currentStart,
        $currentEnd,
        $previousStart,
        $previousEnd,
        $entity = null
    ): float {
        $currentTotal = self::getTotal($metricType, $currentStart, $currentEnd, $entity);
        $previousTotal = self::getTotal($metricType, $previousStart, $previousEnd, $entity);
        
        if ($previousTotal == 0) {
            return $currentTotal > 0 ? 100 : 0;
        }
        
        return (($currentTotal - $previousTotal) / $previousTotal) * 100;
    }

    /**
     * Get top performing entities for a metric
     */
    public static function getTopPerformers(
        string $metricType,
        string $entityType,
        $startDate = null,
        $endDate = null,
        int $limit = 10
    ): array {
        $query = self::ofType($metricType)->where('entity_type', $entityType);
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        return $query->selectRaw('entity_id, SUM(value) as total_value')
                    ->groupBy('entity_id')
                    ->orderByDesc('total_value')
                    ->limit($limit)
                    ->get()
                    ->toArray();
    }

    /**
     * Calculate engagement score based on multiple metrics
     */
    public static function calculateEngagementScore($entity, $date = null): float
    {
        $date = $date ?? now()->toDateString();
        
        // Get various metrics for the entity
        $views = self::getTotal('forum_views', $date, $date, $entity) +
                self::getTotal('thread_views', $date, $date, $entity);
        $posts = self::getTotal('posts_created', $date, $date, $entity);
        $threads = self::getTotal('threads_created', $date, $date, $entity);
        $votes = self::getTotal('votes_cast', $date, $date, $entity);
        
        // Calculate weighted engagement score
        $score = ($views * 0.1) + ($posts * 2) + ($threads * 3) + ($votes * 1.5);
        
        return round($score, 2);
    }
}