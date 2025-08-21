<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use App\Models\UserRegistrationStep;
use App\Models\UserPlatformInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserProfileRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_one_profile()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(UserProfile::class, $user->profile);
        $this->assertEquals($profile->id, $user->profile->id);
    }

    public function test_user_has_many_skills()
    {
        $user = User::factory()->create();
        $skills = UserSkill::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->skills);
        $this->assertInstanceOf(UserSkill::class, $user->skills->first());
    }

    public function test_user_has_many_volunteering_interests()
    {
        $user = User::factory()->create();
        $interests = UserVolunteeringInterest::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->volunteeringInterests);
        $this->assertInstanceOf(UserVolunteeringInterest::class, $user->volunteeringInterests->first());
    }

    public function test_user_has_many_volunteering_history()
    {
        $user = User::factory()->create();
        $history = UserVolunteeringHistory::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->volunteeringHistory);
        $this->assertInstanceOf(UserVolunteeringHistory::class, $user->volunteeringHistory->first());
    }

    public function test_user_has_many_documents()
    {
        $user = User::factory()->create();
        $documents = UserDocument::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->documents);
        $this->assertInstanceOf(UserDocument::class, $user->documents->first());
    }

    public function test_user_has_many_alumni_organizations()
    {
        $user = User::factory()->create();
        $alumni = UserAlumniOrganization::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->alumniOrganizations);
        $this->assertInstanceOf(UserAlumniOrganization::class, $user->alumniOrganizations->first());
    }

    public function test_user_has_many_registration_steps()
    {
        $user = User::factory()->create();
        $steps = UserRegistrationStep::factory()->count(4)->create(['user_id' => $user->id]);

        $this->assertCount(4, $user->registrationSteps);
        $this->assertInstanceOf(UserRegistrationStep::class, $user->registrationSteps->first());
    }

    public function test_user_has_many_platform_interests()
    {
        $user = User::factory()->create();
        $interests = UserPlatformInterest::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->platformInterests);
        $this->assertInstanceOf(UserPlatformInterest::class, $user->platformInterests->first());
    }

    public function test_profile_completion_percentage_attribute()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'profile_completion_percentage' => 75
        ]);

        $this->assertEquals(75, $user->profile_completion_percentage);
    }

    public function test_profile_completion_percentage_attribute_without_profile()
    {
        $user = User::factory()->create();

        $this->assertEquals(0, $user->profile_completion_percentage);
    }

    public function test_has_completed_registration_with_all_steps()
    {
        $user = User::factory()->create();
        $requiredSteps = ['basic_info', 'profile_details', 'interests', 'verification'];

        foreach ($requiredSteps as $step) {
            UserRegistrationStep::factory()->create([
                'user_id' => $user->id,
                'step_name' => $step,
                'is_completed' => true
            ]);
        }

        $this->assertTrue($user->hasCompletedRegistration());
    }

    public function test_has_completed_registration_with_missing_steps()
    {
        $user = User::factory()->create();
        
        UserRegistrationStep::factory()->create([
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => true
        ]);

        UserRegistrationStep::factory()->create([
            'user_id' => $user->id,
            'step_name' => 'profile_details',
            'is_completed' => false
        ]);

        $this->assertFalse($user->hasCompletedRegistration());
    }

    public function test_get_next_registration_step_returns_first_incomplete()
    {
        $user = User::factory()->create();
        
        UserRegistrationStep::factory()->create([
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => true
        ]);

        $this->assertEquals('profile_details', $user->getNextRegistrationStep());
    }

    public function test_get_next_registration_step_returns_null_when_all_complete()
    {
        $user = User::factory()->create();
        $requiredSteps = ['basic_info', 'profile_details', 'interests', 'verification'];

        foreach ($requiredSteps as $step) {
            UserRegistrationStep::factory()->create([
                'user_id' => $user->id,
                'step_name' => $step,
                'is_completed' => true
            ]);
        }

        $this->assertNull($user->getNextRegistrationStep());
    }

    public function test_get_next_registration_step_returns_first_step_when_none_exist()
    {
        $user = User::factory()->create();

        $this->assertEquals('basic_info', $user->getNextRegistrationStep());
    }
}