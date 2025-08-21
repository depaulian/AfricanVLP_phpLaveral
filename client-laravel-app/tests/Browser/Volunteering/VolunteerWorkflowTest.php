<?php

namespace Tests\Browser\Volunteering;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\Organization;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class VolunteerWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'volunteer@example.com',
            'password' => bcrypt('password')
        ]);
        
        $this->organization = Organization::factory()->create();
        $this->category = VolunteeringCategory::factory()->create([
            'name' => 'Environmental'
        ]);
        
        $this->opportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Beach Cleanup Volunteer',
            'description' => 'Help us clean up the local beach and protect marine life.',
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'application_deadline' => now()->addWeek()
        ]);
    }

    /** @test */
    public function user_can_complete_full_volunteer_application_workflow()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering')
                    ->assertSee('Volunteer Opportunities')
                    ->assertSee($this->opportunity->title)
                    ->click('@opportunity-' . $this->opportunity->id)
                    ->assertSee($this->opportunity->description)
                    ->assertSee('Apply Now')
                    ->click('@apply-button')
                    ->assertSee('Apply for this opportunity')
                    ->type('motivation', 'I am passionate about environmental conservation and want to make a difference.')
                    ->type('experience', 'I have volunteered for beach cleanups before and understand the importance of this work.')
                    ->type('availability', 'Weekends and some weekday evenings')
                    ->type('skills', 'Teamwork, physical fitness, attention to detail')
                    ->type('additional_info', 'I can bring my own gloves and tools if needed.')
                    ->click('@submit-application')
                    ->assertSee('Your application has been submitted successfully!')
                    ->visit('/volunteering/applications')
                    ->assertSee($this->opportunity->title)
                    ->assertSee('Pending Review');
        });

        // Verify application was created in database
        $this->assertDatabaseHas('volunteer_applications', [
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function user_can_search_and_filter_opportunities()
    {
        // Create additional opportunities for filtering
        $educationCategory = VolunteeringCategory::factory()->create(['name' => 'Education']);
        $educationOpportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Tutoring Program',
            'status' => 'published',
            'category_id' => $educationCategory->id,
            'organization_id' => $this->organization->id
        ]);

        $this->browse(function (Browser $browser) use ($educationOpportunity) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering')
                    ->assertSee($this->opportunity->title)
                    ->assertSee($educationOpportunity->title)
                    
                    // Test search functionality
                    ->type('search', 'beach')
                    ->click('@search-button')
                    ->assertSee($this->opportunity->title)
                    ->assertDontSee($educationOpportunity->title)
                    
                    // Clear search
                    ->visit('/volunteering')
                    
                    // Test category filter
                    ->select('category_id', $this->category->id)
                    ->click('@filter-button')
                    ->assertSee($this->opportunity->title)
                    ->assertDontSee($educationOpportunity->title);
        });
    }

    /** @test */
    public function user_can_view_and_manage_their_applications()
    {
        // Create an application
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) use ($application) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering/applications')
                    ->assertSee('My Applications')
                    ->assertSee($this->opportunity->title)
                    ->assertSee('Pending Review')
                    
                    // View application details
                    ->click('@view-application-' . $application->id)
                    ->assertSee('Application Details')
                    ->assertSee($this->opportunity->title)
                    ->assertSee($application->motivation)
                    
                    // Test withdrawal
                    ->assertSee('Withdraw Application')
                    ->click('@withdraw-button')
                    ->assertSee('Withdraw Application')
                    ->type('withdrawal_reason', 'Schedule conflict')
                    ->click('@confirm-withdrawal')
                    ->assertSee('Application withdrawn successfully')
                    ->assertSee('Withdrawn');
        });

        // Verify withdrawal in database
        $this->assertEquals('withdrawn', $application->fresh()->status);
    }

    /** @test */
    public function user_can_set_volunteering_preferences()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering/preferences')
                    ->assertSee('Volunteering Preferences')
                    
                    // Set interests
                    ->check('categories[]', $this->category->id)
                    
                    // Set availability
                    ->check('availability[]', 'weekends')
                    ->check('availability[]', 'evenings')
                    
                    // Set location preferences
                    ->type('max_distance', '25')
                    ->select('preferred_time_commitment', 'part_time')
                    
                    // Set notification preferences
                    ->check('notifications[new_opportunities]')
                    ->check('notifications[application_updates]')
                    
                    ->click('@save-preferences')
                    ->assertSee('Preferences saved successfully');
        });

        // Verify preferences were saved
        $this->assertDatabaseHas('user_volunteering_interests', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);
    }

    /** @test */
    public function user_can_view_volunteer_dashboard()
    {
        // Create some data for the dashboard
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'approved'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering/dashboard')
                    ->assertSee('Volunteer Dashboard')
                    ->assertSee('My Statistics')
                    ->assertSee('Recent Applications')
                    ->assertSee('Recommended Opportunities')
                    ->assertSee($this->opportunity->title);
        });
    }

    /** @test */
    public function user_cannot_apply_to_expired_opportunity()
    {
        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Expired Opportunity',
            'status' => 'published',
            'application_deadline' => now()->subDay(),
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $this->browse(function (Browser $browser) use ($expiredOpportunity) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering/opportunities/' . $expiredOpportunity->id)
                    ->assertSee('Applications Closed')
                    ->assertDontSee('Apply Now');
        });
    }

    /** @test */
    public function user_cannot_apply_to_full_opportunity()
    {
        $fullOpportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Full Opportunity',
            'status' => 'published',
            'max_volunteers' => 5,
            'current_volunteers' => 5,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $this->browse(function (Browser $browser) use ($fullOpportunity) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering/opportunities/' . $fullOpportunity->id)
                    ->assertSee('Full')
                    ->assertDontSee('Apply Now');
        });
    }

    /** @test */
    public function user_sees_application_status_on_opportunity_page()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering/opportunities/' . $this->opportunity->id)
                    ->assertSee('Application Status')
                    ->assertSee('Pending Review')
                    ->assertDontSee('Apply Now');
        });
    }

    /** @test */
    public function user_can_filter_applications_by_status()
    {
        // Create applications with different statuses
        $pendingApplication = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $approvedApplication = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'status' => 'approved'
        ]);

        $this->browse(function (Browser $browser) use ($pendingApplication, $approvedApplication) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering/applications')
                    ->assertSee($pendingApplication->opportunity->title)
                    ->assertSee($approvedApplication->opportunity->title)
                    
                    // Filter by pending
                    ->select('status', 'pending')
                    ->click('@filter-applications')
                    ->assertSee($pendingApplication->opportunity->title)
                    ->assertDontSee($approvedApplication->opportunity->title)
                    
                    // Filter by approved
                    ->select('status', 'approved')
                    ->click('@filter-applications')
                    ->assertSee($approvedApplication->opportunity->title)
                    ->assertDontSee($pendingApplication->opportunity->title);
        });
    }

    /** @test */
    public function user_receives_real_time_notifications()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering')
                    
                    // Simulate receiving a notification
                    ->script([
                        "window.Echo.private('user.{$this->user->id}').notification((notification) => {
                            if (notification.type === 'ApplicationStatusUpdated') {
                                document.getElementById('notification-area').innerHTML = 
                                    '<div class=\"alert alert-info\">' + notification.message + '</div>';
                            }
                        });"
                    ])
                    
                    // Trigger a notification (this would normally come from the server)
                    ->script([
                        "window.dispatchEvent(new CustomEvent('notification', {
                            detail: {
                                type: 'ApplicationStatusUpdated',
                                message: 'Your application has been approved!'
                            }
                        }));"
                    ])
                    
                    ->pause(1000)
                    ->assertSee('Your application has been approved!');
        });
    }

    /** @test */
    public function user_can_navigate_through_opportunity_pagination()
    {
        // Create many opportunities to trigger pagination
        VolunteeringOpportunity::factory()->count(25)->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering')
                    ->assertSee('Volunteer Opportunities')
                    
                    // Should see pagination
                    ->assertSee('Next')
                    
                    // Navigate to next page
                    ->click('@next-page')
                    ->assertQueryStringHas('page', '2')
                    
                    // Navigate back
                    ->click('@previous-page')
                    ->assertQueryStringMissing('page');
        });
    }

    /** @test */
    public function user_can_sort_opportunities()
    {
        // Create opportunities with different dates
        $olderOpportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Older Opportunity',
            'status' => 'published',
            'created_at' => now()->subDays(5),
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $newerOpportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Newer Opportunity',
            'status' => 'published',
            'created_at' => now()->subDays(1),
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $this->browse(function (Browser $browser) use ($olderOpportunity, $newerOpportunity) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering')
                    
                    // Sort by newest first
                    ->select('sort', 'newest')
                    ->click('@sort-button')
                    ->pause(1000)
                    
                    // Newer opportunity should appear first
                    ->assertSeeIn('@opportunity-list', $newerOpportunity->title)
                    
                    // Sort by oldest first
                    ->select('sort', 'oldest')
                    ->click('@sort-button')
                    ->pause(1000)
                    
                    // Older opportunity should appear first
                    ->assertSeeIn('@opportunity-list', $olderOpportunity->title);
        });
    }

    /** @test */
    public function user_can_view_opportunity_details_modal()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/volunteering')
                    ->assertSee($this->opportunity->title)
                    
                    // Click quick view button
                    ->click('@quick-view-' . $this->opportunity->id)
                    ->pause(500)
                    
                    // Modal should appear
                    ->assertVisible('@opportunity-modal')
                    ->assertSeeIn('@opportunity-modal', $this->opportunity->title)
                    ->assertSeeIn('@opportunity-modal', $this->opportunity->description)
                    
                    // Close modal
                    ->click('@close-modal')
                    ->pause(500)
                    ->assertNotVisible('@opportunity-modal');
        });
    }
}