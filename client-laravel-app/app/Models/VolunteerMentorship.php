<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VolunteerMentorship extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'mentee_id',
        'status',
        'mentorship_goals',
        'focus_areas',
        'meeting_frequency',
        'communication_preference',
        'start_date',
        'end_date',
        'duration_months',
        'mentor_notes',
        'mentee_notes',
        'progress_milestones',
        'mentor_rating',
        'mentee_rating',
        'completion_feedback',
        'last_interaction_at',
        'total_sessions',
    ];

    protected $casts = [
        'focus_areas' => 'array',
        'progress_milestones' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_interaction_at' => 'datetime',
        'mentor_rating' => 'decimal:1',
        'mentee_rating' => 'decimal:1',
        'duration_months' => 'integer',
        'total_sessions' => 'integer',
    ];

    /**
     * Get the mentor user
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the mentee user
     */
    public function mentee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    /**
     * Scope for active mentorships
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending mentorships
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed mentorships
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for mentorships involving a specific user as mentor
     */
    public function scopeForMentor($query, $userId)
    {
        return $query->where('mentor_id', $userId);
    }

    /**
     * Scope for mentorships involving a specific user as mentee
     */
    public function scopeForMentee($query, $userId)
    {
        return $query->where('mentee_id', $userId);
    }

    /**
     * Scope for mentorships involving a specific user (either role)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('mentor_id', $userId)
              ->orWhere('mentee_id', $userId);
        });
    }

    /**
     * Start the mentorship
     */
    public function start(): void
    {
        $this->update([
            'status' => 'active',
            'start_date' => now(),
        ]);
    }

    /**
     * Complete the mentorship
     */
    public function complete(string $feedback = null): void
    {
        $this->update([
            'status' => 'completed',
            'end_date' => now(),
            'completion_feedback' => $feedback,
        ]);
    }

    /**
     * Cancel the mentorship
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Pause the mentorship
     */
    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Resume the mentorship
     */
    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Record a mentorship session
     */
    public function recordSession(): void
    {
        $this->increment('total_sessions');
        $this->update(['last_interaction_at' => now()]);
    }

    /**
     * Add a progress milestone
     */
    public function addMilestone(string $milestone, string $description = null): void
    {
        $milestones = $this->progress_milestones ?? [];
        $milestones[] = [
            'milestone' => $milestone,
            'description' => $description,
            'achieved_at' => now()->toISOString(),
            'achieved_by' => auth()->id(),
        ];
        
        $this->update(['progress_milestones' => $milestones]);
    }

    /**
     * Rate the mentor (by mentee)
     */
    public function rateMentor(float $rating, User $rater): void
    {
        if ($rater->id !== $this->mentee_id) {
            throw new \InvalidArgumentException('Only the mentee can rate the mentor.');
        }
        
        $this->update(['mentor_rating' => $rating]);
    }

    /**
     * Rate the mentee (by mentor)
     */
    public function rateMentee(float $rating, User $rater): void
    {
        if ($rater->id !== $this->mentor_id) {
            throw new \InvalidArgumentException('Only the mentor can rate the mentee.');
        }
        
        $this->update(['mentee_rating' => $rating]);
    }

    /**
     * Check if mentorship is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if mentorship is overdue
     */
    public function isOverdue(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->status === 'active';
    }

    /**
     * Get mentorship progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        
        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());
        
        if ($totalDays <= 0) {
            return 100;
        }
        
        return min(100, max(0, round(($elapsedDays / $totalDays) * 100)));
    }

    /**
     * Get remaining days
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if (!$this->end_date || $this->status !== 'active') {
            return null;
        }
        
        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Approval',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'paused' => 'Paused',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'active' => 'green',
            'completed' => 'blue',
            'cancelled' => 'red',
            'paused' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get meeting frequency display
     */
    public function getMeetingFrequencyDisplayAttribute(): string
    {
        return match ($this->meeting_frequency) {
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'as_needed' => 'As Needed',
            default => ucfirst(str_replace('_', ' ', $this->meeting_frequency)),
        };
    }

    /**
     * Get communication preference display
     */
    public function getCommunicationPreferenceDisplayAttribute(): string
    {
        return match ($this->communication_preference) {
            'in_person' => 'In Person',
            'video_call' => 'Video Call',
            'phone' => 'Phone',
            'messaging' => 'Messaging',
            'mixed' => 'Mixed Methods',
            default => ucfirst(str_replace('_', ' ', $this->communication_preference)),
        };
    }

    /**
     * Get mentorship duration in human readable format
     */
    public function getDurationDisplayAttribute(): string
    {
        if (!$this->start_date) {
            return 'Not started';
        }
        
        if (!$this->end_date) {
            return 'Ongoing since ' . $this->start_date->format('M Y');
        }
        
        return $this->start_date->format('M Y') . ' - ' . $this->end_date->format('M Y');
    }

    /**
     * Get next expected meeting date
     */
    public function getNextMeetingDateAttribute(): ?Carbon
    {
        if (!$this->last_interaction_at || $this->status !== 'active') {
            return null;
        }
        
        return match ($this->meeting_frequency) {
            'weekly' => $this->last_interaction_at->addWeek(),
            'bi_weekly' => $this->last_interaction_at->addWeeks(2),
            'monthly' => $this->last_interaction_at->addMonth(),
            default => null,
        };
    }

    /**
     * Check if a meeting is overdue
     */
    public function isMeetingOverdue(): bool
    {
        $nextMeeting = $this->next_meeting_date;
        return $nextMeeting && $nextMeeting->isPast();
    }

    /**
     * Get compatibility score between mentor and mentee
     */
    public function getCompatibilityScore(): array
    {
        $mentorInterests = $this->mentor->volunteeringInterests()->pluck('category_id')->toArray();
        $menteeInterests = $this->mentee->volunteeringInterests()->pluck('category_id')->toArray();
        
        $commonInterests = array_intersect($mentorInterests, $menteeInterests);
        $totalInterests = array_unique(array_merge($mentorInterests, $menteeInterests));
        
        $interestScore = count($totalInterests) > 0 ? (count($commonInterests) / count($totalInterests)) * 100 : 0;
        
        // Factor in experience levels, skills, etc.
        $mentorExperience = $this->mentor->volunteerAssignments()->count();
        $menteeExperience = $this->mentee->volunteerAssignments()->count();
        
        $experienceGap = $mentorExperience - $menteeExperience;
        $experienceScore = $experienceGap > 0 ? min(100, $experienceGap * 10) : 0;
        
        $overallScore = ($interestScore * 0.6) + ($experienceScore * 0.4);
        
        return [
            'overall_score' => round($overallScore),
            'interest_compatibility' => round($interestScore),
            'experience_gap' => $experienceGap,
            'common_interests' => count($commonInterests),
            'recommendation' => $this->getCompatibilityRecommendation($overallScore),
        ];
    }

    /**
     * Get compatibility recommendation
     */
    private function getCompatibilityRecommendation(float $score): string
    {
        if ($score >= 80) {
            return 'Excellent match - high compatibility';
        } elseif ($score >= 60) {
            return 'Good match - compatible interests and experience';
        } elseif ($score >= 40) {
            return 'Fair match - some compatibility';
        } else {
            return 'Low compatibility - may need additional support';
        }
    }

    /**
     * Create a mentorship request
     */
    public static function createRequest(
        User $mentor,
        User $mentee,
        array $goals,
        array $focusAreas = [],
        string $meetingFrequency = 'monthly',
        string $communicationPreference = 'mixed',
        int $durationMonths = 6
    ): static {
        // Check if mentorship already exists
        $existing = static::where(function ($query) use ($mentor, $mentee) {
            $query->where('mentor_id', $mentor->id)
                  ->where('mentee_id', $mentee->id);
        })->whereIn('status', ['pending', 'active', 'paused'])->first();
        
        if ($existing) {
            throw new \InvalidArgumentException('Active or pending mentorship already exists between these users.');
        }
        
        return static::create([
            'mentor_id' => $mentor->id,
            'mentee_id' => $mentee->id,
            'mentorship_goals' => implode("\n", $goals),
            'focus_areas' => $focusAreas,
            'meeting_frequency' => $meetingFrequency,
            'communication_preference' => $communicationPreference,
            'duration_months' => $durationMonths,
            'end_date' => now()->addMonths($durationMonths),
            'status' => 'pending',
        ]);
    }

    /**
     * Get mentorship statistics for a user
     */
    public static function getUserMentorshipStats(User $user): array
    {
        $asMentor = static::forMentor($user->id);
        $asMentee = static::forMentee($user->id);
        
        return [
            'as_mentor' => [
                'total' => $asMentor->count(),
                'active' => $asMentor->active()->count(),
                'completed' => $asMentor->completed()->count(),
                'average_rating' => $asMentor->whereNotNull('mentor_rating')->avg('mentor_rating'),
                'total_sessions' => $asMentor->sum('total_sessions'),
            ],
            'as_mentee' => [
                'total' => $asMentee->count(),
                'active' => $asMentee->active()->count(),
                'completed' => $asMentee->completed()->count(),
                'average_rating' => $asMentee->whereNotNull('mentee_rating')->avg('mentee_rating'),
                'total_sessions' => $asMentee->sum('total_sessions'),
            ],
        ];
    }

    /**
     * Find potential mentors for a user
     */
    public static function findPotentialMentors(User $mentee, int $limit = 10): array
    {
        $menteeInterests = $mentee->volunteeringInterests()->pluck('category_id')->toArray();
        $menteeExperience = $mentee->volunteerAssignments()->count();
        
        if (empty($menteeInterests)) {
            return [];
        }
        
        // Find users with similar interests but more experience
        $potentialMentors = User::whereHas('volunteeringInterests', function ($query) use ($menteeInterests) {
                $query->whereIn('category_id', $menteeInterests);
            })
            ->where('id', '!=', $mentee->id)
            ->withCount(['volunteerAssignments as experience_count'])
            ->having('experience_count', '>', $menteeExperience)
            ->withCount(['volunteeringInterests as shared_interests' => function ($query) use ($menteeInterests) {
                $query->whereIn('category_id', $menteeInterests);
            }])
            ->orderByDesc('shared_interests')
            ->orderByDesc('experience_count')
            ->limit($limit)
            ->get();
        
        return $potentialMentors->map(function ($mentor) use ($mentee, $menteeInterests) {
            $mentorInterests = $mentor->volunteeringInterests()->pluck('category_id')->toArray();
            $commonInterests = array_intersect($menteeInterests, $mentorInterests);
            $experienceGap = $mentor->experience_count - $mentee->volunteerAssignments()->count();
            
            return [
                'user' => $mentor,
                'common_interests_count' => count($commonInterests),
                'common_interests' => $commonInterests,
                'experience_gap' => $experienceGap,
                'compatibility_score' => (count($commonInterests) * 20) + min(50, $experienceGap * 5),
                'mentor_rating' => static::forMentor($mentor->id)->avg('mentor_rating'),
                'completed_mentorships' => static::forMentor($mentor->id)->completed()->count(),
            ];
        })->sortByDesc('compatibility_score')->values()->toArray();
    }

    /**
     * Get mentorship analytics
     */
    public static function getAnalytics(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        $total = $query->count();
        $active = $query->active()->count();
        $completed = $query->completed()->count();
        $pending = $query->pending()->count();
        
        $averageRating = static::whereNotNull('mentor_rating')->avg('mentor_rating');
        $averageDuration = static::completed()
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get()
            ->avg(function ($mentorship) {
                return $mentorship->start_date->diffInDays($mentorship->end_date);
            });
        
        $successRate = $total > 0 ? ($completed / $total) * 100 : 0;
        
        return [
            'total_mentorships' => $total,
            'active_mentorships' => $active,
            'completed_mentorships' => $completed,
            'pending_mentorships' => $pending,
            'success_rate' => round($successRate, 2),
            'average_mentor_rating' => round($averageRating, 2),
            'average_duration_days' => round($averageDuration),
            'total_sessions' => static::sum('total_sessions'),
            'most_common_focus_areas' => static::getMostCommonFocusAreas(),
            'monthly_trends' => static::getMonthlyTrends(),
        ];
    }

    /**
     * Get most common focus areas
     */
    private static function getMostCommonFocusAreas(): array
    {
        $focusAreas = static::whereNotNull('focus_areas')->pluck('focus_areas');
        $allAreas = [];
        
        foreach ($focusAreas as $areas) {
            if (is_array($areas)) {
                $allAreas = array_merge($allAreas, $areas);
            }
        }
        
        $counts = array_count_values($allAreas);
        arsort($counts);
        
        return array_slice($counts, 0, 10, true);
    }

    /**
     * Get monthly trends
     */
    private static function getMonthlyTrends(): array
    {
        $trends = static::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                AVG(CASE WHEN mentor_rating IS NOT NULL THEN mentor_rating ELSE NULL END) as avg_rating
            ')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return $trends->toArray();
    }
}