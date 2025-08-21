<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\ForumVote;
use App\Models\ForumAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function it_can_create_a_post()
    {
        $thread = ForumThread::factory()->create();
        $user = User::factory()->create();
        
        $post = ForumPost::create([
            'content' => 'This is a test post content',
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'is_solution' => false,
        ]);

        $this->assertInstanceOf(ForumPost::class, $post);
        $this->assertEquals('This is a test post content', $post->content);
        $this->assertFalse($post->is_solution);
    }

    /** @test */
    public function it_belongs_to_a_thread()
    {
        $thread = ForumThread::factory()->create();
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $this->assertInstanceOf(ForumThread::class, $post->thread);
        $this->assertEquals($thread->id, $post->thread->id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }

    /** @test */
    public function it_can_have_a_parent_post()
    {
        $parentPost = ForumPost::factory()->create();
        $childPost = ForumPost::factory()->create(['parent_id' => $parentPost->id]);

        $this->assertInstanceOf(ForumPost::class, $childPost->parent);
        $this->assertEquals($parentPost->id, $childPost->parent->id);
    }

    /** @test */
    public function it_can_have_child_posts()
    {
        $parentPost = ForumPost::factory()->create();
        $childPosts = ForumPost::factory()->count(3)->create(['parent_id' => $parentPost->id]);

        $this->assertCount(3, $parentPost->children);
        $this->assertInstanceOf(ForumPost::class, $parentPost->children->first());
    }

    /** @test */
    public function it_has_many_votes()
    {
        $post = ForumPost::factory()->create();
        $votes = ForumVote::factory()->count(3)->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id
        ]);

        $this->assertCount(3, $post->votes);
        $this->assertInstanceOf(ForumVote::class, $post->votes->first());
    }

    /** @test */
    public function it_has_many_attachments()
    {
        $post = ForumPost::factory()->create();
        $attachments = ForumAttachment::factory()->count(2)->create(['post_id' => $post->id]);

        $this->assertCount(2, $post->attachments);
        $this->assertInstanceOf(ForumAttachment::class, $post->attachments->first());
    }

    /** @test */
    public function it_can_get_vote_score()
    {
        $post = ForumPost::factory()->create();
        
        // Create upvotes and downvotes
        ForumVote::factory()->count(5)->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up'
        ]);
        ForumVote::factory()->count(2)->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'down'
        ]);

        $this->assertEquals(3, $post->getVoteScore()); // 5 - 2 = 3
    }

    /** @test */
    public function it_can_get_upvote_count()
    {
        $post = ForumPost::factory()->create();
        
        ForumVote::factory()->count(5)->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up'
        ]);
        ForumVote::factory()->count(2)->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'down'
        ]);

        $this->assertEquals(5, $post->getUpvoteCount());
    }

    /** @test */
    public function it_can_get_downvote_count()
    {
        $post = ForumPost::factory()->create();
        
        ForumVote::factory()->count(5)->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'up'
        ]);
        ForumVote::factory()->count(2)->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'vote_type' => 'down'
        ]);

        $this->assertEquals(2, $post->getDownvoteCount());
    }

    /** @test */
    public function it_can_check_if_user_has_voted()
    {
        $post = ForumPost::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        ForumVote::factory()->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'user_id' => $user->id,
            'vote_type' => 'up'
        ]);

        $this->assertTrue($post->hasUserVoted($user));
        $this->assertFalse($post->hasUserVoted($otherUser));
    }

    /** @test */
    public function it_can_get_user_vote_type()
    {
        $post = ForumPost::factory()->create();
        $user = User::factory()->create();
        
        ForumVote::factory()->create([
            'voteable_type' => ForumPost::class,
            'voteable_id' => $post->id,
            'user_id' => $user->id,
            'vote_type' => 'up'
        ]);

        $this->assertEquals('up', $post->getUserVoteType($user));
    }

    /** @test */
    public function it_returns_null_for_user_vote_type_when_no_vote()
    {
        $post = ForumPost::factory()->create();
        $user = User::factory()->create();

        $this->assertNull($post->getUserVoteType($user));
    }

    /** @test */
    public function it_can_mark_as_solution()
    {
        $post = ForumPost::factory()->create(['is_solution' => false]);

        $post->markAsSolution();

        $this->assertTrue($post->fresh()->is_solution);
    }

    /** @test */
    public function it_can_unmark_as_solution()
    {
        $post = ForumPost::factory()->create(['is_solution' => true]);

        $post->unmarkAsSolution();

        $this->assertFalse($post->fresh()->is_solution);
    }

    /** @test */
    public function it_can_scope_solutions()
    {
        ForumPost::factory()->create(['is_solution' => true]);
        ForumPost::factory()->create(['is_solution' => false]);

        $solutions = ForumPost::solutions()->get();

        $this->assertCount(1, $solutions);
        $this->assertTrue($solutions->first()->is_solution);
    }

    /** @test */
    public function it_can_scope_top_level_posts()
    {
        $parentPost = ForumPost::factory()->create(['parent_id' => null]);
        $childPost = ForumPost::factory()->create(['parent_id' => $parentPost->id]);

        $topLevelPosts = ForumPost::topLevel()->get();

        $this->assertCount(1, $topLevelPosts);
        $this->assertEquals($parentPost->id, $topLevelPosts->first()->id);
    }

    /** @test */
    public function it_can_check_if_post_is_edited()
    {
        $post = ForumPost::factory()->create([
            'created_at' => now()->subHour(),
            'updated_at' => now()
        ]);

        $this->assertTrue($post->isEdited());
    }

    /** @test */
    public function it_can_check_if_post_is_not_edited()
    {
        $now = now();
        $post = ForumPost::factory()->create([
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $this->assertFalse($post->isEdited());
    }

    /** @test */
    public function it_can_get_reply_count()
    {
        $parentPost = ForumPost::factory()->create();
        ForumPost::factory()->count(3)->create(['parent_id' => $parentPost->id]);

        $this->assertEquals(3, $parentPost->getReplyCount());
    }

    /** @test */
    public function it_can_check_if_user_can_edit()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = ForumPost::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($post->canUserEdit($user));
        $this->assertFalse($post->canUserEdit($otherUser));
    }

    /** @test */
    public function it_can_check_if_user_can_delete()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = ForumPost::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($post->canUserDelete($user));
        $this->assertFalse($post->canUserDelete($otherUser));
    }

    /** @test */
    public function it_has_proper_fillable_attributes()
    {
        $post = new ForumPost();
        
        $expectedFillable = [
            'content',
            'thread_id',
            'user_id',
            'parent_id',
            'is_solution',
        ];

        $this->assertEquals($expectedFillable, $post->getFillable());
    }

    /** @test */
    public function it_casts_attributes_properly()
    {
        $post = ForumPost::factory()->create([
            'is_solution' => 1,
        ]);

        $this->assertIsBool($post->is_solution);
        $this->assertTrue($post->is_solution);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $post = ForumPost::factory()->create();
        $postId = $post->id;

        $post->delete();

        $this->assertSoftDeleted('forum_posts', ['id' => $postId]);
        $this->assertCount(0, ForumPost::all());
        $this->assertCount(1, ForumPost::withTrashed()->get());
    }

    /** @test */
    public function it_can_get_url_attribute()
    {
        $thread = ForumThread::factory()->create();
        $post = ForumPost::factory()->create(['thread_id' => $thread->id]);

        $expectedUrl = route('forums.threads.show', [$thread->forum, $thread]) . '#post-' . $post->id;
        
        $this->assertEquals($expectedUrl, $post->url);
    }

    /** @test */
    public function it_can_get_excerpt()
    {
        $longContent = str_repeat('This is a long post content. ', 20);
        $post = ForumPost::factory()->create(['content' => $longContent]);

        $excerpt = $post->getExcerpt(50);

        $this->assertLessThanOrEqual(50, strlen($excerpt));
        $this->assertStringEndsWith('...', $excerpt);
    }

    /** @test */
    public function it_returns_full_content_as_excerpt_when_short()
    {
        $shortContent = 'Short content';
        $post = ForumPost::factory()->create(['content' => $shortContent]);

        $excerpt = $post->getExcerpt(50);

        $this->assertEquals($shortContent, $excerpt);
        $this->assertStringEndsNotWith('...', $excerpt);
    }
}