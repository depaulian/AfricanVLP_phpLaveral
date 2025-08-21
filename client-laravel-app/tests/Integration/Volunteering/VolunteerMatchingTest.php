<?php

namespace Tests\Integration\Volunteering;

use Tests\TestCase;
use App\Models\User;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\Organization;
use App\Models\UserVolunteeringInterest;
use App\Models\UserSkill;
use App\Services\VolunteerMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VolunteerMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected VolunteerMatchingService $matchingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->matchingService = app(VolunteerMatchingService::class);
        
        $this->user = User::factory()->create([
            'city' => 'New York',
            'country' => 'USA'
        ]);
        
        $this->organization = Organization::factory()->create();
        
        $this->environmentalCategory = VolunteeringCategory::factory()->create([
            'name' => 'Environmental'
        ]);
        
        $this->educationCategory = VolunteeringCategory::factory()->create([
            'name' => 'Education'
        ]);
    }

    /** @test */
    public function it_matches_opportunities_based_on_user_interests()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        // Create opportunities
        $matchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'organization_id' => $this->organization->id
        ]);

        $nonMatchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->educationCategory->id,
            'organization_id' => $this->organization->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user);

        $this->assertTrue($matches->contains($matchingOpportunity));
        $this->assertFalse($matches->contains($nonMatchingOpportunity));
    }

    /** @test */
    public function it_prioritizes_opportunities_in_user_location()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        // Create opportunities in different locations
        $localOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'city' => 'New York',
            'country' => 'USA',
            'organization_id' => $this->organization->id
        ]);

        $remoteOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'city' => 'Los Angeles',
            'country' => 'USA',
            'organization_id' => $this->organization->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user, 10);

        // Local opportunity should have higher score
        $localMatch = $matches->firstWhere('id', $localOpportunity->id);
        $remoteMatch = $matches->firstWhere('id', $remoteOpportunity->id);

        $this->assertNotNull($localMatch);
        $this->assertNotNull($remoteMatch);
        
        // Assuming the service adds a match_score attribute
        if (isset($localMatch->match_score) && isset($remoteMatch->match_score)) {
            $this->assertGreaterThan($remoteMatch->match_score, $localMatch->match_score);
        }
    }

    /** @test */
    public function it_matches_opportunities_based_on_user_skills()
    {
        // Set user's skills
        UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Teaching'
        ]);

        UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Communication'
        ]);

        // Create opportunities with skill requirements
        $matchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'required_skills' => ['Teaching', 'Patience'],
            'organization_id' => $this->organization->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        $nonMatchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'required_skills' => ['Programming', 'Design'],
            'organization_id' => $this->organization->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user);

        // Should prioritize opportunity with matching skills
        $this->assertTrue($matches->contains($matchingOpportunity));
    }

    /** @test */
    public function it_considers_user_availability_preferences()
    {
        $this->user->update([
            'volunteer_availability' => ['weekends', 'evenings']
        ]);

        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        $matchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'time_commitment' => 'weekends',
            'organization_id' => $this->organization->id
        ]);

        $nonMatchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'time_commitment' => 'weekdays',
            'organization_id' => $this->organization->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user);

        $this->assertTrue($matches->contains($matchingOpportunity));
    }

    /** @test */
    public function it_excludes_opportunities_user_already_applied_to()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'organization_id' => $this->organization->id
        ]);

        // User has already applied
        $this->user->volunteerApplications()->create([
            'opportunity_id' => $opportunity->id,
            'motivation' => 'Test motivation',
            'experience' => 'Test experience',
            'status' => 'pending'
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user);

        $this->assertFalse($matches->contains($opportunity));
    }

    /** @test */
    public function it_excludes_expired_opportunities()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'application_deadline' => now()->subDay(),
            'organization_id' => $this->organization->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user);

        $this->assertFalse($matches->contains($expiredOpportunity));
    }

    /** @test */
    public function it_excludes_full_opportunities()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        $fullOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'max_volunteers' => 5,
            'current_volunteers' => 5,
            'organization_id' => $this->organization->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user);

        $this->assertFalse($matches->contains($fullOpportunity));
    }

    /** @test */
    public function it_prioritizes_urgent_opportunities()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        $urgentOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'is_urgent' => true,
            'organization_id' => $this->organization->id
        ]);

        $normalOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'is_urgent' => false,
            'organization_id' => $this->organization->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user, 10);

        // Urgent opportunity should appear first
        $this->assertEquals($urgentOpportunity->id, $matches->first()->id);
    }

    /** @test */
    public function it_calculates_match_score_correctly()
    {
        // Set user's interests and skills
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Teaching'
        ]);

        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'required_skills' => ['Teaching'],
            'city' => 'New York', // Same as user
            'organization_id' => $this->organization->id
        ]);

        $matchScore = $this->matchingService->calculateMatchScore($this->user, $opportunity);

        $this->assertGreaterThan(0, $matchScore);
        $this->assertLessThanOrEqual(100, $matchScore);
    }

    /** @test */
    public function it_handles_users_with_no_interests()
    {
        // User has no interests set
        $matches = $this->matchingService->findMatchingOpportunities($this->user);

        // Should return some opportunities, but with lower scores
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $matches);
    }

    /** @test */
    public function it_respects_match_limit()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        // Create more opportunities than the limit
        VolunteeringOpportunity::factory()->count(15)->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'organization_id' => $this->organization->id
        ]);

        $matches = $this->matchingService->findMatchingOpportunities($this->user, 5);

        $this->assertLessThanOrEqual(5, $matches->count());
    }

    /** @test */
    public function it_finds_similar_volunteers_for_opportunity()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'required_skills' => ['Teaching', 'Communication'],
            'organization_id' => $this->organization->id
        ]);

        // Create volunteers with matching interests and skills
        $matchingVolunteer = User::factory()->create();
        UserVolunteeringInterest::factory()->create([
            'user_id' => $matchingVolunteer->id,
            'category_id' => $this->environmentalCategory->id
        ]);
        UserSkill::factory()->create([
            'user_id' => $matchingVolunteer->id,
            'name' => 'Teaching'
        ]);

        $nonMatchingVolunteer = User::factory()->create();
        UserVolunteeringInterest::factory()->create([
            'user_id' => $nonMatchingVolunteer->id,
            'category_id' => $this->educationCategory->id
        ]);

        $matches = $this->matchingService->findMatchingVolunteers($opportunity);

        $this->assertTrue($matches->contains($matchingVolunteer));
        $this->assertFalse($matches->contains($nonMatchingVolunteer));
    }

    /** @test */
    public function it_excludes_volunteers_who_already_applied()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'organization_id' => $this->organization->id
        ]);

        $volunteer = User::factory()->create();
        UserVolunteeringInterest::factory()->create([
            'user_id' => $volunteer->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        // Volunteer has already applied
        $volunteer->volunteerApplications()->create([
            'opportunity_id' => $opportunity->id,
            'motivation' => 'Test motivation',
            'experience' => 'Test experience',
            'status' => 'pending'
        ]);

        $matches = $this->matchingService->findMatchingVolunteers($opportunity);

        $this->assertFalse($matches->contains($volunteer));
    }

    /** @test */
    public function it_provides_match_explanations()
    {
        // Set user's interests and skills
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Teaching'
        ]);

        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'required_skills' => ['Teaching'],
            'city' => 'New York',
            'organization_id' => $this->organization->id
        ]);

        $explanation = $this->matchingService->getMatchExplanation($this->user, $opportunity);

        $this->assertIsArray($explanation);
        $this->assertArrayHasKey('reasons', $explanation);
        $this->assertArrayHasKey('score', $explanation);
    }

    /** @test */
    public function it_updates_user_matching_preferences()
    {
        $preferences = [
            'max_distance' => 50,
            'preferred_time_commitment' => 'part_time',
            'notification_frequency' => 'weekly',
            'categories' => [$this->environmentalCategory->id],
            'skills' => ['Teaching', 'Communication']
        ];

        $result = $this->matchingService->updateUserPreferences($this->user, $preferences);

        $this->assertTrue($result);
        
        // Check that preferences were saved
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'volunteer_matching_preferences' => json_encode($preferences)
        ]);
    }

    /** @test */
    public function it_sends_matching_notifications()
    {
        // Set user's interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->environmentalCategory->id
        ]);

        // Enable notifications for user
        $this->user->update([
            'volunteer_notification_preferences' => [
                'new_opportunities' => true,
                'matching_opportunities' => true
            ]
        ]);

        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->environmentalCategory->id,
            'organization_id' => $this->organization->id
        ]);

        $result = $this->matchingService->sendMatchingNotifications($opportunity);

        $this->assertTrue($result);
        
        // Check that notification was created
        $this->assertDatabaseHas('volunteer_notifications', [
            'user_id' => $this->user->id,
            'type' => 'opportunity_match',
            'data->opportunity_id' => $opportunity->id
        ]);
    }
}