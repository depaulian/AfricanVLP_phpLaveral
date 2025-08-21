<?php

namespace Tests\Unit\Services;

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
use Illuminate\Support\Facades\DB;

class ForumGamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $gamificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gamificationService = new ForumGamificationService();
    }

    public function test_handle_post_created_awards_points()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

        $this->gamificationService->handlePostCreated($post);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(5, $reputation->total_points);
        $this->assertEquals(1, $reputation->posts_count);
    }

    public function test_handle_post_created_creates_history_record()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

        $this->gamificationService->handlePostCreated($post);

        $history = ForumReputationHistory::where('user_id', $user->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('post_created', $history->action);
        $this->assertEquals(5, $history->points_change);
        $this->assertEquals('forum_post', $history->source_type);
        $this->assertEquals($post->id, $history->source_id);
    }

    public function test_handle_thread_created_awards_points()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);

        $this->gamificationService->handleThreadCreated($thread);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(10, $reputation->total_points);
        $this->assertEquals(1, $reputation->threads_count);
    }

    public function test_handle_vote_received_awards_points_for_upvote()
    {
        $voter = User::factory()->create();
        $author = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $author->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id, 'user_id' => $author->id]);

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
    }

    public function test_handle_vote_received_ignores_downvote()
    {
        $voter = User::factory()->create();
        $author = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $author->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id, 'user_id' => $author->id]);

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

    public function test_handle_solution_marked_awards_points()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

        $this->gamificationService->handleSolutionMarked($post);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(25, $reputation->total_points);
        $this->assertEquals(1, $reputation->solutions_provided);
    }

    public function test_handle_daily_activity_awards_points()
    {
        $user = User::factory()->create();

        $this->gamificationService->handleDailyActivity($user);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(1, $reputation->total_points);
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

        $awardedBadges = $this->gamificationService->checkAndAwardBadges($user);

        $this->assertCount(1, $awardedBadges);
        $this->assertEquals('First Steps', $awardedBadges[0]->name);

        $userBadge = ForumUserBadge::where('user_id', $user->id)->first();
        $this->assertNotNull($userBadge);
    }

    public function test_get_user_dashboard()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::factory()->create(['user_id' => $user->id]);
        $badge = ForumBadge::factory()->create();
        ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'is_featured' => true,
        ]);
        ForumReputationHistory::factory()->create(['user_id' => $user->id]);

        $dashboard = $this->gamificationService->getUserDashboard($user);

        $this->assertArrayHasKey('reputation', $dashboard);
        $this->assertArrayHasKey('recent_badges', $dashboard);
        $this->assertArrayHasKey('featured_badges', $dashboard);
        $this->assertArrayHasKey('recent_history', $dashboard);
        $this->assertArrayHasKey('gains', $dashboard);

        $this->assertCount(1, $dashboard['featured_badges']);
        $this->assertCount(1, $dashboard['recent_history']);
    }

    public function test_get_leaderboard()
    {
        $users = User::factory()->count(3)->create();
        ForumUserReputation::factory()->create(['user_id' => $users[0]->id, 'total_points' => 100]);
        ForumUserReputation::factory()->create(['user_id' => $users[1]->id, 'total_points' => 300]);
        ForumUserReputation::factory()->create(['user_id' => $users[2]->id, 'total_points' => 200]);

        $leaderboard = $this->gamificationService->getLeaderboard(2);

        $this->assertArrayHasKey('top_users', $leaderboard);
        $this->assertArrayHasKey('total_users', $leaderboard);
        $this->assertCount(2, $leaderboard['top_users']);
        $this->assertEquals(3, $leaderboard['total_users']);

        // Check ordering (highest first)
        $this->assertEquals(300, $leaderboard['top_users'][0]->total_points);
        $this->assertEquals(200, $leaderboard['top_users'][1]->total_points);
    }

    public function test_get_badge_statistics()
    {
        ForumBadge::factory()->create(['type' => 'activity', 'rarity' => 'common', 'awarded_count' => 10]);
        ForumBadge::factory()->create(['type' => 'achievement', 'rarity' => 'rare', 'awarded_count' => 5]);
        ForumBadge::factory()->create(['type' => 'activity', 'rarity' => 'uncommon', 'awarded_count' => 15]);

        $statistics = $this->gamificationService->getBadgeStatistics();

        $this->assertEquals(3, $statistics['total_badges']);
        $this->assertEquals(2, $statistics['by_type']['activity']);
        $this->assertEquals(1, $statistics['by_type']['achievement']);
        $this->assertEquals(1, $statistics['by_rarity']['common']);
        $this->assertEquals(1, $statistics['by_rarity']['rare']);
    }

    public function test_seed_default_badges()
    {
        $this->assertEquals(0, ForumBadge::count());

        $this->gamificationService->seedDefaultBadges();

        $this->assertGreaterThan(0, ForumBadge::count());

        $firstSteps = ForumBadge::where('slug', 'first-steps')->first();
        $this->assertNotNull($firstSteps);
        $this->assertEquals('First Steps', $firstSteps->name);
    }

    public function test_recalculate_user_reputation()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);

        // Create posts
        $posts = ForumPost::factory()->count(3)->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        // Create votes
        foreach ($posts as $post) {
            ForumVote::factory()->count(2)->create([
                'voteable_type' => ForumPost::class,
                'voteable_id' => $post->id,
                'vote_type' => 'up',
            ]);
        }

        // Mark one as solution
        $posts[0]->update(['is_solution' => true]);

        // Create badge
        $badge = ForumBadge::factory()->create(['points_value' => 20]);
        ForumUserBadge::factory()->create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
        ]);

        $this->gamificationService->recalculateUserReputation($user);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertEquals(3, $reputation->posts_count);
        $this->assertEquals(6, $reputation->votes_received); // 3 posts * 2 votes
        $this->assertEquals(1, $reputation->solutions_provided);
        $this->assertEquals(15, $reputation->post_points); // 3 * 5
        $this->assertEquals(12, $reputation->vote_points); // 6 * 2
        $this->assertEquals(25, $reputation->solution_points); // 1 * 25
        $this->assertEquals(20, $reputation->badge_points);
        $this->assertEquals(72, $reputation->total_points); // 15 + 12 + 25 + 20
    }

    public function test_get_user_badge_progress()
    {
        $user = User::factory()->create();
        ForumUserReputation::factory()->create([
            'user_id' => $user->id,
            'posts_count' => 3,
            'votes_received' => 20,
        ]);

        // Create badges user can work towards
        ForumBadge::factory()->create([
            'slug' => 'contributor',
            'criteria' => ['posts_count' => 5],
            'is_active' => true,
        ]);

        ForumBadge::factory()->create([
            'slug' => 'popular',
            'criteria' => ['votes_received' => 50],
            'is_active' => true,
        ]);

        $progress = $this->gamificationService->getUserBadgeProgress($user);

        $this->assertGreaterThan(0, count($progress));

        $contributorProgress = collect($progress)->firstWhere(function($item) {
            return $item['badge']->slug === 'contributor';
        });

        if ($contributorProgress) {
            $this->assertEquals(60.0, $contributorProgress['progress_percentage']); // 3/5 * 100
        }
    }

    public function test_handles_database_errors_gracefully()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

        // Mock database transaction failure
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        // This should not throw an exception
        $this->gamificationService->handlePostCreated($post);

        // Reputation should not be created due to rollback
        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNull($reputation);
    }

    public function test_calculate_badge_progress_with_multiple_criteria()
    {
        $user = User::factory()->create();
        $reputation = ForumUserReputation::factory()->create([
            'user_id' => $user->id,
            'posts_count' => 8,
            'votes_received' => 15,
        ]);

        $badge = ForumBadge::factory()->create([
            'criteria' => [
                'posts_count' => 10,
                'votes_received' => 20,
            ],
            'is_active' => true,
        ]);

        $progress = $this->gamificationService->getUserBadgeProgress($user);

        $badgeProgress = collect($progress)->firstWhere(function($item) use ($badge) {
            return $item['badge']->id === $badge->id;
        });

        if ($badgeProgress) {
            // Progress should be minimum of all requirements: min(80%, 75%) = 75%
            $this->assertEquals(75.0, $badgeProgress['progress_percentage']);
            $this->assertCount(2, $badgeProgress['requirements']);
        }
    }

    public function test_user_dashboard_with_empty_data()
    {
        $user = User::factory()->create();

        $dashboard = $this->gamificationService->getUserDashboard($user);

        $this->assertArrayHasKey('reputation', $dashboard);
        $this->assertArrayHasKey('recent_badges', $dashboard);
        $this->assertArrayHasKey('featured_badges', $dashboard);
        $this->assertArrayHasKey('recent_history', $dashboard);
        $this->assertArrayHasKey('gains', $dashboard);

        $this->assertEquals(0, $dashboard['reputation']['total_points']);
        $this->assertEmpty($dashboard['recent_badges']);
        $this->assertEmpty($dashboard['featured_badges']);
        $this->assertEmpty($dashboard['recent_history']);
        $this->assertEquals(0, $dashboard['gains']['weekly']);
        $this->assertEquals(0, $dashboard['gains']['monthly']);
    }
}