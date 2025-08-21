<?php

namespace Tests\Feature\Forum;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function guest_users_can_view_public_forums()
    {
        $publicForum = Forum::factory()->create(['is_public' => true]);

        $response = $this->get(route('forums.show', $publicForum));

        $response->assertStatus(200);
        $response->assertSee($publicForum->name);
    }

    /** @test */
    public function guest_users_cannot_view_private_forums()
    {
        $privateForum = Forum::factory()->create(['is_public' => false]);

        $response = $this->get(route('forums.show', $privateForum));

        $response->assertStatus(403);
    }

    /** @test */
    public function authenticated_users_can_view_public_forums()
    {
        $user = User::factory()->create();
        $publicForum = Forum::factory()->create(['is_public' => true]);

        $response = $this->actingAs($user)->get(route('forums.show', $publicForum));

        $response->assertStatus(200);
        $response->assertSee($publicForum->name);
    }

    /** @test */
    public function authenticated_users_cannot_view_private_forums_without_organization_membership()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $privateForum = Forum::factory()->create([
            'is_public' => false,
            'organization_id' => $organization->id
        ]);

        $response = $this->actingAs($user)->get(route('forums.show', $privateForum));

        $response->assertStatus(403);
    }

    /** @test */
    public function organization_members_can_view_private_forums()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user->id);
        
        $privateForum = Forum::factory()->create([
            'is_public' => false,
            'organization_id' => $organization->id
        ]);

        $response = $this->actingAs($user)->get(route('forums.show', $privateForum));

        $response->assertStatus(200);
        $response->assertSee($privateForum->name);
    }

    /** @test */
    public function guest_users_cannot_create_threads()
    {
        $publicForum = Forum::factory()->create(['is_public' => true]);

        $response = $this->get(route('forums.threads.create', $publicForum));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_users_can_create_threads_in_accessible_forums()
    {
        $user = User::factory()->create();
        $publicForum = Forum::factory()->create(['is_public' => true]);

        $response = $this->actingAs($user)->get(route('forums.threads.create', $publicForum));

        $response->assertStatus(200);
        $response->assertSee('Create Thread');
    }

    /** @test */
    public function authenticated_users_cannot_create_threads_in_inaccessible_forums()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $privateForum = Forum::factory()->create([
            'is_public' => false,
            'organization_id' => $organization->id
        ]);

        $response = $this->actingAs($user)->get(route('forums.threads.create', $privateForum));

        $response->assertStatus(403);
    }

    /** @test */
    public function users_can_create_threads_with_valid_data()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);

        $threadData = [
            'title' => 'Test Thread',
            'content' => 'This is a test thread content',
        ];

        $response = $this->actingAs($user)->post(route('forums.threads.store', $forum), $threadData);

        $response->assertRedirect();
        $this->assertDatabaseHas('forum_threads', [
            'title' => 'Test Thread',
            'content' => 'This is a test thread content',
            'forum_id' => $forum->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function thread_creation_requires_valid_data()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);

        $response = $this->actingAs($user)->post(route('forums.threads.store', $forum), []);

        $response->assertSessionHasErrors(['title', 'content']);
    }

    /** @test */
    public function users_can_reply_to_threads_in_accessible_forums()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);

        $postData = [
            'content' => 'This is a test reply',
        ];

        $response = $this->actingAs($user)->post(route('forums.posts.store', [$forum, $thread]), $postData);

        $response->assertRedirect();
        $this->assertDatabaseHas('forum_posts', [
            'content' => 'This is a test reply',
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function users_cannot_reply_to_locked_threads()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'is_locked' => true
        ]);

        $postData = [
            'content' => 'This is a test reply',
        ];

        $response = $this->actingAs($user)->post(route('forums.posts.store', [$forum, $thread]), $postData);

        $response->assertStatus(403);
    }

    /** @test */
    public function users_can_edit_their_own_posts()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)->get(route('forums.posts.edit', $post));

        $response->assertStatus(200);
        $response->assertSee('Edit Post');
    }

    /** @test */
    public function users_cannot_edit_other_users_posts()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($user)->get(route('forums.posts.edit', $post));

        $response->assertStatus(403);
    }

    /** @test */
    public function users_can_delete_their_own_posts()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)->delete(route('forums.posts.delete', $post));

        $response->assertRedirect();
        $this->assertSoftDeleted('forum_posts', ['id' => $post->id]);
    }

    /** @test */
    public function users_cannot_delete_other_users_posts()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($user)->delete(route('forums.posts.delete', $post));

        $response->assertStatus(403);
    }

    /** @test */
    public function moderators_can_edit_any_post_in_their_forums()
    {
        $moderator = User::factory()->create();
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $forum->moderators()->attach($moderator->id);
        
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($moderator)->get(route('forums.posts.edit', $post));

        $response->assertStatus(200);
    }

    /** @test */
    public function moderators_can_delete_any_post_in_their_forums()
    {
        $moderator = User::factory()->create();
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $forum->moderators()->attach($moderator->id);
        
        $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
        $post = ForumPost::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($moderator)->delete(route('forums.posts.delete', $post));

        $response->assertRedirect();
        $this->assertSoftDeleted('forum_posts', ['id' => $post->id]);
    }

    /** @test */
    public function moderators_can_pin_threads()
    {
        $moderator = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $forum->moderators()->attach($moderator->id);
        
        $thread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'is_pinned' => false
        ]);

        $response = $this->actingAs($moderator)->post(route('forums.threads.pin', [$forum, $thread]));

        $response->assertRedirect();
        $this->assertTrue($thread->fresh()->is_pinned);
    }

    /** @test */
    public function non_moderators_cannot_pin_threads()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'is_pinned' => false
        ]);

        $response = $this->actingAs($user)->post(route('forums.threads.pin', [$forum, $thread]));

        $response->assertStatus(403);
    }

    /** @test */
    public function moderators_can_lock_threads()
    {
        $moderator = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $forum->moderators()->attach($moderator->id);
        
        $thread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'is_locked' => false
        ]);

        $response = $this->actingAs($moderator)->post(route('forums.threads.lock', [$forum, $thread]));

        $response->assertRedirect();
        $this->assertTrue($thread->fresh()->is_locked);
    }

    /** @test */
    public function non_moderators_cannot_lock_threads()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create(['is_public' => true]);
        $thread = ForumThread::factory()->create([
            'forum_id' => $forum->id,
            'is_locked' => false
        ]);

        $response = $this->actingAs($user)->post(route('forums.threads.lock', [$forum, $thread]));

        $response->assertStatus(403);
    }

    /** @test */
    public function inactive_forums_are_not_accessible()
    {
        $user = User::factory()->create();
        $inactiveForum = Forum::factory()->create([
            'is_public' => true,
            'is_active' => false
        ]);

        $response = $this->actingAs($user)->get(route('forums.show', $inactiveForum));

        $response->assertStatus(404);
    }

    /** @test */
    public function forum_index_only_shows_accessible_forums()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user->id);

        $publicForum = Forum::factory()->create(['is_public' => true, 'name' => 'Public Forum']);
        $privateForum = Forum::factory()->create([
            'is_public' => false,
            'organization_id' => $organization->id,
            'name' => 'Private Forum'
        ]);
        $inaccessibleForum = Forum::factory()->create([
            'is_public' => false,
            'name' => 'Inaccessible Forum'
        ]);

        $response = $this->actingAs($user)->get(route('forums.index'));

        $response->assertStatus(200);
        $response->assertSee('Public Forum');
        $response->assertSee('Private Forum');
        $response->assertDontSee('Inaccessible Forum');
    }
}