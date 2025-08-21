<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FeedbackAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'metric_type',
        'feedback_type',
        'period_type',
        'period_start',
        'period_end',
        'value',
        'breakdown',
        'metadata',
        'calculated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'value' => 'decimal:4',
        'breakdown' => 'array',
        'metadata' => 'array',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the organization this analytics belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope for specific metric type
     */
    public function scopeMetricType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope for specific feedback type
     */
    public function scopeFeedbackType($query, string $type)
    {
        return $query->where('feedback_type', $type);
    }

    /**
     * Scope for specific period type
     */
    public function scopePeriodType($query, string $type)
    {
        return $query->where('period_type', $type);
    }

    /**
     * Scope for specific organization
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->where('period_start', '>=', $start)
                    ->where('period_end', '<=', $end);
    }

    /**
     * Scope for recent analytics
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('period_start', '>=', now()->subDays($days));
    }

    /**
     * Get metric type display name
     */
    public function getMetricTypeDisplayAttribute(): string
    {
        return match ($this->metric_type) {
            'average_rating' => 'Average Rating',
            'feedback_count' => 'Feedback Count',
            'response_rate' => 'Response Rate',
            'completion_rate' => 'Completion Rate',
            'satisfaction_score' => 'Satisfaction Score',
            'improvement_areas' => 'Improvement Areas',
            'positive_feedback_ratio' => 'Positive Feedback Ratio',
            'tag_frequency' => 'Tag Frequency',
            default => ucwords(str_replace('_', ' ', $this->metric_type)),
        };
    }

    /**
     * Get feedback type display name
     */
    public function getFeedbackTypeDisplayAttribute(): string
    {
        return match ($this->feedback_type) {
            'volunteer_to_organization' => 'Volunteer to Organization',
            'organization_to_volunteer' => 'Organization to Volunteer',
            'supervisor_to_volunteer' => 'Supervisor to Volunteer',
            'volunteer_to_supervisor' => 'Volunteer to Supervisor',
            'beneficiary_to_volunteer' => 'Beneficiary to Volunteer',
            'all' => 'All Types',
            default => ucwords(str_replace('_', ' ', $this->feedback_type)),
        };
    }

    /**
     * Get period type display name
     */
    public function getPeriodTypeDisplayAttribute(): string
    {
        return match ($this->period_type) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => ucfirst($this->period_type),
        };
    }

    /**
     * Get formatted value based on metric type
     */
    public function getFormattedValueAttribute(): string
    {
        return match ($this->metric_type) {
            'average_rating' => number_format($this->value, 1) . '/5.0',
            'response_rate', 'completion_rate', 'positive_feedback_ratio' => number_format($this->value, 1) . '%',
            'feedback_count' => number_format($this->value, 0),
            'satisfaction_score' => number_format($this->value, 2),
            default => number_format($this->value, 2),
        };
    }

    /**
     * Get trend indicator
     */
    public function getTrendAttribute(): ?string
    {
        if (!isset($this->metadata['previous_value'])) {
            return null;
        }

        $previousValue = $this->metadata['previous_value'];
        $currentValue = $this->value;

        if ($currentValue > $previousValue) {
            return 'up';
        } elseif ($currentValue < $previousValue) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * Get trend percentage
     */
    public function getTrendPercentageAttribute(): ?float
    {
        if (!isset($this->metadata['previous_value']) || $this->metadata['previous_value'] == 0) {
            return null;
        }

        $previousValue = $this->metadata['previous_value'];
        $currentValue = $this->value;

        return round((($currentValue - $previousValue) / $previousValue) * 100, 1);
    }

    /**
     * Calculate average rating analytics
     */
    public static function calculateAverageRating(
        ?Organization $organization,
        string $feedbackType,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $query = VolunteerFeedback::query()
            ->whereNotNull('overall_rating')
            ->whereBetween('submitted_at', [$periodStart, $periodEnd]);

        if ($organization) {
            $query->whereHas('assignment', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            });
        }

        if ($feedbackType !== 'all') {
            $query->where('feedback_type', $feedbackType);
        }

        $averageRating = $query->avg('overall_rating');
        $breakdown = [];

        // Calculate breakdown by rating categories
        $ratingCategories = ['communication_rating', 'reliability_rating', 'skill_rating', 'attitude_rating', 'impact_rating'];
        foreach ($ratingCategories as $category) {
            $breakdown[$category] = $query->avg($category);
        }

        // Get previous period for trend calculation
        $previousPeriodStart = static::getPreviousPeriodStart($periodStart, $periodType);
        $previousPeriodEnd = static::getPreviousPeriodEnd($periodEnd, $periodType);
        
        $previousQuery = clone $query;
        $previousAverage = $previousQuery->whereBetween('submitted_at', [$previousPeriodStart, $previousPeriodEnd])
                                       ->avg('overall_rating');

        static::updateOrCreate([
            'organization_id' => $organization?->id,
            'metric_type' => 'average_rating',
            'feedback_type' => $feedbackType,
            'period_type' => $periodType,
            'period_start' => $periodStart,
        ], [
            'period_end' => $periodEnd,
            'value' => $averageRating ?? 0,
            'breakdown' => $breakdown,
            'metadata' => [
                'total_feedback' => $query->count(),
                'previous_value' => $previousAverage,
            ],
            'calculated_at' => now(),
        ]);
    }

    /**
     * Calculate feedback count analytics
     */
    public static function calculateFeedbackCount(
        ?Organization $organization,
        string $feedbackType,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $query = VolunteerFeedback::query()
            ->submitted()
            ->whereBetween('submitted_at', [$periodStart, $periodEnd]);

        if ($organization) {
            $query->whereHas('assignment', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            });
        }

        if ($feedbackType !== 'all') {
            $query->where('feedback_type', $feedbackType);
        }

        $totalCount = $query->count();
        
        // Calculate breakdown by status
        $breakdown = [
            'submitted' => $query->where('status', 'submitted')->count(),
            'reviewed' => $query->where('status', 'reviewed')->count(),
            'published' => $query->where('status', 'published')->count(),
        ];

        // Get previous period for trend calculation
        $previousPeriodStart = static::getPreviousPeriodStart($periodStart, $periodType);
        $previousPeriodEnd = static::getPreviousPeriodEnd($periodEnd, $periodType);
        
        $previousQuery = clone $query;
        $previousCount = $previousQuery->whereBetween('submitted_at', [$previousPeriodStart, $previousPeriodEnd])
                                      ->count();

        static::updateOrCreate([
            'organization_id' => $organization?->id,
            'metric_type' => 'feedback_count',
            'feedback_type' => $feedbackType,
            'period_type' => $periodType,
            'period_start' => $periodStart,
        ], [
            'period_end' => $periodEnd,
            'value' => $totalCount,
            'breakdown' => $breakdown,
            'metadata' => [
                'previous_value' => $previousCount,
            ],
            'calculated_at' => now(),
        ]);
    }

    /**
     * Calculate response rate analytics
     */
    public static function calculateResponseRate(
        ?Organization $organization,
        string $feedbackType,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $query = VolunteerFeedback::query()
            ->submitted()
            ->whereBetween('submitted_at', [$periodStart, $periodEnd]);

        if ($organization) {
            $query->whereHas('assignment', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            });
        }

        if ($feedbackType !== 'all') {
            $query->where('feedback_type', $feedbackType);
        }

        $totalFeedback = $query->count();
        $respondedFeedback = $query->whereNotNull('response')->count();
        
        $responseRate = $totalFeedback > 0 ? ($respondedFeedback / $totalFeedback) * 100 : 0;

        // Get previous period for trend calculation
        $previousPeriodStart = static::getPreviousPeriodStart($periodStart, $periodType);
        $previousPeriodEnd = static::getPreviousPeriodEnd($periodEnd, $periodType);
        
        $previousQuery = clone $query;
        $previousTotal = $previousQuery->whereBetween('submitted_at', [$previousPeriodStart, $previousPeriodEnd])
                                      ->count();
        $previousResponded = $previousQuery->whereNotNull('response')->count();
        $previousRate = $previousTotal > 0 ? ($previousResponded / $previousTotal) * 100 : 0;

        static::updateOrCreate([
            'organization_id' => $organization?->id,
            'metric_type' => 'response_rate',
            'feedback_type' => $feedbackType,
            'period_type' => $periodType,
            'period_start' => $periodStart,
        ], [
            'period_end' => $periodEnd,
            'value' => $responseRate,
            'breakdown' => [
                'total_feedback' => $totalFeedback,
                'responded_feedback' => $respondedFeedback,
            ],
            'metadata' => [
                'previous_value' => $previousRate,
            ],
            'calculated_at' => now(),
        ]);
    }

    /**
     * Calculate tag frequency analytics
     */
    public static function calculateTagFrequency(
        ?Organization $organization,
        string $feedbackType,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $query = VolunteerFeedback::query()
            ->submitted()
            ->whereNotNull('tags')
            ->whereBetween('submitted_at', [$periodStart, $periodEnd]);

        if ($organization) {
            $query->whereHas('assignment', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            });
        }

        if ($feedbackType !== 'all') {
            $query->where('feedback_type', $feedbackType);
        }

        $feedback = $query->get();
        $tagFrequency = [];

        foreach ($feedback as $item) {
            if (is_array($item->tags)) {
                foreach ($item->tags as $tag) {
                    $tagFrequency[$tag] = ($tagFrequency[$tag] ?? 0) + 1;
                }
            }
        }

        // Sort by frequency
        arsort($tagFrequency);

        static::updateOrCreate([
            'organization_id' => $organization?->id,
            'metric_type' => 'tag_frequency',
            'feedback_type' => $feedbackType,
            'period_type' => $periodType,
            'period_start' => $periodStart,
        ], [
            'period_end' => $periodEnd,
            'value' => count($tagFrequency),
            'breakdown' => $tagFrequency,
            'metadata' => [
                'total_feedback_with_tags' => $feedback->count(),
            ],
            'calculated_at' => now(),
        ]);
    }

    /**
     * Get previous period start date
     */
    private static function getPreviousPeriodStart(Carbon $periodStart, string $periodType): Carbon
    {
        return match ($periodType) {
            'daily' => $periodStart->copy()->subDay(),
            'weekly' => $periodStart->copy()->subWeek(),
            'monthly' => $periodStart->copy()->subMonth(),
            'quarterly' => $periodStart->copy()->subMonths(3),
            'yearly' => $periodStart->copy()->subYear(),
            default => $periodStart->copy()->subMonth(),
        };
    }

    /**
     * Get previous period end date
     */
    private static function getPreviousPeriodEnd(Carbon $periodEnd, string $periodType): Carbon
    {
        return match ($periodType) {
            'daily' => $periodEnd->copy()->subDay(),
            'weekly' => $periodEnd->copy()->subWeek(),
            'monthly' => $periodEnd->copy()->subMonth(),
            'quarterly' => $periodEnd->copy()->subMonths(3),
            'yearly' => $periodEnd->copy()->subYear(),
            default => $periodEnd->copy()->subMonth(),
        };
    }

    /**
     * Calculate all analytics for a period
     */
    public static function calculateAllAnalytics(
        ?Organization $organization,
        string $feedbackType,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        static::calculateAverageRating($organization, $feedbackType, $periodType, $periodStart, $periodEnd);
        static::calculateFeedbackCount($organization, $feedbackType, $periodType, $periodStart, $periodEnd);
        static::calculateResponseRate($organization, $feedbackType, $periodType, $periodStart, $periodEnd);
        static::calculateTagFrequency($organization, $feedbackType, $periodType, $periodStart, $periodEnd);
    }

    /**
     * Get analytics summary for dashboard
     */
    public static function getDashboardSummary(?Organization $organization, int $days = 30): array
    {
        $query = static::query()
            ->where('period_start', '>=', now()->subDays($days));

        if ($organization) {
            $query->where('organization_id', $organization->id);
        }

        $analytics = $query->get()->groupBy('metric_type');

        return [
            'average_rating' => $analytics->get('average_rating')?->first()?->formatted_value ?? 'N/A',
            'total_feedback' => $analytics->get('feedback_count')?->sum('value') ?? 0,
            'response_rate' => $analytics->get('response_rate')?->avg('value') ?? 0,
            'trends' => [
                'rating_trend' => $analytics->get('average_rating')?->first()?->trend ?? 'stable',
                'feedback_trend' => $analytics->get('feedback_count')?->first()?->trend ?? 'stable',
                'response_trend' => $analytics->get('response_rate')?->first()?->trend ?? 'stable',
            ],
        ];
    }
}