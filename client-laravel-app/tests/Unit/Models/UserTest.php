<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Organization;
use App\Models\Notification;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_email_must_be_unique()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    public function test_user_has_organizations_relationship()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        
        $user->organizations()->attach($organization->id);

        $this->assertTrue($user->organizations->contains($organization));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->organizations);
    }

    public function test_user_has_notifications_relationship()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->notifications->contains($notification));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->notifications);
    }

    public function test_user_has_resources_relationship()
    {
        $user = User::factory()->create();
        $resource = Resource::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($user->resources->contains($resource));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->resources);
    }

    public function test_user_can_check_if_belongs_to_organization()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        
        $this->assertFalse($user->belongsToOrganization($organization->id));
        
        $user->organizations()->attach($organization->id);
        
        $this->assertTrue($user->belongsToOrganization($organization->id));
    }

    public function test_user_full_name_accessor()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_user_avatar_url_accessor()
    {
        $user = User::factory()->create(['avatar' => 'avatar.jpg']);
        
        $this->assertStringContainsString('avatar.jpg', $user->avatar_url);
    }

    public function test_user_volunteering_interests()
    {
        $user = User::factory()->create([
            'volunteering_interests' => ['education', 'environment', 'health']
        ]);

        $this->assertIsArray($user->volunteering_interests);
        $this->assertContains('education', $user->volunteering_interests);
        $this->assertCount(3, $user->volunteering_interests);
    }

    public function test_user_can_be_soft_deleted()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull($user->fresh()->deleted_at);
    }

    public function test_user_email_verification()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        $this->assertFalse($user->hasVerifiedEmail());
        
        $user->markEmailAsVerified();
        
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_active_scope()
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);
        User::factory()->create(['status' => 'suspended']);

        $activeUsers = User::active()->get();
        
        $this->assertCount(1, $activeUsers);
        $this->assertEquals('active', $activeUsers->first()->status);
    }

    public function test_user_search_scope()
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $results = User::search('John')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);
    }

    public function test_user_preferences_json_casting()
    {
        $preferences = [
            'theme' => 'light',
            'notifications' => true,
            'language' => 'en',
            'privacy' => [
                'profile_visible' => true,
                'contact_visible' => false,
            ],
        ];

        $user = User::factory()->create(['preferences' => $preferences]);

        $this->assertEquals($preferences, $user->preferences);
        $this->assertEquals('light', $user->preferences['theme']);
        $this->assertTrue($user->preferences['privacy']['profile_visible']);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();
        
        $newData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
            'phone' => '+1234567890',
            'location' => 'New York, NY',
        ];

        $user->updateProfile($newData);

        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('Updated bio', $user->fresh()->bio);
        $this->assertEquals('+1234567890', $user->fresh()->phone);
        $this->assertEquals('New York, NY', $user->fresh()->location);
    }

    public function test_user_volunteering_history()
    {
        $user = User::factory()->create();
        
        $history = [
            [
                'organization' => 'Red Cross',
                'role' => 'Volunteer',
                'start_date' => '2023-01-01',
                'end_date' => '2023-12-31',
                'hours' => 120,
            ]
        ];

        $user->update(['volunteering_history' => $history]);

        $this->assertIsArray($user->fresh()->volunteering_history);
        $this->assertEquals('Red Cross', $user->volunteering_history[0]['organization']);
        $this->assertEquals(120, $user->volunteering_history[0]['hours']);
    }

    public function test_user_skills_and_expertise()
    {
        $user = User::factory()->create([
            'skills' => ['PHP', 'Laravel', 'JavaScript', 'Project Management']
        ]);

        $this->assertIsArray($user->skills);
        $this->assertContains('Laravel', $user->skills);
        $this->assertCount(4, $user->skills);
    }

    public function test_user_can_join_organization()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $user->joinOrganization($organization->id, 'member');

        $this->assertTrue($user->belongsToOrganization($organization->id));
        $this->assertEquals('member', $user->organizations()->first()->pivot->role);
    }

    public function test_user_can_leave_organization()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        
        $user->organizations()->attach($organization->id, ['role' => 'member']);
        $this->assertTrue($user->belongsToOrganization($organization->id));

        $user->leaveOrganization($organization->id);
        
        $this->assertFalse($user->belongsToOrganization($organization->id));
    }
}