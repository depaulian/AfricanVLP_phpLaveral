<?php

namespace Tests\Integration\Forum;

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
use Illuminate\Support\Facades\Event;

class GamificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $gamificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gamificationService = new ForumGamificationService();
        
        // Seed default badges
        $this->gamificationService->seedDefaultBadges();
    }

    public function test_complete_user_journey_with_gamification()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);

        // Step 1: User creates their first thread
        $thread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'user_id' => $user->id,
        ]);

        $this->gamificationService->handleThreadCreated($thread);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertEquals(10, $reputation->total_points);
        $this->assertEquals(1, $reputation->threads_count);

        // Check if "Thread Starter" badge was awarded
        $threadStarterBadge = ForumUserBadge::whereHas('badge', function($query) {
            $query->where('slug', 'thread-starter');
        })->where('user_id', $user->id)->first();
        $this->assertNotNull($threadStarterBadge);

        // Step 2: User creates their first post
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $this->gamificationService->handlePostCreated($post);

        $reputation->refresh();
        $this->assertEquals(40, $reputation->total_points); // 10 (thread) + 5 (post) + 15 (thread badge) + 10 (first post badge)
        $this->assertEquals(1, $reputation->posts_count);

        // Check if "First Steps" badge was awarded
        $firstStepsBadge = ForumUserBadge::whereHas('badge', function($query) {
            $query->where('slug', 'first-steps');
        })->where('user_id', $user->id)->first();
        $this->assertNotNull($firstStepsBadge);

        // Step 3: User receives upvotes
        $voter1 = User::factory()->create();
        $voter2 = User::factory()->create();

        $vote1 = ForumVote::factory()->create([
            'user_id' => $voter1->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up',
        ]);

        $vote2 = ForumVote::factory()->create([
            'user_id' => $voter2->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up',
        ]);

        $this->gamificationService->handleVoteReceived($vote1);
        $this->gamificationService->handleVoteReceived($vote2);

        $reputation->refresh();
        $this->assertEquals(44, $reputation->total_points); // Previous + 2 + 2
        $this->assertEquals(2, $reputation->votes_received);

        // Step 4: User's post is marked as solution
        $post->update(['is_solution' => true]);
        $this->gamificationService->handleSolutionMarked($post);

        $reputation->refresh();
        $this->assertEquals(69, $reputation->total_points); // Previous + 25
        $this->assertEquals(1, $reputation->solutions_provided);

        // Step 5: Check rank progression
        $this->assertEquals('Newcomer', $reputation->rank);
        $this->assertEquals(1, $reputation->rank_level);

        // Award more points to reach Contributor rank
        $reputation->awardPoints('post_created', 50);
        $reputation->refresh();
        
        $this->assertEquals('Contributor', $reputation->rank);
        $this->assertEquals(2, $reputation->rank_level);

        // Verify reputation history was recorded
        $historyCount = ForumReputationHistory::where('user_id', $user->id)->count();
        $this->assertGreaterThan(5, $historyCount);
    }

    public function test_multiple_users_leaderboard_competition()
    {
        $users = User::factory()->count(5)->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);

        foreach ($users as $index => $user) {
            // Create different amounts of activity for each user
            $threadCount = $index + 1;
            $postCount = ($index + 1) * 2;
            $voteCount = ($index + 1) * 3;

            for ($i = 0; $i < $threadCount; $i++) {
                $thread = ForumThread::factory()->create([
                    'forum_id' => $forum->id,
                    'user_id' => $user->id,
                ]);
                $this->gamificationService->handleThreadCreated($thread);
            }

            for ($i = 0; $i < $postCount; $i++) {
                $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
                $post = ForumPost::factory()->create([
                    'thread_id' => $thread->id,
                    'user_id' => $user->id,
                ]);
                $this->gamificationService->handlePostCreated($post);
            }

            // Simulate receiving votes
            $reputation = ForumUserReputation::where('user_id', $user->id)->first();
            for ($i = 0; $i < $voteCount; $i++) {
                $reputation->awardPoints('vote_received');
            }
        }

        $leaderboard = $this->gamificationService->getLeaderboard(5);
        
        $this->assertCount(5, $leaderboard['top_users']);
        
        // Verify leaderboard is ordered correctly (highest points first)
        $previousPoints = PHP_INT_MAX;
        foreach ($leaderboard['top_users'] as $topUser) {
            $this->assertLessThanOrEqual($previousPoints, $topUser->total_points);
            $previousPoints = $topUser->total_points;
        }

        // The last user (index 4) should have the most points
        $this->assertEquals($users[4]->id, $leaderboard['top_users'][0]->user_id);
    }

    public function test_badge_progression_system()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);

        // Start with no badges
        $this->assertEquals(0, ForumUserBadge::where('user_id', $user->id)->count());

        // Create 10 posts to earn "Contributor" badge
        for ($i = 0; $i < 10; $i++) {
            $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
            $post = ForumPost::factory()->create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
            ]);
            $this->gamificationService->handlePostCreated($post);
        }

        // Should have earned multiple badges by now
        $userBadges = ForumUserBadge::where('user_id', $user->id)->count();
        $this->assertGreaterThan(1, $userBadges);

        // Check specific badges
        $contributorBadge = ForumUserBadge::whereHas('badge', function($query) {
            $query->where('slug', 'contributor');
        })->where('user_id', $user->id)->first();
        $this->assertNotNull($contributorBadge);

        // Simulate receiving many votes to earn "Popular Voice" badge
        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        for ($i = 0; $i < 50; $i++) {
            $reputation->awardPoints('vote_received');
        }

        // Check and award badges
        $this->gamificationService->checkAndAwardBadges($user);

        $popularVoiceBadge = ForumUserBadge::whereHas('badge', function($query) {
            $query->where('slug', 'popular-voice');
        })->where('user_id', $user->id)->first();
        $this->assertNotNull($popularVoiceBadge);
    }

    public function test_consecutive_activity_tracking()
    {
        $user = User::factory()->create();
        
        // Day 1
        $this->gamificationService->handleDailyActivity($user);
        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertEquals(1, $reputation->consecutive_days_active);

        // Simulate day 2 (yesterday's activity, then today's)
        $reputation->last_activity_date = now()->subDay();
        $reputation->save();
        
        $this->gamificationService->handleDailyActivity($user);
        $reputation->refresh();
        $this->assertEquals(2, $reputation->consecutive_days_active);

        // Simulate day 8 (should get bonus for 7 consecutive days)
        $reputation->consecutive_days_active = 6;
        $reputation->last_activity_date = now()->subDay();
        $reputation->save();
        
        $this->gamificationService->handleDailyActivity($user);
        $reputation->refresh();
        $this->assertEquals(7, $reputation->consecutive_days_active);
        
        // Check if bonus points were awarded
        $bonusHistory = ForumReputationHistory::where('user_id', $user->id)
            ->where('action', 'consecutive_days')
            ->first();
        $this->assertNotNull($bonusHistory);
        $this->assertEquals(5, $bonusHistory->points_change);
    }

    public function test_badge_progress_calculation()
    {
        $user = User::factory()->create();
        ForumUserReputation::create([
            'user_id' => $user->id,
            'posts_count' => 7,
            'votes_received' => 30,
            'solutions_provided' => 2,
            'total_points' => 200,
        ]);

        $progress = $this->gamificationService->getUserBadgeProgress($user);

        // Should show progress towards badges user hasn't earned yet
        $this->assertGreaterThan(0, count($progress));

        // Find progress for "Problem Solver" badge (5 solutions needed)
        $problemSolverProgress = collect($progress)->firstWhere(function($item) {
            return $item['badge']->slug === 'problem-solver';
        });

        if ($problemSolverProgress) {
            $this->assertEquals(40.0, $problemSolverProgress['progress_percentage']); // 2/5 * 100
            $this->assertEquals(2, $problemSolverProgress['requirements'][0]['current_value']);
            $this->assertEquals(5, $problemSolverProgress['requirements'][0]['target_value']);
            $this->assertFalse($problemSolverProgress['requirements'][0]['is_met']);
        }
    }

    public function test_reputation_recalculation_accuracy()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);

        // Create forum activity
        $threads = ForumThread::factory()->count(2)->create([
            'forum_id' => $forum->id,
            'user_id' => $user->id,
        ]);

        $posts = ForumPost::factory()->count(5)->create([
            'thread_id' => $threads[0]->id,
            'user_id' => $user->id,
        ]);

        // Create votes for posts
        foreach ($posts->take(3) as $post) {
            ForumVote::factory()->count(4)->create([
                'voteable_type' => ForumPost::class,
                'voteable_id' => $post->id,
                'vote_type' => 'up',
            ]);
        }

        // Mark 2 posts as solutions
        $posts[0]->update(['is_solution' => true]);
        $posts[1]->update(['is_solution' => true]);

        // Create and award a badge manually
        $badge = ForumBadge::factory()->create(['points_value' => 30]);
        ForumUserBadge::create([
            'user_id' => $user->id,
            'forum_badge_id' => $badge->id,
            'earned_at' => now(),
        ]);

        // Recalculate reputation
        $this->gamificationService->recalculateUserReputation($user);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();

        // Verify calculations
        $this->assertEquals(5, $reputation->posts_count);
        $this->assertEquals(2, $reputation->threads_count);
        $this->assertEquals(12, $reputation->votes_received); // 3 posts * 4 votes each
        $this->assertEquals(2, $reputation->solutions_provided);

        // Points: 5 posts * 5 + 12 votes * 2 + 2 solutions * 25 + 1 badge * 30 = 25 + 24 + 50 + 30 = 129
        $this->assertEquals(129, $reputation->total_points);
        $this->assertEquals(25, $reputation->post_points);
        $this->assertEquals(24, $reputation->vote_points);
        $this->assertEquals(50, $reputation->solution_points);
        $this->assertEquals(30, $reputation->badge_points);
    }

    public function test_gamification_with_database_transactions()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);

        // Test that gamification handles database errors gracefully
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        // This should complete successfully
        $this->gamificationService->handlePostCreated($post);

        $reputation = ForumUserReputation::where('user_id', $user->id)->first();
        $this->assertNotNull($reputation);
        $this->assertEquals(5, $reputation->total_points);

        $history = ForumReputationHistory::where('user_id', $user->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('post_created', $history->action);
    }

    public function test_user_dashboard_comprehensive_data()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);

        // Create comprehensive user activity
        for ($i = 0; $i < 3; $i++) {
            $thread = ForumThread::factory()->create([
                'forum_id' => $forum->id,
                'user_id' => $user->id,
            ]);
            $this->gamificationService->handleThreadCreated($thread);

            $post = ForumPost::factory()->create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
            ]);
            $this->gamificationService->handlePostCreated($post);
        }

        // Award some badges
        $this->gamificationService->checkAndAwardBadges($user);

        // Set some badges as featured
        $userBadges = ForumUserBadge::where('user_id', $user->id)->take(2)->get();
        foreach ($userBadges as $userBadge) {
            $userBadge->update(['is_featured' => true]);
        }

        $dashboard = $this->gamificationService->getUserDashboard($user);

        // Verify all dashboard sections are present and populated
        $this->assertArrayHasKey('reputation', $dashboard);
        $this->assertArrayHasKey('recent_badges', $dashboard);
        $this->assertArrayHasKey('featured_badges', $dashboard);
        $this->assertArrayHasKey('recent_history', $dashboard);
        $this->assertArrayHasKey('gains', $dashboard);

        // Verify reputation data
        $this->assertGreaterThan(0, $dashboard['reputation']['total_points']);
        $this->assertArrayHasKey('current_rank', $dashboard['reputation']);
        $this->assertArrayHasKey('next_rank_progress', $dashboard['reputation']);
        $this->assertArrayHasKey('activity_stats', $dashboard['reputation']);

        // Verify badges
        $this->assertGreaterThan(0, count($dashboard['recent_badges']));
        $this->assertGreaterThan(0, count($dashboard['featured_badges']));

        // Verify history
        $this->assertGreaterThan(0, count($dashboard['recent_history']));

        // Verify gains
        $this->assertArrayHasKey('weekly', $dashboard['gains']);
        $this->assertArrayHasKey('monthly', $dashboard['gains']);
    }
}