<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\User;
use App\Models\Event;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_can_be_created()
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'description' => 'A test organization',
            'email' => 'test@organization.com',
        ]);

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertEquals('Test Organization', $organization->name);
        $this->assertEquals('A test organization', $organization->description);
        $this->assertEquals('test@organization.com', $organization->email);
    }

    public function test_organization_name_must_be_unique()
    {
        Organization::factory()->create(['name' => 'Unique Organization']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Organization::factory()->create(['name' => 'Unique Organization']);
    }

    public function test_organization_has_users_relationship()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        $organization->users()->attach($user->id, ['role' => 'member']);

        $this->assertTrue($organization->users->contains($user));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $organization->users);
        $this->assertEquals('member', $organization->users->first()->pivot->role);
    }

    public function test_organization_has_events_relationship()
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($organization->events->contains($event));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $organization->events);
    }

    public function test_organization_has_resources_relationship()
    {
        $organization = Organization::factory()->create();
        $resource = Resource::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($organization->resources->contains($resource));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $organization->resources);
    }

    public function test_organization_logo_url_accessor()
    {
        $organization = Organization::factory()->create(['logo' => 'logo.jpg']);
        
        $this->assertStringContainsString('logo.jpg', $organization->logo_url);
    }

    public function test_organization_can_be_soft_deleted()
    {
        $organization = Organization::factory()->create();
        $organizationId = $organization->id;

        $organization->delete();

        $this->assertSoftDeleted('organizations', ['id' => $organizationId]);
        $this->assertNotNull($organization->fresh()->deleted_at);
    }

    public function test_organization_active_scope()
    {
        Organization::factory()->create(['status' => 'active']);
        Organization::factory()->create(['status' => 'inactive']);
        Organization::factory()->create(['status' => 'pending']);

        $activeOrganizations = Organization::active()->get();
        
        $this->assertCount(1, $activeOrganizations);
        $this->assertEquals('active', $activeOrganizations->first()->status);
    }

    public function test_organization_search_scope()
    {
        Organization::factory()->create(['name' => 'Red Cross', 'description' => 'Healthcare organization']);
        Organization::factory()->create(['name' => 'Green Peace', 'description' => 'Environmental organization']);

        $results = Organization::search('Red')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('Red Cross', $results->first()->name);
    }

    public function test_organization_by_category_scope()
    {
        Organization::factory()->create(['category' => 'healthcare']);
        Organization::factory()->create(['category' => 'education']);
        Organization::factory()->create(['category' => 'healthcare']);

        $healthcareOrgs = Organization::byCategory('healthcare')->get();
        
        $this->assertCount(2, $healthcareOrgs);
        $this->assertTrue($healthcareOrgs->every(fn($org) => $org->category === 'healthcare'));
    }

    public function test_organization_contact_info_json_casting()
    {
        $contactInfo = [
            'phone' => '+1234567890',
            'address' => '123 Main St, City, State',
            'website' => 'https://example.com',
            'social_media' => [
                'facebook' => 'https://facebook.com/org',
                'twitter' => 'https://twitter.com/org',
            ],
        ];

        $organization = Organization::factory()->create(['contact_info' => $contactInfo]);

        $this->assertEquals($contactInfo, $organization->contact_info);
        $this->assertEquals('+1234567890', $organization->contact_info['phone']);
        $this->assertEquals('https://facebook.com/org', $organization->contact_info['social_media']['facebook']);
    }

    public function test_organization_can_add_member()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->addMember($user->id, 'volunteer');

        $this->assertTrue($organization->users->contains($user));
        $this->assertEquals('volunteer', $organization->users->first()->pivot->role);
    }

    public function test_organization_can_remove_member()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        $organization->users()->attach($user->id, ['role' => 'member']);
        $this->assertTrue($organization->users->contains($user));

        $organization->removeMember($user->id);
        
        $this->assertFalse($organization->fresh()->users->contains($user));
    }

    public function test_organization_can_update_member_role()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        $organization->users()->attach($user->id, ['role' => 'member']);
        $this->assertEquals('member', $organization->users->first()->pivot->role);

        $organization->updateMemberRole($user->id, 'admin');
        
        $this->assertEquals('admin', $organization->fresh()->users->first()->pivot->role);
    }

    public function test_organization_member_count_accessor()
    {
        $organization = Organization::factory()->create();
        $users = User::factory()->count(3)->create();
        
        foreach ($users as $user) {
            $organization->users()->attach($user->id, ['role' => 'member']);
        }

        $this->assertEquals(3, $organization->member_count);
    }

    public function test_organization_admin_users_relationship()
    {
        $organization = Organization::factory()->create();
        $adminUser = User::factory()->create();
        $memberUser = User::factory()->create();
        
        $organization->users()->attach($adminUser->id, ['role' => 'admin']);
        $organization->users()->attach($memberUser->id, ['role' => 'member']);

        $admins = $organization->adminUsers;
        
        $this->assertCount(1, $admins);
        $this->assertTrue($admins->contains($adminUser));
        $this->assertFalse($admins->contains($memberUser));
    }

    public function test_organization_upcoming_events_relationship()
    {
        $organization = Organization::factory()->create();
        $pastEvent = Event::factory()->create([
            'organization_id' => $organization->id,
            'start_date' => now()->subDays(5),
        ]);
        $upcomingEvent = Event::factory()->create([
            'organization_id' => $organization->id,
            'start_date' => now()->addDays(5),
        ]);

        $upcomingEvents = $organization->upcomingEvents;
        
        $this->assertCount(1, $upcomingEvents);
        $this->assertTrue($upcomingEvents->contains($upcomingEvent));
        $this->assertFalse($upcomingEvents->contains($pastEvent));
    }

    public function test_organization_settings_json_casting()
    {
        $settings = [
            'allow_public_registration' => true,
            'require_approval' => false,
            'notification_preferences' => [
                'new_members' => true,
                'events' => true,
                'resources' => false,
            ],
        ];

        $organization = Organization::factory()->create(['settings' => $settings]);

        $this->assertEquals($settings, $organization->settings);
        $this->assertTrue($organization->settings['allow_public_registration']);
        $this->assertTrue($organization->settings['notification_preferences']['new_members']);
    }

    public function test_organization_can_check_if_user_is_member()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        $this->assertFalse($organization->hasMember($user->id));
        
        $organization->users()->attach($user->id, ['role' => 'member']);
        
        $this->assertTrue($organization->hasMember($user->id));
    }

    public function test_organization_can_check_if_user_is_admin()
    {
        $organization = Organization::factory()->create();
        $adminUser = User::factory()->create();
        $memberUser = User::factory()->create();
        
        $organization->users()->attach($adminUser->id, ['role' => 'admin']);
        $organization->users()->attach($memberUser->id, ['role' => 'member']);
        
        $this->assertTrue($organization->isAdmin($adminUser->id));
        $this->assertFalse($organization->isAdmin($memberUser->id));
    }
}