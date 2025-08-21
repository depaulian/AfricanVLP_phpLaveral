<?php

namespace Tests\Feature\Forum;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\ForumUserReputation;
use App\Models\ForumBadge;
use App\Models\ForumUserBadge;
use App\Models\ForumReputationHistory;
use App\Services\ForumGamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected $gamificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gamificationService = new ForumGamificationService();
    }

    public function test_user_gains_reputation_when_creating_post()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $this->gamificationService->handlePostCreated($post);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(5, $reputation->total_points);
        $this->assertEquals(1, $reputation->posts_count);
        $this->assertEquals(5, $reputation->post_points);
    }

    public function test_user_gains_reputation_when_creating_thread()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        
        $thread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'user_id' => $user->id,
        ]);

        $this->gamificationService->handleThreadCreated($thread);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(10, $reputation->total_points);
        $this->assertEquals(1, $reputation->threads_count);
        $this->assertEquals(10, $reputation->post_points);
    }

    public function test_user_gains_reputation_when_receiving_upvote()
    {
        $voter = User::factory()->create();
        $author = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $author->id]);
        
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        $vote = ForumVote::factory()->create([
            'user_id' => $voter->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up',
        ]);

        $this->gamificationService->handleVoteReceived($vote);

        $reputation = ForumUserReputation::where('user_id', $author->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(2, $reputation->total_points);
        $this->assertEquals(1, $reputation->votes_received);
        $this->assertEquals(2, $reputation->vote_points);
    }

    public function test_user_does_not_gain_reputation_for_downvote()
    {
        $voter = User::factory()->create();
        $author = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $author->id]);
        
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        $vote = ForumVote::factory()->create([
            'user_id' => $voter->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'down',
        ]);

        $this->gamificationService->handleVoteReceived($vote);

        $reputation = ForumUserReputation::where('user_id', $author->id)->first();
        $this->assertNull($reputation);
    }

    public function test_user_gains_reputation_when_solution_marked()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'is_solution' => true,
        ]);

        $this->gamificationService->handleSolutionMarked($post);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(25, $reputation->total_points);
        $this->assertEquals(1, $reputation->solutions_provided);
        $this->assertEquals(25, $reputation->solution_points);
    }

    public function test_reputation_history_is_created()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $this->gamificationService->handlePostCreated($post);

        $history = ForumReputationHistory::where('user_id', $user->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('post_created', $history->action);
        $this->assertEquals(5, $history->points_change);
        $this->assertEquals(0, $history->points_before);
        $this->assertEquals(5, $history->points_after);
    }

    public function test_badges_are_awarded_automatically()
    {
        // Create a badge for first post
        ForumBadge::create([
            'name' => 'First Steps',
            'slug' => 'first-steps',
            'description' => 'Created your first forum post',
            'type' => 'milestone',
            'rarity' => 'common',
            'points_value' => 10,
            'criteria' => ['first_post' => true],
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $this->gamificationService->handlePostCreated($post);

        // Check that badge was awarded
        $userBadge = ForumUserBadge::where('user_id', $user->id)->first();
        $this->assertNotNull($userBadge);
        $this->assertEquals('first-steps', $userBadge->badge->slug);

        // Check that badge points were added to reputation
        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertEquals(15, $reputation->total_points); // 5 for post + 10 for badge
        $this->assertEquals(10, $reputation->badge_points);
    }

    public function test_rank_is_updated_when_points_increase()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::getOrCreateForUser($user->id);
        
        // Award enough points to reach Contributor rank
        $reputation->awardPoints('post_created', 100);

        $reputation->refresh();
        $this->assertEquals('Contributor', $reputation->rank);
        $this->assertEquals(2, $reputation->rank_level);
    }

    public function test_get_user_dashboard_returns_complete_data()
    {
        $user = User::factory()->create();
        
        // Create some reputation and badges
        $reputation = ForumUserReputation::create([
            'user_id' => $user->id,
            'total_points' => 150,
            'posts_count' => 10,
            'rank' => 'Contributor',
            'rank_level' => 2,
        ]);

        $badge = ForumBadge::factory()->create();
        ForumUserBadge::create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now(),
            'is_featured' => true,
        ]);

        ForumReputationHistory::create([
            'user_id' => $user->id,
            'action' => 'post_created',
            'points_change' => 5,
            'points_before' => 0,
            'points_after' => 5,
        ]);

        $dashboard = $this->gamificationService->getUserDashboard($user);

        $this->assertArrayHasKey('reputation', $dashboard);
        $this->assertArrayHasKey('recent_badges', $dashboard);
        $this->assertArrayHasKey('featured_badges', $dashboard);
        $this->assertArrayHasKey('recent_history', $dashboard);
        $this->assertArrayHasKey('gains', $dashboard);

        $this->assertEquals(150, $dashboard['reputation']['total_points']);
        $this->assertEquals('Contributor', $dashboard['reputation']['current_rank']['name']);
        $this->assertCount(1, $dashboard['featured_badges']);
    }

    public function test_get_leaderboard_returns_top_users()
    {
        $users = User::factory()->count(5)->create();
        
        // Create reputations with different points
        foreach ($users as $index => $user) {
            ForumUserReputation::create([
                'user_id' => $user->id,
                'total_points' => ($index + 1) * 100, // 100, 200, 300, 400, 500
            ]);
        }

        $leaderboard = $this->gamificationService->getLeaderboard(3);

        $this->assertArrayHasKey('top_users', $leaderboard);
        $this->assertArrayHasKey('total_users', $leaderboard);
        $this->assertCount(3, $leaderboard['top_users']);
        $this->assertEquals(5, $leaderboard['total_users']);

        // Check that users are ordered by points descending
        $this->assertEquals(500, $leaderboard['top_users'][0]->total_points);
        $this->assertEquals(400, $leaderboard['top_users'][1]->total_points);
        $this->assertEquals(300, $leaderboard['top_users'][2]->total_points);
    }

    public function test_recalculate_user_reputation()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        
        // Create posts and votes
        $posts = ForumPost::factory()->count(3)->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        // Create votes for posts
        foreach ($posts as $post) {
            ForumVote::factory()->count(2)->create([
                'voteable_type' => ForumPost::class,
                'voteable_id' => $post->id,
                'vote_type' => 'up',
            ]);
        }

        // Mark one post as solution
        $posts[0]->update(['is_solution' => true]);

        // Create a badge
        $badge = ForumBadge::factory()->create(['points_value' => 20]);
        ForumUserBadge::create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now(),
        ]);

        $this->gamificationService->recalculateUserReputation($user);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        
        $this->assertEquals(3, $reputation->posts_count);
        $this->assertEquals(6, $reputation->votes_received); // 3 posts * 2 votes each
        $this->assertEquals(1, $reputation->solutions_provided);
        
        // Points calculation: 3 posts * 5 + 6 votes * 2 + 1 solution * 25 + 1 badge * 20 = 72
        $this->assertEquals(72, $reputation->total_points);
        $this->assertEquals(15, $reputation->post_points); // 3 * 5
        $this->assertEquals(12, $reputation->vote_points); // 6 * 2
        $this->assertEquals(25, $reputation->solution_points); // 1 * 25
        $this->assertEquals(20, $reputation->badge_points); // 1 * 20
    }

    public function test_get_user_badge_progress()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 3,
            'total_points' => 50,
        ]);

        // Create a badge the user can work towards
        ForumBadge::create([
            'name' => 'Contributor',
            'slug' => 'contributor',
            'description' => 'Made 5 posts',
            'type' => 'activity',
            'rarity' => 'uncommon',
            'criteria' => ['posts_count' => 5],
            'is_active' => true,
        ]);

        $progress = $this->gamificationService->getUserBadgeProgress($user);

        $this->assertCount(1, $progress);
        $this->assertEquals('Contributor', $progress[0]['badge']->name);
        $this->assertEquals(60.0, $progress[0]['progress_percentage']); // 3/5 * 100
        
        $requirement = $progress[0]['requirements'][0];
        $this->assertEquals('posts_count', $requirement['criterion']);
        $this->assertEquals(3, $requirement['current_value']);
        $this->assertEquals(5, $requirement['target_value']);
        $this->assertFalse($requirement['is_met']);
    }

    public function test_seed_default_badges()
    {
        $this->gamificationService->seedDefaultBadges();

        $badges = ForumBadge::all();
        $this->assertGreaterThan(0, $badges->count());

        // Check that specific default badges exist
        $firstSteps = ForumBadge::where('slug', 'first-steps')->first();
        $this->assertNotNull($firstSteps);
        $this->assertEquals('First Steps', $firstSteps->name);
        $this->assertEquals(['first_post' => true], $firstSteps->criteria);
    }

    public function test_daily_activity_tracking()
    {
        $user = User::factory()->create();
        
        $this->gamificationService->handleDailyActivity($user);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(1, $reputation->total_points);
        $this->assertEquals(1, $reputation->consecutive_days_active);
        $this->assertEquals(today(), $reputation->last_activity_date);
    }

    public function test_badge_statistics()
    {
        // Create various badges
        ForumBadge::factory()->create(['type' => 'activity', 'rarity' => 'common', 'awarded_count' => 10]);
        ForumBadge::factory()->create(['type' => 'achievement', 'rarity' => 'rare', 'awarded_count' => 5]);
        ForumBadge::factory()->create(['type' => 'milestone', 'rarity' => 'epic', 'awarded_count' => 2]);

        $statistics = $this->gamificationService->getBadgeStatistics();

        $this->assertArrayHasKey('total_badges', $statistics);
        $this->assertArrayHasKey('by_type', $statistics);
        $this->assertArrayHasKey('by_rarity', $statistics);
        $this->assertArrayHasKey('most_awarded', $statistics);
        $this->assertArrayHasKey('least_awarded', $statistics);

        $this->assertEquals(3, $statistics['total_badges']);
        $this->assertEquals(1, $statistics['by_type']['activity']);
        $this->assertEquals(1, $statistics['by_rarity']['common']);
    }
}