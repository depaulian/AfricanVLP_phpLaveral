<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\ForumUserReputation;
use App\Models\ForumReputationHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ForumUserReputationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_reputation_record()
    {
        $user = User::factory()->create();
        
        $reputation = ForumUserReputation::create([
            'user_id' => $user->id,
            'total_points' => 100,
            'rank' => 'Contributor',
            'rank_level' => 2,
        ]);

        $this->assertInstanceOf(ForumUserReputation::class, $reputation);
        $this->assertEquals($user->id, $reputation->user_id);
        $this->assertEquals(100, $reputation->total_points);
        $this->assertEquals('Contributor', $reputation->rank);
    }

    public function test_belongs_to_user()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $reputation->user);
        $this->assertEquals($user->id, $reputation->user->id);
    }

    public function test_has_many_reputation_history()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::factory()->create(['user_id' => $user->id]);
        
        ForumReputationHistory::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $reputation->reputationHistory);
    }

    public function test_award_points_for_post_created()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        $initialPoints = $reputation->total_points;
        $reputation->awardPoints('post_created');

        $this->assertEquals($initialPoints + 5, $reputation->total_points);
        $this->assertEquals(1, $reputation->posts_count);
        $this->assertEquals(5, $reputation->post_points);
    }

    public function test_award_points_for_vote_received()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        $initialPoints = $reputation->total_points;
        $reputation->awardPoints('vote_received');

        $this->assertEquals($initialPoints + 2, $reputation->total_points);
        $this->assertEquals(1, $reputation->votes_received);
        $this->assertEquals(2, $reputation->vote_points);
    }

    public function test_award_points_for_solution_marked()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        $initialPoints = $reputation->total_points;
        $reputation->awardPoints('solution_marked');

        $this->assertEquals($initialPoints + 25, $reputation->total_points);
        $this->assertEquals(1, $reputation->solutions_provided);
        $this->assertEquals(25, $reputation->solution_points);
    }

    public function test_calculate_rank_newcomer()
    {
        $rank = ForumUserReputation::calculateRank(0);
        
        $this->assertEquals('Newcomer', $rank['name']);
        $this->assertEquals(1, $rank['level']);
        $this->assertEquals('#9CA3AF', $rank['color']);
    }

    public function test_calculate_rank_contributor()
    {
        $rank = ForumUserReputation::calculateRank(150);
        
        $this->assertEquals('Contributor', $rank['name']);
        $this->assertEquals(2, $rank['level']);
        $this->assertEquals('#10B981', $rank['color']);
    }

    public function test_calculate_rank_legend()
    {
        $rank = ForumUserReputation::calculateRank(15000);
        
        $this->assertEquals('Legend', $rank['name']);
        $this->assertEquals(7, $rank['level']);
        $this->assertEquals('#DC2626', $rank['color']);
    }

    public function test_update_rank_when_points_increase()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        // Start as Newcomer
        $this->assertEquals('Newcomer', $reputation->rank);
        $this->assertEquals(1, $reputation->rank_level);
        
        // Award enough points to become Contributor
        $reputation->total_points = 150;
        $reputation->updateRank();
        
        $this->assertEquals('Contributor', $reputation->rank);
        $this->assertEquals(2, $reputation->rank_level);
    }

    public function test_get_next_rank_progress()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        $reputation->total_points = 50; // Halfway to Contributor (100 points)
        
        $progress = $reputation->getNextRankProgress();
        
        $this->assertFalse($progress['is_max_rank']);
        $this->assertEquals(50, $progress['points_needed']);
        $this->assertEquals(50.0, $progress['progress_percentage']);
        $this->assertEquals('Contributor', $progress['next_rank']['name']);
    }

    public function test_get_next_rank_progress_at_max_rank()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        $reputation->total_points = 15000; // Legend rank
        $reputation->updateRank();
        
        $progress = $reputation->getNextRankProgress();
        
        $this->assertTrue($progress['is_max_rank']);
        $this->assertEquals(100, $progress['progress_percentage']);
        $this->assertEquals(0, $progress['points_needed']);
        $this->assertNull($progress['next_rank']);
    }

    public function test_update_activity_tracking_first_time()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        $reputation->updateActivityTracking();
        
        $this->assertEquals(1, $reputation->consecutive_days_active);
        $this->assertEquals(Carbon::today(), $reputation->last_activity_date);
    }

    public function test_update_activity_tracking_consecutive_days()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        // Set yesterday as last activity
        $reputation->last_activity_date = Carbon::yesterday();
        $reputation->consecutive_days_active = 1;
        $reputation->save();
        
        $reputation->updateActivityTracking();
        
        $this->assertEquals(2, $reputation->consecutive_days_active);
        $this->assertEquals(Carbon::today(), $reputation->last_activity_date);
    }

    public function test_update_activity_tracking_reset_streak()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        // Set 3 days ago as last activity
        $reputation->last_activity_date = Carbon::today()->subDays(3);
        $reputation->consecutive_days_active = 5;
        $reputation->save();
        
        $reputation->updateActivityTracking();
        
        $this->assertEquals(1, $reputation->consecutive_days_active);
        $this->assertEquals(Carbon::today(), $reputation->last_activity_date);
    }

    public function test_get_leaderboard_position()
    {
        $users = User::factory()->count(5)->create();
        
        // Create reputations with different points
        ForumUserReputation::create(['user_id' => $users[0]->id, 'total_points' => 100]);
        ForumUserReputation::create(['user_id' => $users[1]->id, 'total_points' => 200]);
        ForumUserReputation::create(['user_id' => $users[2]->id, 'total_points' => 150]);
        ForumUserReputation::create(['user_id' => $users[3]->id, 'total_points' => 300]);
        $reputation = ForumUserReputation::create(['user_id' => $users[4]->id, 'total_points' => 175]);
        
        $position = $reputation->getLeaderboardPosition();
        
        $this->assertEquals(3, $position); // 300, 200, 175, 150, 100
    }

    public function test_get_leaderboard()
    {
        $users = User::factory()->count(3)->create();
        
        ForumUserReputation::create(['user_id' => $users[0]->id, 'total_points' => 100]);
        ForumUserReputation::create(['user_id' => $users[1]->id, 'total_points' => 300]);
        ForumUserReputation::create(['user_id' => $users[2]->id, 'total_points' => 200]);
        
        $leaderboard = ForumUserReputation::getLeaderboard(2);
        
        $this->assertCount(2, $leaderboard);
        $this->assertEquals(300, $leaderboard[0]->total_points);
        $this->assertEquals(200, $leaderboard[1]->total_points);
    }

    public function test_get_statistics()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::create([
            'user_id' => $user->id,
            'total_points' => 150,
            'posts_count' => 10,
            'threads_count' => 3,
            'votes_received' => 25,
            'solutions_provided' => 2,
            'consecutive_days_active' => 5,
            'post_points' => 50,
            'vote_points' => 50,
            'solution_points' => 50,
            'badge_points' => 0,
        ]);
        
        $stats = $reputation->getStatistics();
        
        $this->assertEquals(150, $stats['total_points']);
        $this->assertEquals('Contributor', $stats['current_rank']['name']);
        $this->assertEquals(10, $stats['activity_stats']['posts_count']);
        $this->assertEquals(50, $stats['point_breakdown']['post_points']);
    }

    public function test_get_or_create_for_user_creates_new()
    {
        $user = User::factory()->create();
        
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        $this->assertInstanceOf(ForumUserReputation::class, $reputation);
        $this->assertEquals($user->id, $reputation->user_id);
        $this->assertEquals(0, $reputation->total_points);
        $this->assertEquals('Newcomer', $reputation->rank);
    }

    public function test_get_or_create_for_user_returns_existing()
    {
        $user = User::factory()->create();
        $existing = ForumUserReputation::create([
            'user_id' => $user->id,
            'total_points' => 100,
        ]);
        
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        $this->assertEquals($existing->id, $reputation->id);
        $this->assertEquals(100, $reputation->total_points);
    }

    public function test_award_points_creates_history_record()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        $reputation->awardPoints('post_created', null, [
            'source_type' => 'forum_post',
            'source_id' => 1,
            'description' => 'Test post creation',
        ]);
        
        $history = ForumReputationHistory::where('user_id', $user->id)->first();
        
        $this->assertNotNull($history);
        $this->assertEquals('post_created', $history->action);
        $this->assertEquals(5, $history->points_change);
        $this->assertEquals('forum_post', $history->source_type);
        $this->assertEquals(1, $history->source_id);
    }
}