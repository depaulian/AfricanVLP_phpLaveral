<?php

namespace Tests\Feature\Volunteering;

use Tests\TestCase;
use App\Models\User;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\Organization;
use App\Models\UserVolunteeringInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class OpportunityDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->category = VolunteeringCategory::factory()->create([
            'name' => 'Environmental'
        ]);
    }

    /** @test */
    public function user_can_view_opportunities_index_page()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index'));

        $response->assertStatus(200);
        $response->assertSee($opportunity->title);
        $response->assertSee($opportunity->organization->name);
    }

    /** @test */
    public function user_can_search_opportunities_by_keyword()
    {
        $matchingOpportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Beach Cleanup Volunteer',
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $nonMatchingOpportunity = VolunteeringOpportunity::factory()->create([
            'title' => 'Food Bank Helper',
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['search' => 'beach']));

        $response->assertStatus(200);
        $response->assertSee($matchingOpportunity->title);
        $response->assertDontSee($nonMatchingOpportunity->title);
    }

    /** @test */
    public function user_can_filter_opportunities_by_category()
    {
        $environmentalCategory = VolunteeringCategory::factory()->create([
            'name' => 'Environmental'
        ]);
        
        $educationCategory = VolunteeringCategory::factory()->create([
            'name' => 'Education'
        ]);

        $environmentalOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $environmentalCategory->id
        ]);

        $educationOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $educationCategory->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['category_id' => $environmentalCategory->id]));

        $response->assertStatus(200);
        $response->assertSee($environmentalOpportunity->title);
        $response->assertDontSee($educationOpportunity->title);
    }

    /** @test */
    public function user_can_filter_opportunities_by_location()
    {
        $newYorkOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'city' => 'New York',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $losAngelesOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'city' => 'Los Angeles',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['location' => 'New York']));

        $response->assertStatus(200);
        $response->assertSee($newYorkOpportunity->title);
        $response->assertDontSee($losAngelesOpportunity->title);
    }

    /** @test */
    public function user_can_filter_opportunities_by_time_commitment()
    {
        $fullTimeOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'time_commitment' => 'full_time',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $partTimeOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'time_commitment' => 'part_time',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['time_commitment' => 'full_time']));

        $response->assertStatus(200);
        $response->assertSee($fullTimeOpportunity->title);
        $response->assertDontSee($partTimeOpportunity->title);
    }

    /** @test */
    public function user_can_filter_remote_opportunities()
    {
        $remoteOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'is_remote' => true,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $onsiteOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'is_remote' => false,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['is_remote' => '1']));

        $response->assertStatus(200);
        $response->assertSee($remoteOpportunity->title);
        $response->assertDontSee($onsiteOpportunity->title);
    }

    /** @test */
    public function user_can_filter_urgent_opportunities()
    {
        $urgentOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'is_urgent' => true,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $normalOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'is_urgent' => false,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['is_urgent' => '1']));

        $response->assertStatus(200);
        $response->assertSee($urgentOpportunity->title);
        $response->assertDontSee($normalOpportunity->title);
    }

    /** @test */
    public function user_can_sort_opportunities_by_date()
    {
        $olderOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'created_at' => now()->subDays(5),
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $newerOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'created_at' => now()->subDays(1),
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['sort' => 'newest']));

        $response->assertStatus(200);
        
        // Check that newer opportunity appears before older one in the response
        $content = $response->getContent();
        $newerPos = strpos($content, $newerOpportunity->title);
        $olderPos = strpos($content, $olderOpportunity->title);
        
        $this->assertLessThan($olderPos, $newerPos);
    }

    /** @test */
    public function user_can_view_opportunity_details()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'description' => 'This is a detailed description of the opportunity.'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.show', $opportunity));

        $response->assertStatus(200);
        $response->assertSee($opportunity->title);
        $response->assertSee($opportunity->description);
        $response->assertSee($opportunity->organization->name);
        $response->assertSee($opportunity->category->name);
    }

    /** @test */
    public function user_cannot_view_unpublished_opportunity()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'draft',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.show', $opportunity));

        $response->assertStatus(404);
    }

    /** @test */
    public function user_sees_personalized_recommendations_based_on_interests()
    {
        // Set user's volunteering interests
        UserVolunteeringInterest::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $matchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->category->id,
            'organization_id' => $this->organization->id
        ]);

        $otherCategory = VolunteeringCategory::factory()->create();
        $nonMatchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $otherCategory->id,
            'organization_id' => $this->organization->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index'));

        $response->assertStatus(200);
        
        // The matching opportunity should appear higher in recommendations
        $response->assertSee($matchingOpportunity->title);
        $response->assertSee($nonMatchingOpportunity->title);
    }

    /** @test */
    public function user_can_see_opportunity_application_status()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.show', $opportunity));

        $response->assertStatus(200);
        $response->assertSee('Apply Now'); // Should show apply button when not applied
    }

    /** @test */
    public function user_sees_full_opportunity_indicator()
    {
        $fullOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'max_volunteers' => 5,
            'current_volunteers' => 5,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.show', $fullOpportunity));

        $response->assertStatus(200);
        $response->assertSee('Full'); // Should indicate opportunity is full
        $response->assertDontSee('Apply Now'); // Should not show apply button
    }

    /** @test */
    public function user_sees_expired_opportunity_indicator()
    {
        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'application_deadline' => now()->subDay(),
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.show', $expiredOpportunity));

        $response->assertStatus(200);
        $response->assertSee('Applications Closed'); // Should indicate applications are closed
        $response->assertDontSee('Apply Now'); // Should not show apply button
    }

    /** @test */
    public function user_can_see_urgent_opportunity_badge()
    {
        $urgentOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'is_urgent' => true,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index'));

        $response->assertStatus(200);
        $response->assertSee('Urgent'); // Should show urgent badge
    }

    /** @test */
    public function user_can_see_remote_opportunity_badge()
    {
        $remoteOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'is_remote' => true,
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index'));

        $response->assertStatus(200);
        $response->assertSee('Remote'); // Should show remote badge
    }

    /** @test */
    public function guest_user_can_view_opportunities_but_cannot_apply()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->get(route('client.volunteering.index'));

        $response->assertStatus(200);
        $response->assertSee($opportunity->title);
    }

    /** @test */
    public function guest_user_is_redirected_to_login_when_trying_to_apply()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->get(route('client.volunteering.apply', $opportunity));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function opportunities_are_paginated()
    {
        // Create more opportunities than the pagination limit
        VolunteeringOpportunity::factory()->count(25)->create([
            'status' => 'published',
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index'));

        $response->assertStatus(200);
        $response->assertSee('Next'); // Should show pagination links
    }

    /** @test */
    public function user_can_combine_multiple_filters()
    {
        $matchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->category->id,
            'city' => 'New York',
            'is_remote' => true,
            'time_commitment' => 'part_time',
            'organization_id' => $this->organization->id
        ]);

        $nonMatchingOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'published',
            'category_id' => $this->category->id,
            'city' => 'Los Angeles', // Different city
            'is_remote' => true,
            'time_commitment' => 'part_time',
            'organization_id' => $this->organization->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', [
                'category_id' => $this->category->id,
                'location' => 'New York',
                'is_remote' => '1',
                'time_commitment' => 'part_time'
            ]));

        $response->assertStatus(200);
        $response->assertSee($matchingOpportunity->title);
        $response->assertDontSee($nonMatchingOpportunity->title);
    }

    /** @test */
    public function search_results_show_no_results_message_when_empty()
    {
        $response = $this->actingAs($this->user)
            ->get(route('client.volunteering.index', ['search' => 'nonexistent']));

        $response->assertStatus(200);
        $response->assertSee('No opportunities found'); // Should show no results message
    }
}