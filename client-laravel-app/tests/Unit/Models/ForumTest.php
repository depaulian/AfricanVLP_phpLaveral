<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function it_can_create_a_forum()
    {
        $organization = Organization::factory()->create();
        
        $forum = Forum::create([
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'organization_id' => $organization->id,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->assertInstanceOf(Forum::class, $forum);
        $this->assertEquals('Test Forum', $forum->name);
        $this->assertEquals('A test forum', $forum->description);
        $this->assertTrue($forum->is_active);
        $this->assertTrue($forum->is_public);
    }

    /** @test */
    public function it_belongs_to_an_organization()
    {
        $organization = Organization::factory()->create();
        $forum = Forum::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $forum->organization);
        $this->assertEquals($organization->id, $forum->organization->id);
    }

    /** @test */
    public function it_has_many_threads()
    {
        $forum = Forum::factory()->create();
        $threads = ForumThread::factory()->count(3)->create(['forum_id' => $forum->id]);

        $this->assertCount(3, $forum->threads);
        $this->assertInstanceOf(ForumThread::class, $forum->threads->first());
    }

    /** @test */
    public function it_can_get_latest_threads()
    {
        $forum = Forum::factory()->create();
        $oldThread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'created_at' => now()->subDays(2)
        ]);
        $newThread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'created_at' => now()
        ]);

        $latestThreads = $forum->latestThreads(1);

        $this->assertCount(1, $latestThreads);
        $this->assertEquals($newThread->id, $latestThreads->first()->id);
    }

    /** @test */
    public function it_can_get_thread_count()
    {
        $forum = Forum::factory()->create();
        ForumThread::factory()->count(5)->create(['forum_id' => $forum->id]);

        $this->assertEquals(5, $forum->getThreadCount());
    }

    /** @test */
    public function it_can_get_post_count()
    {
        $forum = Forum::factory()->create();
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        
        // Create posts for the thread
        \App\Models\ForumPost::factory()->count(3)->create(['thread_id' => $thread->id]);

        $this->assertEquals(3, $forum->getPostCount());
    }

    /** @test */
    public function it_can_scope_active_forums()
    {
        Forum::factory()->create(['is_active' => true]);
        Forum::factory()->create(['is_active' => false]);

        $activeForums = Forum::active()->get();

        $this->assertCount(1, $activeForums);
        $this->assertTrue($activeForums->first()->is_active);
    }

    /** @test */
    public function it_can_scope_public_forums()
    {
        Forum::factory()->create(['is_public' => true]);
        Forum::factory()->create(['is_public' => false]);

        $publicForums = Forum::public()->get();

        $this->assertCount(1, $publicForums);
        $this->assertTrue($publicForums->first()->is_public);
    }

    /** @test */
    public function it_can_scope_forums_for_organization()
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        
        Forum::factory()->create(['organization_id' => $organization1->id]);
        Forum::factory()->create(['organization_id' => $organization2->id]);

        $org1Forums = Forum::forOrganization($organization1->id)->get();

        $this->assertCount(1, $org1Forums);
        $this->assertEquals($organization1->id, $org1Forums->first()->organization_id);
    }

    /** @test */
    public function it_can_check_if_user_can_access_forum()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $publicForum = Forum::factory()->create(['is_public' => true]);
        $privateForum = Forum::factory()->create([
            'is_public' => false,
            'organization_id' => $organization->id
        ]);

        // User can access public forum
        $this->assertTrue($publicForum->canUserAccess($user));

        // User cannot access private forum without organization membership
        $this->assertFalse($privateForum->canUserAccess($user));

        // Add user to organization
        $organization->users()->attach($user->id);

        // Now user can access private forum
        $this->assertTrue($privateForum->canUserAccess($user));
    }

    /** @test */
    public function it_can_get_last_activity()
    {
        $forum = Forum::factory()->create();
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = \App\Models\ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'created_at' => now()->subHour()
        ]);

        $lastActivity = $forum->getLastActivity();

        $this->assertNotNull($lastActivity);
        $this->assertEquals($post->created_at->toDateTimeString(), $lastActivity->toDateTimeString());
    }

    /** @test */
    public function it_returns_null_for_last_activity_when_no_posts()
    {
        $forum = Forum::factory()->create();

        $this->assertNull($forum->getLastActivity());
    }

    /** @test */
    public function it_can_get_moderators()
    {
        $forum = Forum::factory()->create();
        $moderator = User::factory()->create();
        
        // Assuming there's a moderators relationship
        $forum->moderators()->attach($moderator->id);

        $this->assertCount(1, $forum->moderators);
        $this->assertEquals($moderator->id, $forum->moderators->first()->id);
    }

    /** @test */
    public function it_can_check_if_user_is_moderator()
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();
        $moderator = User::factory()->create();
        
        $forum->moderators()->attach($moderator->id);

        $this->assertFalse($forum->isModerator($user));
        $this->assertTrue($forum->isModerator($moderator));
    }

    /** @test */
    public function it_has_proper_fillable_attributes()
    {
        $forum = new Forum();
        
        $expectedFillable = [
            'name',
            'description',
            'organization_id',
            'is_active',
            'is_public',
            'sort_order',
            'settings'
        ];

        $this->assertEquals($expectedFillable, $forum->getFillable());
    }

    /** @test */
    public function it_casts_attributes_properly()
    {
        $forum = Forum::factory()->create([
            'is_active' => 1,
            'is_public' => 0,
            'settings' => ['key' => 'value']
        ]);

        $this->assertIsBool($forum->is_active);
        $this->assertIsBool($forum->is_public);
        $this->assertIsArray($forum->settings);
        $this->assertTrue($forum->is_active);
        $this->assertFalse($forum->is_public);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Forum::create([]);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $forum = Forum::factory()->create();
        $forumId = $forum->id;

        $forum->delete();

        $this->assertSoftDeleted('forums', ['id' => $forumId]);
        $this->assertCount(0, Forum::all());
        $this->assertCount(1, Forum::withTrashed()->get());
    }
}