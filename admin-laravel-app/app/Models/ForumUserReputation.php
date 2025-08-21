<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ForumUserReputation extends Model
{
    use HasFactory;

    protected $table = 'forum_user_reputation';

    protected $fillable = [
        'user_id',
        'total_points',
        'post_points',
        'vote_points',
        'solution_points',
        'badge_points',
        'rank',
        'rank_level',
        'posts_count',
        'threads_count',
        'votes_received',
        'solutions_provided',
        'consecutive_days_active',
        'last_activity_date',
    ];

    protected $casts = [
        'last_activity_date' => 'date',
    ];

    // Rank definitions with point thresholds
    public const RANKS = [
        1 => ['name' => 'Newcomer', 'min_points' => 0, 'color' => '#9CA3AF'],
        2 => ['name' => 'Contributor', 'min_points' => 100, 'color' => '#10B981'],
        3 => ['name' => 'Regular', 'min_points' => 500, 'color' => '#3B82F6'],
        4 => ['name' => 'Veteran', 'min_points' => 1500, 'color' => '#8B5CF6'],
        5 => ['name' => 'Expert', 'min_points' => 3000, 'color' => '#F59E0B'],
        6 => ['name' => 'Master', 'min_points' => 6000, 'color' => '#EF4444'],
        7 => ['name' => 'Legend', 'min_points' => 12000, 'color' => '#DC2626'],
    ];

    // Point values for different actions
    public const POINT_VALUES = [
        'post_created' => 5,
        'thread_created' => 10,
        'vote_received' => 2,
        'solution_marked' => 25,
        'daily_activity' => 1,
        'consecutive_days' => 5, // Bonus for consecutive days
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reputationHistory(): HasMany
    {
        return $this->hasMany(ForumReputationHistory::class, 'user_id', 'user_id');
    }

    /**
     * Award points for a specific action
     */
    public function awardPoints(string $action, int $customPoints = null, array $context = []): void
    {
        $points = $customPoints ?? self::POINT_VALUES[$action] ?? 0;
        
        if ($points <= 0) {
            return;
        }

        $pointsBefore = $this->total_points;
        
        // Update specific point categories
        switch ($action) {
            case 'post_created':
                $this->post_points += $points;
                $this->posts_count++;
                break;
            case 'thread_created':
                $this->post_points += $points;
                $this->threads_count++;
                break;
            case 'vote_received':
                $this->vote_points += $points;
                $this->votes_received++;
                break;
            case 'solution_marked':
                $this->solution_points += $points;
                $this->solutions_provided++;
                break;
            case 'daily_activity':
            case 'consecutive_days':
                // These don't have specific categories
                break;
        }

        // Update total points
        $this->total_points += $points;
        
        // Update rank if necessary
        $this->updateRank();
        
        // Update activity tracking
        $this->updateActivityTracking();
        
        $this->save();

        // Record in history
        ForumReputationHistory::create([
            'user_id' => $this->user_id,
            'action' => $action,
            'points_change' => $points,
            'points_before' => $pointsBefore,
            'points_after' => $this->total_points,
            'source_type' => $context['source_type'] ?? null,
            'source_id' => $context['source_id'] ?? null,
            'description' => $context['description'] ?? null,
            'metadata' => $context['metadata'] ?? null,
        ]);
    }

    /**
     * Update user's rank based on total points
     */
    public function updateRank(): void
    {
        $newRank = $this->calculateRank($this->total_points);
        
        if ($newRank['level'] !== $this->rank_level) {
            $this->rank = $newRank['name'];
            $this->rank_level = $newRank['level'];
        }
    }

    /**
     * Calculate rank based on points
     */
    public function calculateRank(int $points): array
    {
        $rank = self::RANKS[1]; // Default to lowest rank
        $level = 1;
        
        foreach (self::RANKS as $rankLevel => $rankData) {
            if ($points >= $rankData['min_points']) {
                $rank = $rankData;
                $level = $rankLevel;
            }
        }
        
        return [
            'name' => $rank['name'],
            'level' => $level,
            'color' => $rank['color'],
            'min_points' => $rank['min_points'],
        ];
    }

    /**
     * Get progress to next rank
     */
    public function getNextRankProgress(): array
    {
        $currentRank = $this->calculateRank($this->total_points);
        $nextLevel = $currentRank['level'] + 1;
        
        if (!isset(self::RANKS[$nextLevel])) {
            return [
                'is_max_rank' => true,
                'progress_percentage' => 100,
                'points_needed' => 0,
                'next_rank' => null,
            ];
        }
        
        $nextRank = self::RANKS[$nextLevel];
        $pointsNeeded = $nextRank['min_points'] - $this->total_points;
        $pointsInCurrentRange = $this->total_points - $currentRank['min_points'];
        $totalPointsInRange = $nextRank['min_points'] - $currentRank['min_points'];
        $progressPercentage = ($pointsInCurrentRange / $totalPointsInRange) * 100;
        
        return [
            'is_max_rank' => false,
            'progress_percentage' => min(100, max(0, $progressPercentage)),
            'points_needed' => $pointsNeeded,
            'next_rank' => $nextRank,
        ];
    }

    /**
     * Update activity tracking
     */
    public function updateActivityTracking(): void
    {
        $today = Carbon::today();
        
        if ($this->last_activity_date === null) {
            $this->consecutive_days_active = 1;
            $this->last_activity_date = $today;
        } elseif ($this->last_activity_date->isYesterday()) {
            $this->consecutive_days_active++;
            $this->last_activity_date = $today;
            
            // Award bonus points for consecutive days
            if ($this->consecutive_days_active % 7 === 0) { // Weekly bonus
                $this->awardPoints('consecutive_days', self::POINT_VALUES['consecutive_days'], [
                    'description' => "Consecutive activity bonus: {$this->consecutive_days_active} days",
                ]);
            }
        } elseif (!$this->last_activity_date->isToday()) {
            $this->consecutive_days_active = 1;
            $this->last_activity_date = $today;
        }
    }

    /**
     * Get user's leaderboard position
     */
    public function getLeaderboardPosition(): int
    {
        return self::where('total_points', '>', $this->total_points)->count() + 1;
    }

    /**
     * Get top users for leaderboard
     */
    public static function getLeaderboard(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('user')
            ->orderBy('total_points', 'desc')
            ->orderBy('rank_level', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $currentRank = $this->calculateRank($this->total_points);
        $nextRankProgress = $this->getNextRankProgress();
        
        return [
            'total_points' => $this->total_points,
            'current_rank' => $currentRank,
            'next_rank_progress' => $nextRankProgress,
            'leaderboard_position' => $this->getLeaderboardPosition(),
            'activity_stats' => [
                'posts_count' => $this->posts_count,
                'threads_count' => $this->threads_count,
                'votes_received' => $this->votes_received,
                'solutions_provided' => $this->solutions_provided,
                'consecutive_days_active' => $this->consecutive_days_active,
            ],
            'point_breakdown' => [
                'post_points' => $this->post_points,
                'vote_points' => $this->vote_points,
                'solution_points' => $this->solution_points,
                'badge_points' => $this->badge_points,
            ],
        ];
    }

    /**
     * Create or get reputation record for user
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_points' => 0,
                'rank' => self::RANKS[1]['name'],
                'rank_level' => 1,
            ]
        );
    }
}