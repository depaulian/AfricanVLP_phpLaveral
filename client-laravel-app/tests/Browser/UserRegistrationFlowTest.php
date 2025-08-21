<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UserRegistrationFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_complete_registration_flow()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->assertSee('Create Account')
                    ->type('name', 'John Doe')
                    ->type('email', 'john@example.com')
                    ->type('password', 'Password123!')
                    ->type('password_confirmation', 'Password123!')
                    ->check('terms')
                    ->press('Register')
                    ->waitForLocation('/dashboard')
                    ->assertSee('Welcome, John Doe')
                    ->assertSee('Complete Your Profile');
        });

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_can_complete_profile_setup()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'profile_completed' => false,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile/setup')
                    ->assertSee('Complete Your Profile')
                    ->type('bio', 'I am passionate about volunteering and making a difference.')
                    ->type('phone', '+1234567890')
                    ->type('location', 'New York, NY')
                    ->select('volunteering_interests[]', 'education')
                    ->select('volunteering_interests[]', 'environment')
                    ->type('skills[]', 'Project Management')
                    ->type('skills[]', 'Communication')
                    ->press('Save Profile')
                    ->waitForText('Profile completed successfully')
                    ->assertPathIs('/dashboard');
        });

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'bio' => 'I am passionate about volunteering and making a difference.',
            'phone' => '+1234567890',
            'location' => 'New York, NY',
            'profile_completed' => true,
        ]);
    }

    public function test_user_can_browse_and_join_organizations()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'name' => 'Environmental Action Group',
            'description' => 'Working to protect our environment',
            'category' => 'environment',
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($user, $organization) {
            $browser->loginAs($user)
                    ->visit('/organizations')
                    ->assertSee('Find Organizations')
                    ->type('search', 'Environmental')
                    ->press('Search')
                    ->waitForText('Environmental Action Group')
                    ->click('@organization-' . $organization->id)
                    ->waitForLocation('/organizations/' . $organization->id)
                    ->assertSee('Environmental Action Group')
                    ->assertSee('Working to protect our environment')
                    ->press('Request to Join')
                    ->waitForText('Join request sent successfully')
                    ->assertSee('Request Pending');
        });

        $this->assertDatabaseHas('tmp_organization_users', [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_search_and_filter_organizations()
    {
        $user = User::factory()->create();
        
        Organization::factory()->create([
            'name' => 'Red Cross',
            'category' => 'healthcare',
            'location' => 'New York',
        ]);
        
        Organization::factory()->create([
            'name' => 'Green Peace',
            'category' => 'environment',
            'location' => 'California',
        ]);
        
        Organization::factory()->create([
            'name' => 'Education First',
            'category' => 'education',
            'location' => 'New York',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/organizations')
                    ->assertSee('Red Cross')
                    ->assertSee('Green Peace')
                    ->assertSee('Education First')
                    
                    // Filter by category
                    ->select('category', 'healthcare')
                    ->press('Filter')
                    ->waitUntilMissing('@organization-green-peace')
                    ->assertSee('Red Cross')
                    ->assertDontSee('Green Peace')
                    ->assertDontSee('Education First')
                    
                    // Clear filters
                    ->press('Clear Filters')
                    ->waitForText('Green Peace')
                    ->assertSee('Red Cross')
                    ->assertSee('Green Peace')
                    ->assertSee('Education First')
                    
                    // Filter by location
                    ->select('location', 'New York')
                    ->press('Filter')
                    ->waitUntilMissing('@organization-green-peace')
                    ->assertSee('Red Cross')
                    ->assertSee('Education First')
                    ->assertDontSee('Green Peace');
        });
    }

    public function test_user_can_view_and_register_for_events()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $event = \App\Models\Event::factory()->create([
            'title' => 'Community Cleanup Day',
            'description' => 'Join us for a community cleanup event',
            'organization_id' => $organization->id,
            'start_date' => now()->addDays(7),
            'location' => 'Central Park, NY',
            'max_participants' => 50,
        ]);

        $this->browse(function (Browser $browser) use ($user, $event) {
            $browser->loginAs($user)
                    ->visit('/events')
                    ->assertSee('Upcoming Events')
                    ->assertSee('Community Cleanup Day')
                    ->click('@event-' . $event->id)
                    ->waitForLocation('/events/' . $event->id)
                    ->assertSee('Community Cleanup Day')
                    ->assertSee('Join us for a community cleanup event')
                    ->assertSee('Central Park, NY')
                    ->press('Register for Event')
                    ->waitForText('Successfully registered for event')
                    ->assertSee('You are registered');
        });

        $this->assertDatabaseHas('event_participants', [
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_user_can_send_and_receive_messages()
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);
        $organization = Organization::factory()->create();
        
        // Both users are members of the same organization
        $organization->users()->attach($user1->id, ['role' => 'member']);
        $organization->users()->attach($user2->id, ['role' => 'member']);

        $this->browse(function (Browser $browser) use ($user1, $user2, $organization) {
            $browser->loginAs($user1)
                    ->visit('/messages')
                    ->assertSee('Messages')
                    ->press('New Message')
                    ->waitForText('Compose Message')
                    ->select('recipient_id', $user2->id)
                    ->type('subject', 'Hello from John')
                    ->type('message', 'Hi Jane, I wanted to reach out about the upcoming event.')
                    ->press('Send Message')
                    ->waitForText('Message sent successfully')
                    ->assertSee('Hello from John');
        });

        $this->browse(function (Browser $browser) use ($user2) {
            $browser->loginAs($user2)
                    ->visit('/messages')
                    ->assertSee('Hello from John')
                    ->click('@message-1')
                    ->waitForText('Hi Jane, I wanted to reach out')
                    ->type('reply_message', 'Hi John, thanks for reaching out!')
                    ->press('Reply')
                    ->waitForText('Reply sent successfully');
        });

        $this->assertDatabaseHas('messages', [
            'sender_id' => $user1->id,
            'recipient_id' => $user2->id,
            'subject' => 'Hello from John',
        ]);
    }

    public function test_user_can_update_profile_and_preferences()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile')
                    ->assertSee('Profile Settings')
                    ->type('name', 'John Updated Doe')
                    ->type('bio', 'Updated bio information')
                    ->type('phone', '+1987654321')
                    ->press('Update Profile')
                    ->waitForText('Profile updated successfully')
                    
                    // Test preferences
                    ->click('Preferences')
                    ->waitForText('Notification Preferences')
                    ->check('notifications[email_events]')
                    ->uncheck('notifications[email_messages]')
                    ->select('language', 'es')
                    ->press('Save Preferences')
                    ->waitForText('Preferences saved successfully');
        });

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Updated Doe',
            'bio' => 'Updated bio information',
            'phone' => '+1987654321',
        ]);
    }

    public function test_user_can_upload_profile_picture()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile')
                    ->assertSee('Profile Picture')
                    ->attach('avatar', __DIR__ . '/fixtures/test-avatar.jpg')
                    ->press('Upload Picture')
                    ->waitForText('Profile picture updated successfully')
                    ->assertPresent('img[src*="avatar"]');
        });

        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_user_can_view_volunteering_history()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Test Org']);
        
        // Add user to organization
        $organization->users()->attach($user->id, [
            'role' => 'volunteer',
            'joined_at' => now()->subMonths(6),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile/volunteering')
                    ->assertSee('Volunteering History')
                    ->assertSee('Test Org')
                    ->assertSee('Volunteer')
                    ->assertSee('6 months ago');
        });
    }

    public function test_accessibility_features_work()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    
                    // Test skip link
                    ->keys('body', ['{tab}'])
                    ->assertFocused('a[href="#main-content"]')
                    ->keys('a[href="#main-content"]', ['{enter}'])
                    ->assertFocused('#main-content')
                    
                    // Test keyboard navigation
                    ->keys('body', ['{tab}'])
                    ->assertFocused('nav a:first-child')
                    
                    // Test ARIA labels
                    ->assertAttribute('nav', 'role', 'navigation')
                    ->assertAttribute('main', 'role', 'main');
        });
    }

    public function test_form_validation_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->press('Register')
                    ->waitForText('The name field is required')
                    ->assertSee('The email field is required')
                    ->assertSee('The password field is required')
                    
                    // Test email validation
                    ->type('email', 'invalid-email')
                    ->press('Register')
                    ->waitForText('The email must be a valid email address')
                    
                    // Test password strength
                    ->type('email', 'test@example.com')
                    ->type('password', 'weak')
                    ->type('password_confirmation', 'weak')
                    ->press('Register')
                    ->waitForText('Password must be at least 8 characters');
        });
    }
}