<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\VolunteeringRole;
use App\Models\VolunteerApplication;
use App\Models\VolunteerAssignment;
use App\Models\Organization;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class VolunteeringOpportunityTest extends TestCase
{
    use RefreshDatabase;

    protected $opportunity;
    protected $user;
    protected $organization;
    protected $category;
    protected $role;
    protected $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->city = City::factory()->create();
        $this->category = VolunteeringCategory::factory()->create();
        $this->role = VolunteeringRole::factory()->create([
            'category_id' => $this->category->id
        ]);

        $this->opportunity = VolunteeringOpportunity::factory()->create([
            'organization_id' => $this->organization->id,
            'city_id' => $this->city->id,
            'category_id' => $this->category->id,
            'role_id' => $this->role->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'title', 'description', 'requirements', 'benefits',
            'organization_id', 'category_id', 'role_id', 'city_id',
            'address', 'latitude', 'longitude', 'start_date', 'end_date',
            'application_deadline', 'volunteers_needed', 'time_commitment',
            'schedule_type', 'schedule_details', 'skills_required',
            'age_requirement', 'background_check_required',
            'training_provided', 'transportation_provided',
            'meals_provided', 'accommodation_provided',
            'contact_person', 'contact_email', 'contact_phone',
            'application_instructions', 'additional_info',
            'status', 'featured', 'urgent', 'created_by'
        ];

        $this->assertEquals($fillable, $this->opportunity->getFillable());
    }

    /** @test */
    public function it_has_proper_casts()
    {
        $casts = [
            'id' => 'int',
            'start_date' => 'date',
            'end_date' => 'date',
            'application_deadline' => 'date',
            'volunteers_needed' => 'integer',
            'time_commitment' => 'integer',
            'skills_required' => 'array',
            'age_requirement' => 'integer',
            'background_check_required' => 'boolean',
            'training_provided' => 'boolean',
            'transportation_provided' => 'boolean',
            'meals_provided' => 'boolean',
            'accommodation_provided' => 'boolean',
            'featured' => 'boolean',
            'urgent' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
        ];

        $this->assertEquals($casts, $this->opportunity->getCasts());
    }

    /** @test */
    public function it_belongs_to_organization()
    {
        $this->assertInstanceOf(Organization::class, $this->opportunity->organization);
        $this->assertEquals($this->organization->id, $this->opportunity->organization->id);
    }

    /** @test */
    public function it_belongs_to_category()
    {
        $this->assertInstanceOf(VolunteeringCategory::class, $this->opportunity->category);
        $this->assertEquals($this->category->id, $this->opportunity->category->id);
    }

    /** @test */
    public function it_belongs_to_role()
    {
        $this->assertInstanceOf(VolunteeringRole::class, $this->opportunity->role);
        $this->assertEquals($this->role->id, $this->opportunity->role->id);
    }

    /** @test */
    public function it_belongs_to_city()
    {
        $this->assertInstanceOf(City::class, $this->opportunity->city);
        $this->assertEquals($this->city->id, $this->opportunity->city->id);
    }

    /** @test */
    public function it_belongs_to_creator()
    {
        $this->assertInstanceOf(User::class, $this->opportunity->creator);
        $this->assertEquals($this->user->id, $this->opportunity->creator->id);
    }

    /** @test */
    public function it_has_many_applications()
    {
        $application1 = VolunteerApplication::factory()->create([
            'opportunity_id' => $this->opportunity->id
        ]);
        $application2 = VolunteerApplication::factory()->create([
            'opportunity_id' => $this->opportunity->id
        ]);

        $this->assertInstanceOf(Collection::class, $this->opportunity->applications);
        $this->assertCount(2, $this->opportunity->applications);
        $this->assertTrue($this->opportunity->applications->contains($application1));
        $this->assertTrue($this->opportunity->applications->contains($application2));
    }

    /** @test */
    public function it_has_many_assignments()
    {
        $application = VolunteerApplication::factory()->create([
            'opportunity_id' => $this->opportunity->id
        ]);
        
        $assignment1 = VolunteerAssignment::factory()->create([
            'opportunity_id' => $this->opportunity->id,
            'application_id' => $application->id
        ]);
        $assignment2 = VolunteerAssignment::factory()->create([
            'opportunity_id' => $this->opportunity->id,
            'application_id' => $application->id
        ]);

        $this->assertInstanceOf(Collection::class, $this->opportunity->assignments);
        $this->assertCount(2, $this->opportunity->assignments);
        $this->assertTrue($this->opportunity->assignments->contains($assignment1));
        $this->assertTrue($this->opportunity->assignments->contains($assignment2));
    }

    /** @test */
    public function it_can_scope_active_opportunities()
    {
        $activeOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'active',
            'start_date' => Carbon::now()->addDays(1),
            'end_date' => Carbon::now()->addDays(30),
            'application_deadline' => Carbon::now()->addDays(15)
        ]);

        $inactiveOpportunity = VolunteeringOpportunity::factory()->create([
            'status' => 'inactive'
        ]);

        $activeOpportunities = VolunteeringOpportunity::active()->get();

        $this->assertTrue($activeOpportunities->contains($activeOpportunity));
        $this->assertFalse($activeOpportunities->contains($inactiveOpportunity));
    }

    /** @test */
    public function it_can_scope_featured_opportunities()
    {
        $featuredOpportunity = VolunteeringOpportunity::factory()->create([
            'featured' => true
        ]);

        $regularOpportunity = VolunteeringOpportunity::factory()->create([
            'featured' => false
        ]);

        $featuredOpportunities = VolunteeringOpportunity::featured()->get();

        $this->assertTrue($featuredOpportunities->contains($featuredOpportunity));
        $this->assertFalse($featuredOpportunities->contains($regularOpportunity));
    }

    /** @test */
    public function it_can_scope_urgent_opportunities()
    {
        $urgentOpportunity = VolunteeringOpportunity::factory()->create([
            'urgent' => true
        ]);

        $regularOpportunity = VolunteeringOpportunity::factory()->create([
            'urgent' => false
        ]);

        $urgentOpportunities = VolunteeringOpportunity::urgent()->get();

        $this->assertTrue($urgentOpportunities->contains($urgentOpportunity));
        $this->assertFalse($urgentOpportunities->contains($regularOpportunity));
    }

    /** @test */
    public function it_can_scope_by_category()
    {
        $category1 = VolunteeringCategory::factory()->create();
        $category2 = VolunteeringCategory::factory()->create();

        $opportunity1 = VolunteeringOpportunity::factory()->create([
            'category_id' => $category1->id
        ]);
        $opportunity2 = VolunteeringOpportunity::factory()->create([
            'category_id' => $category2->id
        ]);

        $categoryOpportunities = VolunteeringOpportunity::byCategory($category1->id)->get();

        $this->assertTrue($categoryOpportunities->contains($opportunity1));
        $this->assertFalse($categoryOpportunities->contains($opportunity2));
    }

    /** @test */
    public function it_can_scope_by_location()
    {
        $city1 = City::factory()->create();
        $city2 = City::factory()->create();

        $opportunity1 = VolunteeringOpportunity::factory()->create([
            'city_id' => $city1->id
        ]);
        $opportunity2 = VolunteeringOpportunity::factory()->create([
            'city_id' => $city2->id
        ]);

        $locationOpportunities = VolunteeringOpportunity::byLocation($city1->id)->get();

        $this->assertTrue($locationOpportunities->contains($opportunity1));
        $this->assertFalse($locationOpportunities->contains($opportunity2));
    }

    /** @test */
    public function it_can_scope_accepting_applications()
    {
        $acceptingOpportunity = VolunteeringOpportunity::factory()->create([
            'application_deadline' => Carbon::now()->addDays(10),
            'status' => 'active'
        ]);

        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'application_deadline' => Carbon::now()->subDays(1),
            'status' => 'active'
        ]);

        $acceptingOpportunities = VolunteeringOpportunity::acceptingApplications()->get();

        $this->assertTrue($acceptingOpportunities->contains($acceptingOpportunity));
        $this->assertFalse($acceptingOpportunities->contains($expiredOpportunity));
    }

    /** @test */
    public function it_can_check_if_accepting_applications()
    {
        $acceptingOpportunity = VolunteeringOpportunity::factory()->create([
            'application_deadline' => Carbon::now()->addDays(10),
            'status' => 'active'
        ]);

        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'application_deadline' => Carbon::now()->subDays(1),
            'status' => 'active'
        ]);

        $this->assertTrue($acceptingOpportunity->isAcceptingApplications());
        $this->assertFalse($expiredOpportunity->isAcceptingApplications());
    }

    /** @test */
    public function it_can_check_if_full()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'volunteers_needed' => 2
        ]);

        // Create 2 accepted applications
        VolunteerApplication::factory()->count(2)->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'accepted'
        ]);

        $this->assertTrue($opportunity->isFull());

        // Create one more pending application
        VolunteerApplication::factory()->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'pending'
        ]);

        // Should still be full (only accepted applications count)
        $this->assertTrue($opportunity->fresh()->isFull());
    }

    /** @test */
    public function it_can_get_available_spots()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'volunteers_needed' => 5
        ]);

        // Create 2 accepted applications
        VolunteerApplication::factory()->count(2)->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'accepted'
        ]);

        $this->assertEquals(3, $opportunity->getAvailableSpots());
    }

    /** @test */
    public function it_can_get_application_count()
    {
        VolunteerApplication::factory()->count(3)->create([
            'opportunity_id' => $this->opportunity->id
        ]);

        $this->assertEquals(3, $this->opportunity->getApplicationCount());
    }

    /** @test */
    public function it_can_get_accepted_application_count()
    {
        VolunteerApplication::factory()->count(2)->create([
            'opportunity_id' => $this->opportunity->id,
            'status' => 'accepted'
        ]);

        VolunteerApplication::factory()->create([
            'opportunity_id' => $this->opportunity->id,
            'status' => 'pending'
        ]);

        $this->assertEquals(2, $this->opportunity->getAcceptedApplicationCount());
    }

    /** @test */
    public function it_can_check_if_user_has_applied()
    {
        $user = User::factory()->create();

        $this->assertFalse($this->opportunity->hasUserApplied($user));

        VolunteerApplication::factory()->create([
            'opportunity_id' => $this->opportunity->id,
            'user_id' => $user->id
        ]);

        $this->assertTrue($this->opportunity->fresh()->hasUserApplied($user));
    }

    /** @test */
    public function it_can_get_user_application()
    {
        $user = User::factory()->create();

        $this->assertNull($this->opportunity->getUserApplication($user));

        $application = VolunteerApplication::factory()->create([
            'opportunity_id' => $this->opportunity->id,
            'user_id' => $user->id
        ]);

        $userApplication = $this->opportunity->fresh()->getUserApplication($user);
        $this->assertInstanceOf(VolunteerApplication::class, $userApplication);
        $this->assertEquals($application->id, $userApplication->id);
    }

    /** @test */
    public function it_can_check_if_expired()
    {
        $activeOpportunity = VolunteeringOpportunity::factory()->create([
            'end_date' => Carbon::now()->addDays(10)
        ]);

        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'end_date' => Carbon::now()->subDays(1)
        ]);

        $this->assertFalse($activeOpportunity->isExpired());
        $this->assertTrue($expiredOpportunity->isExpired());
    }

    /** @test */
    public function it_can_get_days_until_deadline()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'application_deadline' => Carbon::now()->addDays(5)
        ]);

        $this->assertEquals(5, $opportunity->getDaysUntilDeadline());

        $expiredOpportunity = VolunteeringOpportunity::factory()->create([
            'application_deadline' => Carbon::now()->subDays(2)
        ]);

        $this->assertEquals(-2, $expiredOpportunity->getDaysUntilDeadline());
    }

    /** @test */
    public function it_can_get_duration_in_days()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(10)
        ]);

        $this->assertEquals(10, $opportunity->getDurationInDays());
    }

    /** @test */
    public function it_can_get_formatted_schedule()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'schedule_type' => 'flexible',
            'schedule_details' => 'Weekends preferred'
        ]);

        $expected = 'Flexible - Weekends preferred';
        $this->assertEquals($expected, $opportunity->getFormattedSchedule());
    }

    /** @test */
    public function it_can_get_required_skills_list()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'skills_required' => ['Communication', 'Leadership', 'Problem Solving']
        ]);

        $this->assertEquals('Communication, Leadership, Problem Solving', $opportunity->getRequiredSkillsList());

        $noSkillsOpportunity = VolunteeringOpportunity::factory()->create([
            'skills_required' => null
        ]);

        $this->assertEquals('No specific skills required', $noSkillsOpportunity->getRequiredSkillsList());
    }

    /** @test */
    public function it_can_get_benefits_list()
    {
        $opportunity = VolunteeringOpportunity::factory()->create([
            'training_provided' => true,
            'transportation_provided' => true,
            'meals_provided' => false,
            'accommodation_provided' => false
        ]);

        $benefits = $opportunity->getBenefitsList();
        
        $this->assertContains('Training provided', $benefits);
        $this->assertContains('Transportation provided', $benefits);
        $this->assertNotContains('Meals provided', $benefits);
        $this->assertNotContains('Accommodation provided', $benefits);
    }

    /** @test */
    public function it_can_search_by_title()
    {
        $opportunity1 = VolunteeringOpportunity::factory()->create([
            'title' => 'Environmental Cleanup Volunteer'
        ]);
        $opportunity2 = VolunteeringOpportunity::factory()->create([
            'title' => 'Food Bank Assistant'
        ]);

        $results = VolunteeringOpportunity::search('Environmental')->get();

        $this->assertTrue($results->contains($opportunity1));
        $this->assertFalse($results->contains($opportunity2));
    }

    /** @test */
    public function it_can_search_by_description()
    {
        $opportunity1 = VolunteeringOpportunity::factory()->create([
            'description' => 'Help clean up local parks and beaches'
        ]);
        $opportunity2 = VolunteeringOpportunity::factory()->create([
            'description' => 'Assist with food distribution'
        ]);

        $results = VolunteeringOpportunity::search('parks')->get();

        $this->assertTrue($results->contains($opportunity1));
        $this->assertFalse($results->contains($opportunity2));
    }

    /** @test */
    public function it_has_proper_validation_rules()
    {
        $rules = VolunteeringOpportunity::validationRules();

        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('organization_id', $rules);
        $this->assertArrayHasKey('category_id', $rules);
        $this->assertArrayHasKey('start_date', $rules);
        $this->assertArrayHasKey('end_date', $rules);
        $this->assertArrayHasKey('application_deadline', $rules);
        $this->assertArrayHasKey('volunteers_needed', $rules);
    }

    /** @test */
    public function it_can_get_status_badge_class()
    {
        $activeOpportunity = VolunteeringOpportunity::factory()->create(['status' => 'active']);
        $inactiveOpportunity = VolunteeringOpportunity::factory()->create(['status' => 'inactive']);
        $completedOpportunity = VolunteeringOpportunity::factory()->create(['status' => 'completed']);

        $this->assertEquals('badge-success', $activeOpportunity->getStatusBadgeClass());
        $this->assertEquals('badge-secondary', $inactiveOpportunity->getStatusBadgeClass());
        $this->assertEquals('badge-info', $completedOpportunity->getStatusBadgeClass());
    }

    /** @test */
    public function it_can_get_urgency_badge_class()
    {
        $urgentOpportunity = VolunteeringOpportunity::factory()->create(['urgent' => true]);
        $regularOpportunity = VolunteeringOpportunity::factory()->create(['urgent' => false]);

        $this->assertEquals('badge-danger', $urgentOpportunity->getUrgencyBadgeClass());
        $this->assertEquals('badge-light', $regularOpportunity->getUrgencyBadgeClass());
    }
}