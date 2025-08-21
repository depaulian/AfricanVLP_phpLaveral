<?php

namespace Tests\Integration\Forum;

use Tests\TestCase;
use App\Models\User;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\ForumVote;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VotingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function users_can_upvote_posts()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $response = $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('forum_votes', [
            'user_id' => $user->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up'
        ]);
    }

    /** @test */
    public function users_can_downvote_posts()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $response = $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'down'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('forum_votes', [
            'user_id' => $user->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'down'
        ]);
    }

    /** @test */
    public function users_cannot_vote_on_their_own_posts()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'You cannot vote on your own post']);
    }

    /** @test */
    public function users_can_change_their_vote()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        // First vote up
        $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        // Then vote down
        $response = $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'down'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('forum_votes', [
            'user_id' => $user->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'down'
        ]);

        // Should only have one vote record
        $this->assertEquals(1, ForumVote::where('user_id', $user->id)->count());
    }

    /** @test */
    public function users_can_remove_their_vote()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        // First vote up
        $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        // Then remove vote
        $response = $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up' // Same vote type removes it
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseMissing('forum_votes', [
            'user_id' => $user->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
        ]);
    }

    /** @test */
    public function vote_scores_are_calculated_correctly()
    {
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        // Create 5 upvotes
        $upvoters = User::factory()->count(5)->create();
        foreach ($upvoters as $user) {
            ForumVote::create([
                'user_id' => $user->id,
                'voteable_type' => ForumPost::class,
                'voteable_id' => $post->id,
                'vote_type' => 'up'
            ]);
        }

        // Create 2 downvotes
        $downvoters = User::factory()->count(2)->create();
        foreach ($downvoters as $user) {
            ForumVote::create([
                'user_id' => $user->id,
                'voteable_type' => ForumPost::class,
                'voteable_id' => $post->id,
                'vote_type' => 'down'
            ]);
        }

        $this->assertEquals(3, $post->getVoteScore()); // 5 - 2 = 3
        $this->assertEquals(5, $post->getUpvoteCount());
        $this->assertEquals(2, $post->getDownvoteCount());
    }

    /** @test */
    public function guest_users_cannot_vote()
    {
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $response = $this->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function voting_requires_valid_vote_type()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $response = $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'invalid'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vote_type']);
    }

    /** @test */
    public function vote_response_includes_updated_counts()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        // Add some existing votes
        $otherUser = User::factory()->create();
        ForumVote::create([
            'user_id' => $otherUser->id,
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up'
        ]);

        $response = $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'vote_score' => 2,
            'upvote_count' => 2,
            'downvote_count' => 0,
            'user_vote' => 'up'
        ]);
    }

    /** @test */
    public function users_can_vote_on_threads()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);

        $response = $this->actingAs($user)->post(route('forums.threads.vote', [$forum, $thread]), [
            'vote_type' => 'up'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('forum_votes', [
            'user_id' => $user->id,
            'voteable_type' => ForumThread::class,
            'voteable_id' => $thread->id,
            'vote_type' => 'up'
        ]);
    }

    /** @test */
    public function voting_triggers_analytics_tracking()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        // Check that analytics event was tracked
        $this->assertDatabaseHas('forum_analytics', [
            'user_id' => $user->id,
            'event_type' => 'vote_cast',
            'trackable_type' => ForumPost::class,
            'trackable_id' => $post->id,
        ]);
    }

    /** @test */
    public function voting_triggers_notifications()
    {
        $postAuthor = User::factory()->create();
        $voter = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $postAuthor->id
        ]);

        $this->actingAs($voter)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        // Check that notification was created for post author
        $this->assertDatabaseHas('forum_notifications', [
            'user_id' => $postAuthor->id,
            'type' => 'vote',
            'notifiable_type' => ForumPost::class,
            'notifiable_id' => $post->id,
        ]);
    }

    /** @test */
    public function vote_counts_are_cached_for_performance()
    {
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $user = User::factory()->create();
        
        // First vote should calculate and cache
        $this->actingAs($user)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        // Verify cache key exists
        $cacheKey = "post_votes_{$post->id}";
        $this->assertTrue(\Cache::has($cacheKey));
    }

    /** @test */
    public function vote_cache_is_invalidated_on_new_votes()
    {
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // First vote
        $this->actingAs($user1)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        $cacheKey = "post_votes_{$post->id}";
        $cachedData = \Cache::get($cacheKey);
        
        // Second vote should update cache
        $this->actingAs($user2)->post(route('forums.posts.vote', $post), [
            'vote_type' => 'up'
        ]);

        $newCachedData = \Cache::get($cacheKey);
        $this->assertNotEquals($cachedData, $newCachedData);
    }

    /** @test */
    public function bulk_voting_operations_work_correctly()
    {
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $posts = ForumPost::factory()->count(5)->create(['thread_id' => $thread->id]);
        $user = User::factory()->create();

        foreach ($posts as $post) {
            $this->actingAs($user)->post(route('forums.posts.vote', $post), [
                'vote_type' => 'up'
            ]);
        }

        $this->assertEquals(5, ForumVote::where('user_id', $user->id)->count());
        
        foreach ($posts as $post) {
            $this->assertEquals(1, $post->getVoteScore());
        }
    }
}