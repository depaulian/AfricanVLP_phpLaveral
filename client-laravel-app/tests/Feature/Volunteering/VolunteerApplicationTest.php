<?php

namespace Tests\Feature\Volunteering;

use Tests\TestCase;
use App\Models\User;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerApplication;
use App\Models\Organization;
use App\Models\VolunteeringCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class VolunteerApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->category = VolunteeringCategory::factory()->create();
        $this->opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'application_deadline' => now()->addWeek()
        ]);
    }

    /** @test */
    public function user_can_view_application_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.apply', $this->opportunity));

        $response->assertStatus(200);
        $response->assertSee($this->opportunity->title);
        $response->assertSee('Apply for this opportunity');
        $response->assertSee('Why are you interested');
        $response->assertSee('Relevant experience');
    }

    /** @test */
    public function user_can_submit_application()
    {
        $applicationData = [
            'motivation' => 'I am passionate about helping the community.',
            'experience' => 'I have 2 years of volunteer experience.',
            'availability' => 'Weekends and evenings',
            'skills' => 'Communication, teamwork, problem-solving',
            'additional_info' => 'I am very committed to this cause.'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('client.volunteering.apply.store', $this->opportunity), $applicationData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('volunteer_applications', [
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'motivation' => $applicationData['motivation'],
            'experience' => $applicationData['experience'],
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function application_requires_motivation()
    {
        $applicationData = [
            'experience' => 'I have 2 years of volunteer experience.',
            'availability' => 'Weekends and evenings',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('client.volunteering.apply.store', $this->opportunity), $applicationData);

        $response->assertSessionHasErrors('motivation');
        
        $this->assertDatabaseMissing('volunteer_applications', [
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ]);
    }

    /** @test */
    public function application_requires_experience()
    {
        $applicationData = [
            'motivation' => 'I am passionate about helping the community.',
            'availability' => 'Weekends and evenings',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('client.volunteering.apply.store', $this->opportunity), $applicationData);

        $response->assertSessionHasErrors('experience');
        
        $this->assertDatabaseMissing('volunteer_applications', [
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ]);
    }

    /** @test */
    public function user_cannot_apply_twice_for_same_opportunity()
    {
        // Create existing application
        VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ]);

        $applicationData = [
            'motivation' => 'I am passionate about helping the community.',
            'experience' => 'I have 2 years of volunteer experience.',
            'availability' => 'Weekends and evenings',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('client.volunteering.apply.store', $this->opportunity), $applicationData);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Should still only have one application
        $this->assertEquals(1, VolunteerApplication::where([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ])->count());
    }

    /** @test */
    public function user_cannot_apply_to_expired_opportunity()
    {
        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'application_deadline' => now()->subDay(),
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.apply', $expiredOpportunity));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function user_cannot_apply_to_full_opportunity()
    {
        $fullOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'max_volunteers' => 5,
            'current_volunteers' => 5,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.apply', $fullOpportunity));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function user_cannot_apply_to_unpublished_opportunity()
    {
        $draftOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'draft',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.apply', $draftOpportunity));

        $response->assertStatus(404);
    }

    /** @test */
    public function user_can_view_their_applications()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.applications.index'));

        $response->assertStatus(200);
        $response->assertSee($this->opportunity->title);
        $response->assertSee($application->status_display);
    }

    /** @test */
    public function user_can_view_application_details()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'motivation' => 'Test motivation'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.applications.show', $application));

        $response->assertStatus(200);
        $response->assertSee($this->opportunity->title);
        $response->assertSee('Test motivation');
        $response->assertSee($application->status_display);
    }

    /** @test */
    public function user_cannot_view_other_users_applications()
    {
        $otherUser = User::factory()->create();
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $otherUser->id,
            'opportunity_id' => $this->opportunity->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.applications.show', $application));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_withdraw_pending_application()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('client.volunteering.applications.withdraw', $application), [
                'withdrawal_reason' => 'Changed my mind'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('withdrawn', $application->fresh()->status);
        $this->assertEquals('Changed my mind', $application->fresh()->withdrawal_reason);
    }

    /** @test */
    public function user_can_withdraw_approved_application()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('client.volunteering.applications.withdraw', $application), [
                'withdrawal_reason' => 'Schedule conflict'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('withdrawn', $application->fresh()->status);
        $this->assertEquals('Schedule conflict', $application->fresh()->withdrawal_reason);
    }

    /** @test */
    public function user_cannot_withdraw_rejected_application()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'rejected'
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('client.volunteering.applications.withdraw', $application), [
                'withdrawal_reason' => 'Changed my mind'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals('rejected', $application->fresh()->status);
    }

    /** @test */
    public function withdrawal_reason_is_required()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('client.volunteering.applications.withdraw', $application), []);

        $response->assertSessionHasErrors('withdrawal_reason');
        $this->assertEquals('pending', $application->fresh()->status);
    }

    /** @test */
    public function application_increments_opportunity_application_count()
    {
        $initialCount = $this->opportunity->application_count;

        $applicationData = [
            'motivation' => 'I am passionate about helping the community.',
            'experience' => 'I have 2 years of volunteer experience.',
            'availability' => 'Weekends and evenings',
        ];

        $this->actingAs($this->user)
            ->post(route('client.volunteering.apply.store', $this->opportunity), $applicationData);

        $this->assertEquals($initialCount + 1, $this->opportunity->fresh()->application_count);
    }

    /** @test */
    public function user_sees_application_status_on_opportunity_page()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.show', $this->opportunity));

        $response->assertStatus(200);
        $response->assertSee('Application Status');
        $response->assertSee('Pending Review');
        $response->assertDontSee('Apply Now');
    }

    /** @test */
    public function user_can_filter_applications_by_status()
    {
        $pendingApplication = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $approvedApplication = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.applications.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee($pendingApplication->opportunity->title);
        $response->assertDontSee($approvedApplication->opportunity->title);
    }

    /** @test */
    public function applications_are_ordered_by_most_recent()
    {
        $olderApplication = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'applied_at' => now()->subDays(5)
        ]);

        $newerApplication = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'applied_at' => now()->subDays(1)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.applications.index'));

        $response->assertStatus(200);
        
        // Check that newer application appears before older one
        $content = $response->getContent();
        $newerPos = strpos($content, $newerApplication->opportunity->title);
        $olderPos = strpos($content, $olderApplication->opportunity->title);
        
        $this->assertLessThan($olderPos, $newerPos);
    }

    /** @test */
    public function guest_cannot_access_application_pages()
    {
        $response = $this->get(route('client.volunteering.apply', $this->opportunity));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('client.volunteering.applications.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function application_creates_status_history_entry()
    {
        $applicationData = [
            'motivation' => 'I am passionate about helping the community.',
            'experience' => 'I have 2 years of volunteer experience.',
            'availability' => 'Weekends and evenings',
        ];

        $this->actingAs($this->user)
            ->post(route('client.volunteering.apply.store', $this->opportunity), $applicationData);

        $application = VolunteerApplication::where([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ])->first();

        $this->assertDatabaseHas('volunteer_application_status_histories', [
            'application_id' => $application->id,
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_see_application_timeline()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ]);

        // Add some status history
        $application->statusHistories()->create([
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => $this->user->id,
            'notes' => 'Application submitted'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.applications.show', $application));

        $response->assertStatus(200);
        $response->assertSee('Application Timeline');
        $response->assertSee('Application submitted');
    }

    /** @test */
    public function user_receives_confirmation_after_successful_application()
    {
        $applicationData = [
            'motivation' => 'I am passionate about helping the community.',
            'experience' => 'I have 2 years of volunteer experience.',
            'availability' => 'Weekends and evenings',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('client.volunteering.apply.store', $this->opportunity), $applicationData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Your application has been submitted successfully!');
    }
}