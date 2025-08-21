<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\ForumBadge;
use App\Models\ForumUserBadge;
use App\Models\ForumUserReputation;
use App\Models\ForumPost;
use App\Models\ForumVote;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_badge()
    {
        $badge = ForumBadge::create([
            'name' => 'Test Badge',
            'slug' => 'test-badge',
            'description' => 'A test badge',
            'type' => 'activity',
            'rarity' => 'common',
            'points_value' => 10,
            'criteria' => ['posts_count' => 5],
        ]);

        $this->assertInstanceOf(ForumBadge::class, $badge);
        $this->assertEquals('Test Badge', $badge->name);
        $this->assertEquals('test-badge', $badge->slug);
        $this->assertEquals(['posts_count' => 5], $badge->criteria);
    }

    public function test_has_many_user_badges()
    {
        $badge = ForumBadge::factory()->create();
        $users = User::factory()->count(3)->create();
        
        foreach ($users as $user) {
            ForumUserBadge::create([
                'user_id' => $user->id,
                'forum_badge_id' => $badge->id,
                'earned_at' => now(),
            ]);
        }

        $this->assertCount(3, $badge->userBadges);
    }

    public function test_belongs_to_many_users()
    {
        $badge = ForumBadge::factory()->create();
        $users = User::factory()->count(2)->create();
        
        foreach ($users as $user) {
            ForumUserBadge::create([
                'user_id' => $user->id,
                'forum_badge_id' => $badge->id,
                'earned_at' => now(),
            ]);
        }

        $this->assertCount(2, $badge->users);
    }

    public function test_check_criteria_posts_count()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 10,
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'Contributor',
            'slug' => 'contributor',
            'description' => 'Made 5 posts',
            'type' => 'activity',
            'rarity' => 'common',
            'criteria' => ['posts_count' => 5],
            'is_active' => true,
        ]);

        $this->assertTrue($badge->checkCriteria($user));
    }

    public function test_check_criteria_fails_when_not_met()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 3,
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'Contributor',
            'slug' => 'contributor',
            'description' => 'Made 5 posts',
            'type' => 'activity',
            'rarity' => 'common',
            'criteria' => ['posts_count' => 5],
            'is_active' => true,
        ]);

        $this->assertFalse($badge->checkCriteria($user));
    }

    public function test_check_criteria_multiple_requirements()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 10,
            'votes_received' => 20,
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'Popular Contributor',
            'slug' => 'popular-contributor',
            'description' => 'Made 5 posts and received 15 votes',
            'type' => 'achievement',
            'rarity' => 'uncommon',
            'criteria' => [
                'posts_count' => 5,
                'votes_received' => 15,
            ],
            'is_active' => true,
        ]);

        $this->assertTrue($badge->checkCriteria($user));
    }

    public function test_check_criteria_fails_when_one_requirement_not_met()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 10,
            'votes_received' => 5, // Not enough votes
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'Popular Contributor',
            'slug' => 'popular-contributor',
            'description' => 'Made 5 posts and received 15 votes',
            'type' => 'achievement',
            'rarity' => 'uncommon',
            'criteria' => [
                'posts_count' => 5,
                'votes_received' => 15,
            ],
            'is_active' => true,
        ]);

        $this->assertFalse($badge->checkCriteria($user));
    }

    public function test_check_criteria_inactive_badge()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 10,
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'Contributor',
            'slug' => 'contributor',
            'description' => 'Made 5 posts',
            'type' => 'activity',
            'rarity' => 'common',
            'criteria' => ['posts_count' => 5],
            'is_active' => false, // Inactive badge
        ]);

        $this->assertFalse($badge->checkCriteria($user));
    }

    public function test_check_criteria_first_post()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 1,
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'First Steps',
            'slug' => 'first-steps',
            'description' => 'Created first post',
            'type' => 'milestone',
            'rarity' => 'common',
            'criteria' => ['first_post' => true],
            'is_active' => true,
        ]);

        $this->assertTrue($badge->checkCriteria($user));
    }

    public function test_award_to_user_creates_user_badge()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create(['points_value' => 10]);
        
        $userBadge = $badge->awardToUser($user);
        
        $this->assertInstanceOf(ForumUserBadge::class, $userBadge);
        $this->assertEquals($user->id, $userBadge->user_id);
        $this->assertEquals($badge->id, $userBadge->forum_badge_id);
    }

    public function test_award_to_user_updates_awarded_count()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create(['awarded_count' => 0]);
        
        $badge->awardToUser($user);
        $badge->refresh();
        
        $this->assertEquals(1, $badge->awarded_count);
    }

    public function test_award_to_user_awards_points()
    {
        $user = User::factory()->create();
        ForumUserReputation::create(['user_id' => $user->id, 'total_points' => 0]);
        
        $badge = ForumBadge::factory()->create(['points_value' => 25]);
        
        $badge->awardToUser($user);
        
        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertEquals(25, $reputation->total_points);
        $this->assertEquals(25, $reputation->badge_points);
    }

    public function test_award_to_user_prevents_duplicate_awards()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        
        // Award badge first time
        $firstAward = $badge->awardToUser($user);
        $this->assertNotNull($firstAward);
        
        // Try to award same badge again
        $secondAward = $badge->awardToUser($user);
        $this->assertNull($secondAward);
    }

    public function test_get_default_badges()
    {
        $defaultBadges = ForumBadge::getDefaultBadges();
        
        $this->assertIsArray($defaultBadges);
        $this->assertGreaterThan(0, count($defaultBadges));
        
        $firstBadge = $defaultBadges[0];
        $this->assertArrayHasKey('name', $firstBadge);
        $this->assertArrayHasKey('slug', $firstBadge);
        $this->assertArrayHasKey('criteria', $firstBadge);
    }

    public function test_check_and_award_badges()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 1,
        ]);
        
        // Create a badge for first post
        ForumBadge::create([
            'name' => 'First Steps',
            'slug' => 'first-steps',
            'description' => 'Created first post',
            'type' => 'milestone',
            'rarity' => 'common',
            'criteria' => ['first_post' => true],
            'points_value' => 10,
            'is_active' => true,
        ]);
        
        $awardedBadges = ForumBadge::checkAndAwardBadges($user);
        
        $this->assertCount(1, $awardedBadges);
        $this->assertEquals('First Steps', $awardedBadges[0]->name);
        
        // Check that user badge was created
        $userBadge = ForumUserBadge::where('user_id', $user->id)->first();
        $this->assertNotNull($userBadge);
    }

    public function test_get_rarity_info()
    {
        $badge = ForumBadge::factory()->create(['rarity' => 'epic']);
        
        $rarityInfo = $badge->getRarityInfo();
        
        $this->assertEquals('Epic', $rarityInfo['name']);
        $this->assertEquals('#8B5CF6', $rarityInfo['color']);
    }

    public function test_get_rarity_info_defaults_to_common()
    {
        $badge = ForumBadge::factory()->create(['rarity' => 'invalid']);
        
        $rarityInfo = $badge->getRarityInfo();
        
        $this->assertEquals('Common', $rarityInfo['name']);
        $this->assertEquals('#9CA3AF', $rarityInfo['color']);
    }

    public function test_criteria_evaluation_total_points()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'total_points' => 150,
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'Point Collector',
            'slug' => 'point-collector',
            'description' => 'Earned 100 points',
            'type' => 'milestone',
            'rarity' => 'uncommon',
            'criteria' => ['total_points' => 100],
            'is_active' => true,
        ]);

        $this->assertTrue($badge->checkCriteria($user));
    }

    public function test_criteria_evaluation_rank_level()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'rank_level' => 3,
        ]);
        
        $badge = ForumBadge::create([
            'name' => 'Rank Achiever',
            'slug' => 'rank-achiever',
            'description' => 'Reached rank level 3',
            'type' => 'milestone',
            'rarity' => 'rare',
            'criteria' => ['rank_level' => 3],
            'is_active' => true,
        ]);

        $this->assertTrue($badge->checkCriteria($user));
    }
}