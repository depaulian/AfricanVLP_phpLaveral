<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumThreadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function it_can_create_a_thread()
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();
        
        $thread = ForumThread::create([
            'title' => 'Test Thread',
            'content' => 'This is a test thread content',
            'forum_id' => $forum->id,
            'user_id' => $user->id,
            'is_pinned' => false,
            'is_locked' => false,
        ]);

        $this->assertInstanceOf(ForumThread::class, $thread);
        $this->assertEquals('Test Thread', $thread->title);
        $this->assertEquals('This is a test thread content', $thread->content);
        $this->assertFalse($thread->is_pinned);
        $this->assertFalse($thread->is_locked);
    }

    /** @test */
    public function it_belongs_to_a_forum()
    {
        $forum = Forum::factory()->create();
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);

        $this->assertInstanceOf(Forum::class, $thread->forum);
        $this->assertEquals($forum->id, $thread->forum->id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $thread = ForumThread::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $thread->user);
        $this->assertEquals($user->id, $thread->user->id);
    }

    /** @test */
    public function it_has_many_posts()
    {
        $thread = ForumThread::factory()->create();
        $posts = ForumPost::factory()->count(3)->create(['thread_id' => $thread->id]);

        $this->assertCount(3, $thread->posts);
        $this->assertInstanceOf(ForumPost::class, $thread->posts->first());
    }

    /** @test */
    public function it_can_get_latest_posts()
    {
        $thread = ForumThread::factory()->create();
        $oldPost = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'created_at' => now()->subDays(2)
        ]);
        $newPost = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'created_at' => now()
        ]);

        $latestPosts = $thread->latestPosts(1);

        $this->assertCount(1, $latestPosts);
        $this->assertEquals($newPost->id, $latestPosts->first()->id);
    }

    /** @test */
    public function it_can_get_post_count()
    {
        $thread = ForumThread::factory()->create();
        ForumPost::factory()->count(5)->create(['thread_id' => $thread->id]);

        $this->assertEquals(5, $thread->getPostCount());
    }

    /** @test */
    public function it_can_get_view_count()
    {
        $thread = ForumThread::factory()->create(['view_count' => 100]);

        $this->assertEquals(100, $thread->getViewCount());
    }

    /** @test */
    public function it_can_increment_view_count()
    {
        $thread = ForumThread::factory()->create(['view_count' => 5]);

        $thread->incrementViewCount();

        $this->assertEquals(6, $thread->fresh()->view_count);
    }

    /** @test */
    public function it_can_scope_pinned_threads()
    {
        ForumThread::factory()->create(['is_pinned' => true]);
        ForumThread::factory()->create(['is_pinned' => false]);

        $pinnedThreads = ForumThread::pinned()->get();

        $this->assertCount(1, $pinnedThreads);
        $this->assertTrue($pinnedThreads->first()->is_pinned);
    }

    /** @test */
    public function it_can_scope_locked_threads()
    {
        ForumThread::factory()->create(['is_locked' => true]);
        ForumThread::factory()->create(['is_locked' => false]);

        $lockedThreads = ForumThread::locked()->get();

        $this->assertCount(1, $lockedThreads);
        $this->assertTrue($lockedThreads->first()->is_locked);
    }

    /** @test */
    public function it_can_scope_solved_threads()
    {
        $solvedThread = ForumThread::factory()->create();
        $unsolvedThread = ForumThread::factory()->create();
        
        // Create a solution post for the solved thread
        ForumPost::factory()->create([
            'thread_id' => $solvedThread->id,
            'is_solution' => true
        ]);

        $solvedThreads = ForumThread::solved()->get();

        $this->assertCount(1, $solvedThreads);
        $this->assertEquals($solvedThread->id, $solvedThreads->first()->id);
    }

    /** @test */
    public function it_can_check_if_thread_is_solved()
    {
        $solvedThread = ForumThread::factory()->create();
        $unsolvedThread = ForumThread::factory()->create();
        
        ForumPost::factory()->create([
            'thread_id' => $solvedThread->id,
            'is_solution' => true
        ]);

        $this->assertTrue($solvedThread->isSolved());
        $this->assertFalse($unsolvedThread->isSolved());
    }

    /** @test */
    public function it_can_get_solution_post()
    {
        $thread = ForumThread::factory()->create();
        $solutionPost = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'is_solution' => true
        ]);
        ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'is_solution' => false
        ]);

        $solution = $thread->getSolutionPost();

        $this->assertInstanceOf(ForumPost::class, $solution);
        $this->assertEquals($solutionPost->id, $solution->id);
        $this->assertTrue($solution->is_solution);
    }

    /** @test */
    public function it_returns_null_when_no_solution_post()
    {
        $thread = ForumThread::factory()->create();
        ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'is_solution' => false
        ]);

        $this->assertNull($thread->getSolutionPost());
    }

    /** @test */
    public function it_can_pin_thread()
    {
        $thread = ForumThread::factory()->create(['is_pinned' => false]);

        $thread->pin();

        $this->assertTrue($thread->fresh()->is_pinned);
    }

    /** @test */
    public function it_can_unpin_thread()
    {
        $thread = ForumThread::factory()->create(['is_pinned' => true]);

        $thread->unpin();

        $this->assertFalse($thread->fresh()->is_pinned);
    }

    /** @test */
    public function it_can_lock_thread()
    {
        $thread = ForumThread::factory()->create(['is_locked' => false]);

        $thread->lock();

        $this->assertTrue($thread->fresh()->is_locked);
    }

    /** @test */
    public function it_can_unlock_thread()
    {
        $thread = ForumThread::factory()->create(['is_locked' => true]);

        $thread->unlock();

        $this->assertFalse($thread->fresh()->is_locked);
    }

    /** @test */
    public function it_can_get_last_post()
    {
        $thread = ForumThread::factory()->create();
        $firstPost = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'created_at' => now()->subHour()
        ]);
        $lastPost = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'created_at' => now()
        ]);

        $retrievedLastPost = $thread->getLastPost();

        $this->assertInstanceOf(ForumPost::class, $retrievedLastPost);
        $this->assertEquals($lastPost->id, $retrievedLastPost->id);
    }

    /** @test */
    public function it_returns_null_for_last_post_when_no_posts()
    {
        $thread = ForumThread::factory()->create();

        $this->assertNull($thread->getLastPost());
    }

    /** @test */
    public function it_can_check_if_user_can_reply()
    {
        $user = User::factory()->create();
        $openThread = ForumThread::factory()->create(['is_locked' => false]);
        $lockedThread = ForumThread::factory()->create(['is_locked' => true]);

        $this->assertTrue($openThread->canUserReply($user));
        $this->assertFalse($lockedThread->canUserReply($user));
    }

    /** @test */
    public function it_has_proper_fillable_attributes()
    {
        $thread = new ForumThread();
        
        $expectedFillable = [
            'title',
            'content',
            'forum_id',
            'user_id',
            'is_pinned',
            'is_locked',
            'view_count',
            'tags'
        ];

        $this->assertEquals($expectedFillable, $thread->getFillable());
    }

    /** @test */
    public function it_casts_attributes_properly()
    {
        $thread = ForumThread::factory()->create([
            'is_pinned' => 1,
            'is_locked' => 0,
            'view_count' => '100',
            'tags' => ['php', 'laravel']
        ]);

        $this->assertIsBool($thread->is_pinned);
        $this->assertIsBool($thread->is_locked);
        $this->assertIsInt($thread->view_count);
        $this->assertIsArray($thread->tags);
        $this->assertTrue($thread->is_pinned);
        $this->assertFalse($thread->is_locked);
        $this->assertEquals(100, $thread->view_count);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $thread = ForumThread::factory()->create();
        $threadId = $thread->id;

        $thread->delete();

        $this->assertSoftDeleted('forum_threads', ['id' => $threadId]);
        $this->assertCount(0, ForumThread::all());
        $this->assertCount(1, ForumThread::withTrashed()->get());
    }

    /** @test */
    public function it_can_get_url_attribute()
    {
        $forum = Forum::factory()->create();
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);

        $expectedUrl = route('forums.threads.show', [$forum, $thread]);
        
        $this->assertEquals($expectedUrl, $thread->url);
    }
}