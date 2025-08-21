<?php

namespace App\Services;

use App\Models\User;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\ForumVote;
use App\Models\ForumUserReputation;
use App\Models\ForumBadge;
use App\Models\ForumUserBadge;
use App\Models\ForumReputationHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForumGamificationService
{
    /**
     * Handle post creation gamification
     */
    public function handlePostCreated(ForumPost $post): void
    {
        try {
            DB::beginTransaction();

            $reputation = ForumUserReputation::getOrCreateForUser($post->user_id);
            
            // Award points for post creation
            $reputation->awardPoints('post_created', null, [
                'source_type' => 'forum_post',
                'source_id' => $post->id,
                'description' => 'Created a forum post',
            ]);

            // Check and award badges
            $this->checkAndAwardBadges($post->user);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling post creation gamification: ' . $e->getMessage());
        }
    }

    /**
     * Handle thread creation gamification
     */
    public function handleThreadCreated(ForumThread $thread): void
    {
        try {
            DB::beginTransaction();

            $reputation = ForumUserReputation::getOrCreateForUser($thread->user_id);
            
            // Award points for thread creation
            $reputation->awardPoints('thread_created', null, [
                'source_type' => 'forum_thread',
                'source_id' => $thread->id,
                'description' => 'Started a forum thread',
            ]);

            // Check and award badges
            $this->checkAndAwardBadges($thread->user);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling thread creation gamification: ' . $e->getMessage());
        }
    }

    /**
     * Handle vote received gamification
     */
    public function handleVoteReceived(ForumVote $vote): void
    {
        try {
            DB::beginTransaction();

            // Get the user who received the vote
            $voteable = $vote->voteable;
            if (!$voteable || !isset($voteable->user_id)) {
                return;
            }

            $reputation = ForumUserReputation::getOrCreateForUser($voteable->user_id);
            
            // Award points for receiving a vote (only for upvotes)
            if ($vote->vote_type === 'up') {
                $reputation->awardPoints('vote_received', null, [
                    'source_type' => get_class($voteable),
                    'source_id' => $voteable->id,
                    'description' => 'Received an upvote',
                    'metadata' => ['voter_id' => $vote->user_id],
                ]);

                // Check and award badges
                $this->checkAndAwardBadges($voteable->user);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling vote received gamification: ' . $e->getMessage());
        }
    }

    /**
     * Handle solution marked gamification
     */
    public function handleSolutionMarked(ForumPost $post): void
    {
        try {
            DB::beginTransaction();

            $reputation = ForumUserReputation::getOrCreateForUser($post->user_id);
            
            // Award points for having solution marked
            $reputation->awardPoints('solution_marked', null, [
                'source_type' => 'forum_post',
                'source_id' => $post->id,
                'description' => 'Post marked as solution',
            ]);

            // Check and award badges
            $this->checkAndAwardBadges($post->user);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling solution marked gamification: ' . $e->getMessage());
        }
    }

    /**
     * Handle daily activity tracking
     */
    public function handleDailyActivity(User $user): void
    {
        try {
            $reputation = ForumUserReputation::getOrCreateForUser($user->id);
            
            // Award daily activity points (this is handled in updateActivityTracking)
            $reputation->awardPoints('daily_activity', null, [
                'description' => 'Daily forum activity',
            ]);

            // Check and award badges
            $this->checkAndAwardBadges($user);
        } catch (\Exception $e) {
            Log::error('Error handling daily activity gamification: ' . $e->getMessage());
        }
    }

    /**
     * Check and award badges for user
     */
    public function checkAndAwardBadges(User $user): array
    {
        return ForumBadge::checkAndAwardBadges($user);
    }

    /**
     * Get user's gamification dashboard data
     */
    public function getUserDashboard(User $user): array
    {
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        $statistics = $reputation->getStatistics();
        
        // Get recent badges
        $recentBadges = ForumUserBadge::getRecentForUser($user->id, 30, 5);
        
        // Get featured badges
        $featuredBadges = ForumUserBadge::getFeaturedForUser($user->id, 3);
        
        // Get recent reputation history
        $recentHistory = ForumReputationHistory::getForUser($user->id, 10);
        
        // Get recent gains
        $weeklyGains = ForumReputationHistory::getRecentGains($user->id, 7);
        $monthlyGains = ForumReputationHistory::getRecentGains($user->id, 30);
        
        return [
            'reputation' => $statistics,
            'recent_badges' => $recentBadges,
            'featured_badges' => $featuredBadges,
            'recent_history' => $recentHistory,
            'gains' => [
                'weekly' => $weeklyGains,
                'monthly' => $monthlyGains,
            ],
        ];
    }

    /**
     * Get leaderboard data
     */
    public function getLeaderboard(int $limit = 10): array
    {
        $topUsers = ForumUserReputation::getLeaderboard($limit);
        
        return [
            'top_users' => $topUsers,
            'total_users' => ForumUserReputation::count(),
        ];
    }

    /**
     * Get badge statistics
     */
    public function getBadgeStatistics(): array
    {
        $badges = ForumBadge::where('is_active', true)->get();
        
        $statistics = [
            'total_badges' => $badges->count(),
            'by_type' => $badges->groupBy('type')->map->count(),
            'by_rarity' => $badges->groupBy('rarity')->map->count(),
            'most_awarded' => $badges->sortByDesc('awarded_count')->take(5),
            'least_awarded' => $badges->where('awarded_count', '>', 0)->sortBy('awarded_count')->take(5),
        ];
        
        return $statistics;
    }

    /**
     * Seed default badges
     */
    public function seedDefaultBadges(): void
    {
        $defaultBadges = ForumBadge::getDefaultBadges();
        
        foreach ($defaultBadges as $badgeData) {
            ForumBadge::updateOrCreate(
                ['slug' => $badgeData['slug']],
                $badgeData
            );
        }
    }

    /**
     * Recalculate user reputation (for maintenance)
     */
    public function recalculateUserReputation(User $user): void
    {
        try {
            DB::beginTransaction();

            $reputation = ForumUserReputation::getOrCreateForUser($user->id);
            
            // Reset counters
            $reputation->posts_count = ForumPost::where('user_id', $user->id)->count();
            $reputation->threads_count = ForumThread::where('user_id', $user->id)->count();
            
            // Count votes received
            $votesReceived = 0;
            $userPosts = ForumPost::where('user_id', $user->id)->get();
            foreach ($userPosts as $post) {
                $votesReceived += ForumVote::where('voteable_type', ForumPost::class)
                    ->where('voteable_id', $post->id)
                    ->where('vote_type', 'up')
                    ->count();
            }
            $reputation->votes_received = $votesReceived;
            
            // Count solutions provided
            $reputation->solutions_provided = ForumPost::where('user_id', $user->id)
                ->where('is_solution', true)
                ->count();
            
            // Recalculate points based on activities
            $reputation->post_points = $reputation->posts_count * ForumUserReputation::POINT_VALUES['post_created'];
            $reputation->vote_points = $reputation->votes_received * ForumUserReputation::POINT_VALUES['vote_received'];
            $reputation->solution_points = $reputation->solutions_provided * ForumUserReputation::POINT_VALUES['solution_marked'];
            
            // Calculate badge points
            $badgePoints = ForumUserBadge::join('forum_badges', 'forum_user_badges.forum_badge_id', '=', 'forum_badges.id')
                ->where('forum_user_badges.user_id', $user->id)
                ->sum('forum_badges.points_value');
            $reputation->badge_points = $badgePoints;
            
            // Calculate total points
            $reputation->total_points = $reputation->post_points + $reputation->vote_points + 
                                      $reputation->solution_points + $reputation->badge_points;
            
            // Update rank
            $reputation->updateRank();
            $reputation->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recalculating user reputation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user's progress towards next badges
     */
    public function getUserBadgeProgress(User $user): array
    {
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        $availableBadges = ForumBadge::where('is_active', true)
            ->whereNotIn('id', function($query) use ($user) {
                $query->select('forum_badge_id')
                      ->from('forum_user_badges')
                      ->where('user_id', $user->id);
            })
            ->get();

        $progress = [];
        
        foreach ($availableBadges as $badge) {
            $badgeProgress = $this->calculateBadgeProgress($badge, $user, $reputation);
            if ($badgeProgress['progress_percentage'] > 0) {
                $progress[] = $badgeProgress;
            }
        }
        
        // Sort by progress percentage descending
        usort($progress, function($a, $b) {
            return $b['progress_percentage'] <=> $a['progress_percentage'];
        });
        
        return array_slice($progress, 0, 5); // Return top 5 closest badges
    }

    /**
     * Calculate progress towards a specific badge
     */
    private function calculateBadgeProgress(ForumBadge $badge, User $user, ForumUserReputation $reputation): array
    {
        $progress = [
            'badge' => $badge,
            'progress_percentage' => 0,
            'requirements' => [],
        ];

        foreach ($badge->criteria as $criterion => $targetValue) {
            $currentValue = $this->getCurrentValueForCriterion($criterion, $user, $reputation);
            $requirementProgress = min(100, ($currentValue / $targetValue) * 100);
            
            $progress['requirements'][] = [
                'criterion' => $criterion,
                'current_value' => $currentValue,
                'target_value' => $targetValue,
                'progress_percentage' => $requirementProgress,
                'is_met' => $currentValue >= $targetValue,
            ];
            
            // Overall progress is the minimum of all requirements
            if ($progress['progress_percentage'] === 0) {
                $progress['progress_percentage'] = $requirementProgress;
            } else {
                $progress['progress_percentage'] = min($progress['progress_percentage'], $requirementProgress);
            }
        }

        return $progress;
    }

    /**
     * Get current value for a criterion
     */
    private function getCurrentValueForCriterion(string $criterion, User $user, ForumUserReputation $reputation): int
    {
        switch ($criterion) {
            case 'total_points':
                return $reputation->total_points;
            case 'posts_count':
                return $reputation->posts_count;
            case 'threads_count':
                return $reputation->threads_count;
            case 'votes_received':
                return $reputation->votes_received;
            case 'solutions_provided':
                return $reputation->solutions_provided;
            case 'consecutive_days_active':
                return $reputation->consecutive_days_active;
            case 'rank_level':
                return $reputation->rank_level;
            case 'forum_posts_in_day':
                return ForumPost::where('user_id', $user->id)
                    ->whereDate('created_at', today())
                    ->count();
            case 'forum_votes_in_week':
                return ForumVote::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subWeek())
                    ->count();
            default:
                return 0;
        }
    }
}