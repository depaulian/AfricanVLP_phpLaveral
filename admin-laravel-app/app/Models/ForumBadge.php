<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ForumBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'type',
        'rarity',
        'points_value',
        'criteria',
        'is_active',
        'awarded_count',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
    ];

    // Badge types
    public const TYPES = [
        'activity' => 'Activity',
        'achievement' => 'Achievement',
        'milestone' => 'Milestone',
        'special' => 'Special',
    ];

    // Badge rarities with colors
    public const RARITIES = [
        'common' => ['name' => 'Common', 'color' => '#9CA3AF'],
        'uncommon' => ['name' => 'Uncommon', 'color' => '#10B981'],
        'rare' => ['name' => 'Rare', 'color' => '#3B82F6'],
        'epic' => ['name' => 'Epic', 'color' => '#8B5CF6'],
        'legendary' => ['name' => 'Legendary', 'color' => '#F59E0B'],
    ];

    /**
     * Relationships
     */
    public function userBadges(): HasMany
    {
        return $this->hasMany(ForumUserBadge::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'forum_user_badges')
            ->withPivot(['earned_at', 'earning_context', 'is_featured'])
            ->withTimestamps();
    }

    /**
     * Check if user meets criteria for this badge
     */
    public function checkCriteria(User $user): bool
    {
        if (!$this->is_active || !$this->criteria) {
            return false;
        }

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        if (!$reputation) {
            return false;
        }

        foreach ($this->criteria as $criterion => $value) {
            if (!$this->evaluateCriterion($criterion, $value, $user, $reputation)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a specific criterion
     */
    private function evaluateCriterion(string $criterion, $value, User $user, ForumUserReputation $reputation): bool
    {
        switch ($criterion) {
            case 'total_points':
                return $reputation->total_points >= $value;
            
            case 'posts_count':
                return $reputation->posts_count >= $value;
            
            case 'threads_count':
                return $reputation->threads_count >= $value;
            
            case 'votes_received':
                return $reputation->votes_received >= $value;
            
            case 'solutions_provided':
                return $reputation->solutions_provided >= $value;
            
            case 'consecutive_days_active':
                return $reputation->consecutive_days_active >= $value;
            
            case 'rank_level':
                return $reputation->rank_level >= $value;
            
            case 'forum_posts_in_day':
                return ForumPost::where('user_id', $user->id)
                    ->whereDate('created_at', today())
                    ->count() >= $value;
            
            case 'forum_votes_in_week':
                return ForumVote::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subWeek())
                    ->count() >= $value;
            
            case 'first_post':
                return $reputation->posts_count === 1;
            
            case 'first_thread':
                return $reputation->threads_count === 1;
            
            default:
                return false;
        }
    }

    /**
     * Award badge to user
     */
    public function awardToUser(User $user, array $context = []): ?ForumUserBadge
    {
        // Check if user already has this badge
        if ($this->users()->where('user_id', $user->id)->exists()) {
            return null;
        }

        // Create user badge record
        $userBadge = ForumUserBadge::create([
            'user_id' => $user->id,
            'forum_badge_id' => $this->id,
            'earned_at' => now(),
            'earning_context' => $context,
        ]);

        // Update badge awarded count
        $this->increment('awarded_count');

        // Award points to user's reputation
        if ($this->points_value > 0) {
            $reputation = ForumUserReputation::getOrCreateForUser($user->id);
            $reputation->badge_points += $this->points_value;
            $reputation->total_points += $this->points_value;
            $reputation->updateRank();
            $reputation->save();

            // Record in reputation history
            ForumReputationHistory::create([
                'user_id' => $user->id,
                'action' => 'badge_earned',
                'points_change' => $this->points_value,
                'points_before' => $reputation->total_points - $this->points_value,
                'points_after' => $reputation->total_points,
                'source_type' => 'forum_badge',
                'source_id' => $this->id,
                'description' => "Earned badge: {$this->name}",
                'metadata' => ['badge_slug' => $this->slug],
            ]);
        }

        return $userBadge;
    }

    /**
     * Get default badges to seed
     */
    public static function getDefaultBadges(): array
    {
        return [
            [
                'name' => 'First Steps',
                'slug' => 'first-steps',
                'description' => 'Created your first forum post',
                'icon' => 'fas fa-baby',
                'color' => '#10B981',
                'type' => 'milestone',
                'rarity' => 'common',
                'points_value' => 10,
                'criteria' => ['first_post' => true],
            ],
            [
                'name' => 'Thread Starter',
                'slug' => 'thread-starter',
                'description' => 'Started your first discussion thread',
                'icon' => 'fas fa-comments',
                'color' => '#3B82F6',
                'type' => 'milestone',
                'rarity' => 'common',
                'points_value' => 15,
                'criteria' => ['first_thread' => true],
            ],
            [
                'name' => 'Contributor',
                'slug' => 'contributor',
                'description' => 'Made 10 helpful posts',
                'icon' => 'fas fa-hand-helping',
                'color' => '#8B5CF6',
                'type' => 'activity',
                'rarity' => 'uncommon',
                'points_value' => 25,
                'criteria' => ['posts_count' => 10],
            ],
            [
                'name' => 'Problem Solver',
                'slug' => 'problem-solver',
                'description' => 'Provided 5 accepted solutions',
                'icon' => 'fas fa-lightbulb',
                'color' => '#F59E0B',
                'type' => 'achievement',
                'rarity' => 'rare',
                'points_value' => 50,
                'criteria' => ['solutions_provided' => 5],
            ],
            [
                'name' => 'Popular Voice',
                'slug' => 'popular-voice',
                'description' => 'Received 50 upvotes on your posts',
                'icon' => 'fas fa-thumbs-up',
                'color' => '#EF4444',
                'type' => 'achievement',
                'rarity' => 'rare',
                'points_value' => 40,
                'criteria' => ['votes_received' => 50],
            ],
            [
                'name' => 'Dedicated Member',
                'slug' => 'dedicated-member',
                'description' => 'Active for 30 consecutive days',
                'icon' => 'fas fa-calendar-check',
                'color' => '#DC2626',
                'type' => 'activity',
                'rarity' => 'epic',
                'points_value' => 75,
                'criteria' => ['consecutive_days_active' => 30],
            ],
            [
                'name' => 'Forum Expert',
                'slug' => 'forum-expert',
                'description' => 'Reached Expert rank',
                'icon' => 'fas fa-crown',
                'color' => '#F59E0B',
                'type' => 'milestone',
                'rarity' => 'epic',
                'points_value' => 100,
                'criteria' => ['rank_level' => 5],
            ],
            [
                'name' => 'Legend',
                'slug' => 'legend',
                'description' => 'Achieved legendary status',
                'icon' => 'fas fa-trophy',
                'color' => '#DC2626',
                'type' => 'milestone',
                'rarity' => 'legendary',
                'points_value' => 200,
                'criteria' => ['rank_level' => 7],
            ],
            [
                'name' => 'Daily Warrior',
                'slug' => 'daily-warrior',
                'description' => 'Made 5 posts in a single day',
                'icon' => 'fas fa-fire',
                'color' => '#EF4444',
                'type' => 'activity',
                'rarity' => 'uncommon',
                'points_value' => 20,
                'criteria' => ['forum_posts_in_day' => 5],
            ],
            [
                'name' => 'Community Supporter',
                'slug' => 'community-supporter',
                'description' => 'Cast 25 votes in a week',
                'icon' => 'fas fa-heart',
                'color' => '#EC4899',
                'type' => 'activity',
                'rarity' => 'uncommon',
                'points_value' => 30,
                'criteria' => ['forum_votes_in_week' => 25],
            ],
        ];
    }

    /**
     * Check and award badges for user
     */
    public static function checkAndAwardBadges(User $user): array
    {
        $awardedBadges = [];
        
        $badges = self::where('is_active', true)->get();
        
        foreach ($badges as $badge) {
            if ($badge->checkCriteria($user)) {
                $userBadge = $badge->awardToUser($user);
                if ($userBadge) {
                    $awardedBadges[] = $badge;
                }
            }
        }
        
        return $awardedBadges;
    }

    /**
     * Get rarity info
     */
    public function getRarityInfo(): array
    {
        return self::RARITIES[$this->rarity] ?? self::RARITIES['common'];
    }
}