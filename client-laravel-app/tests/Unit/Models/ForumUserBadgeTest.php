<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\ForumBadge;
use App\Models\ForumUserBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ForumUserBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_user_badge()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        
        $userBadge = ForumUserBadge::create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now(),
            'earning_context' => ['source' => 'test'],
            'is_featured' => true,
        ]);

        $this->assertInstanceOf(ForumUserBadge::class, $userBadge);
        $this->assertEquals($user->id, $userBadge->user_id);
        $this->assertEquals($badge->id, $userBadge->forum_badge_id);
        $this->assertTrue($userBadge->is_featured);
        $this->assertEquals(['source' => 'test'], $userBadge->earning_context);
    }

    public function test_belongs_to_user()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        $userBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
        ]);

        $this->assertInstanceOf(User::class, $userBadge->user);
        $this->assertEquals($user->id, $userBadge->user->id);
    }

    public function test_belongs_to_badge()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        $userBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
        ]);

        $this->assertInstanceOf(ForumBadge::class, $userBadge->badge);
        $this->assertEquals($badge->id, $userBadge->badge->id);
    }

    public function test_scope_featured()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        
        $featuredBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'is_featured' => true,
        ]);
        
        $nonFeaturedBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'is_featured' => false,
        ]);

        $featuredBadges = ForumUserBadge::featured()->get();
        
        $this->assertCount(1, $featuredBadges);
        $this->assertEquals($featuredBadge->id, $featuredBadges->first()->id);
    }

    public function test_scope_recent()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        
        $recentBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now()->subDays(3),
        ]);
        
        $oldBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now()->subDays(10),
        ]);

        $recentBadges = ForumUserBadge::recent(7)->get();
        
        $this->assertCount(1, $recentBadges);
        $this->assertEquals($recentBadge->id, $recentBadges->first()->id);
    }

    public function test_get_featured_for_user()
    {
        $user = User::factory()->create();
        $badges = ForumBadge::factory()->count(5)->create();
        
        // Create 3 featured badges and 2 non-featured
        foreach ($badges->take(3) as $badge) {
            ForumUserBadge::factory()->create([
                'user_id' => $user->id,
                'forum_badge_id' => $badge->id,
                'is_featured' => true,
                'earned_at' => now()->subDays(rand(1, 5)),
            ]);
        }
        
        foreach ($badges->skip(3) as $badge) {
            ForumUserBadge::factory()->create([
                'user_id' => $user->id,
                'forum_badge_id' => $badge->id,
                'is_featured' => false,
            ]);
        }

        $featuredBadges = ForumUserBadge::getFeaturedForUser($user->id, 2);
        
        $this->assertCount(2, $featuredBadges);
        foreach ($featuredBadges as $userBadge) {
            $this->assertTrue($userBadge->is_featured);
            $this->assertEquals($user->id, $userBadge->user_id);
        }
    }

    public function test_get_recent_for_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        
        // Create recent badges for user1
        ForumUserBadge::factory()->count(3)->create([
            'user_id' => $user1->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now()->subDays(rand(1, 10)),
        ]);
        
        // Create old badge for user1
        ForumUserBadge::factory()->create([
            'user_id' => $user1->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now()->subDays(40),
        ]);
        
        // Create badge for user2
        ForumUserBadge::factory()->create([
            'user_id' => $user2->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now()->subDays(5),
        ]);

        $recentBadges = ForumUserBadge::getRecentForUser($user1->id, 30, 5);
        
        $this->assertCount(3, $recentBadges);
        foreach ($recentBadges as $userBadge) {
            $this->assertEquals($user1->id, $userBadge->user_id);
            $this->assertTrue($userBadge->earned_at->greaterThan(now()->subDays(30)));
        }
    }

    public function test_toggle_featured()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        $userBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'is_featured' => false,
        ]);

        $this->assertFalse($userBadge->is_featured);
        
        $userBadge->toggleFeatured();
        
        $this->assertTrue($userBadge->is_featured);
        
        $userBadge->toggleFeatured();
        
        $this->assertFalse($userBadge->is_featured);
    }

    public function test_earned_at_is_cast_to_datetime()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        $earnedAt = '2024-01-15 10:30:00';
        
        $userBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => $earnedAt,
        ]);

        $this->assertInstanceOf(Carbon::class, $userBadge->earned_at);
        $this->assertEquals('2024-01-15 10:30:00', $userBadge->earned_at->format('Y-m-d H:i:s'));
    }

    public function test_earning_context_is_cast_to_array()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        $context = ['source' => 'forum_post', 'post_id' => 123];
        
        $userBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earning_context' => $context,
        ]);

        $this->assertIsArray($userBadge->earning_context);
        $this->assertEquals($context, $userBadge->earning_context);
    }

    public function test_is_featured_is_cast_to_boolean()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        
        $userBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'is_featured' => 1,
        ]);

        $this->assertIsBool($userBadge->is_featured);
        $this->assertTrue($userBadge->is_featured);
    }

    public function test_get_featured_for_user_orders_by_earned_at_desc()
    {
        $user = User::factory()->create();
        $badges = ForumBadge::factory()->count(3)->create();
        
        $firstBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badges[0]->id,
            'is_featured' => true,
            'earned_at' => now()->subDays(3),
        ]);
        
        $secondBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badges[1]->id,
            'is_featured' => true,
            'earned_at' => now()->subDays(1), // Most recent
        ]);
        
        $thirdBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badges[2]->id,
            'is_featured' => true,
            'earned_at' => now()->subDays(2),
        ]);

        $featuredBadges = ForumUserBadge::getFeaturedForUser($user->id, 3);
        
        $this->assertEquals($secondBadge->id, $featuredBadges[0]->id);
        $this->assertEquals($thirdBadge->id, $featuredBadges[1]->id);
        $this->assertEquals($firstBadge->id, $featuredBadges[2]->id);
    }

    public function test_get_recent_for_user_orders_by_earned_at_desc()
    {
        $user = User::factory()->create();
        $badge = ForumBadge::factory()->create();
        
        $firstBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now()->subDays(5),
        ]);
        
        $secondBadge = ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now()->subDays(2), // Most recent
        ]);

        $recentBadges = ForumUserBadge::getRecentForUser($user->id, 30, 5);
        
        $this->assertEquals($secondBadge->id, $recentBadges[0]->id);
        $this->assertEquals($firstBadge->id, $recentBadges[1]->id);
    }
}