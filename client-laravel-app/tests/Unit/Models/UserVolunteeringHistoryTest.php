<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserVolunteeringHistory;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserVolunteeringHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_volunteering_history_belongs_to_user()
    {
        $user = User::factory()->create();
        $history = UserVolunteeringHistory::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $history->user);
        $this->assertEquals($user->id, $history->user->id);
    }

    public function test_user_volunteering_history_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $history = UserVolunteeringHistory::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $history->organization);
        $this->assertEquals($organization->id, $history->organization->id);
    }

    public function test_duration_attribute_for_current_position()
    {
        $startDate = now()->subMonths(6);
        $history = UserVolunteeringHistory::factory()->create([
            'start_date' => $startDate,
            'is_current' => true
        ]);

        $expectedDuration = $startDate->format('M Y') . ' - Present';
        $this->assertEquals($expectedDuration, $history->duration);
    }

    public function test_duration_attribute_for_completed_position()
    {
        $startDate = now()->subYear();
        $endDate = now()->subMonths(6);
        $history = UserVolunteeringHistory::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => false
        ]);

        $expectedDuration = $startDate->format('M Y') . ' - ' . $endDate->format('M Y');
        $this->assertEquals($expectedDuration, $history->duration);
    }

    public function test_duration_attribute_without_end_date()
    {
        $startDate = now()->subMonths(6);
        $history = UserVolunteeringHistory::factory()->create([
            'start_date' => $startDate,
            'end_date' => null,
            'is_current' => false
        ]);

        $expectedDuration = $startDate->format('M Y');
        $this->assertEquals($expectedDuration, $history->duration);
    }

    public function test_duration_in_months_attribute_for_current_position()
    {
        $startDate = now()->subMonths(6);
        $history = UserVolunteeringHistory::factory()->create([
            'start_date' => $startDate,
            'is_current' => true
        ]);

        $this->assertEquals(6, $history->duration_in_months);
    }

    public function test_duration_in_months_attribute_for_completed_position()
    {
        $startDate = now()->subYear();
        $endDate = now()->subMonths(6);
        $history = UserVolunteeringHistory::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => false
        ]);

        $this->assertEquals(6, $history->duration_in_months);
    }

    public function test_skills_gained_list_attribute()
    {
        $skills = ['Leadership', 'Communication', 'Project Management'];
        $history = UserVolunteeringHistory::factory()->create(['skills_gained' => $skills]);

        $this->assertEquals('Leadership, Communication, Project Management', $history->skills_gained_list);
    }

    public function test_current_scope()
    {
        UserVolunteeringHistory::factory()->create(['is_current' => true]);
        UserVolunteeringHistory::factory()->create(['is_current' => false]);

        $currentHistory = UserVolunteeringHistory::current()->get();

        $this->assertCount(1, $currentHistory);
        $this->assertTrue($currentHistory->first()->is_current);
    }

    public function test_completed_scope()
    {
        UserVolunteeringHistory::factory()->create(['is_current' => true]);
        UserVolunteeringHistory::factory()->create(['is_current' => false]);

        $completedHistory = UserVolunteeringHistory::completed()->get();

        $this->assertCount(1, $completedHistory);
        $this->assertFalse($completedHistory->first()->is_current);
    }

    public function test_dates_are_cast_to_carbon()
    {
        $startDate = '2023-01-15';
        $endDate = '2023-12-15';
        $history = UserVolunteeringHistory::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $history->start_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $history->end_date);
        $this->assertEquals($startDate, $history->start_date->format('Y-m-d'));
        $this->assertEquals($endDate, $history->end_date->format('Y-m-d'));
    }

    public function test_skills_gained_is_cast_to_array()
    {
        $skills = ['Leadership', 'Communication'];
        $history = UserVolunteeringHistory::factory()->create(['skills_gained' => $skills]);

        $this->assertIsArray($history->skills_gained);
        $this->assertEquals($skills, $history->skills_gained);
    }

    public function test_is_current_is_cast_to_boolean()
    {
        $history = UserVolunteeringHistory::factory()->create(['is_current' => 1]);
        $this->assertIsBool($history->is_current);
        $this->assertTrue($history->is_current);

        $history = UserVolunteeringHistory::factory()->create(['is_current' => 0]);
        $this->assertIsBool($history->is_current);
        $this->assertFalse($history->is_current);
    }
}