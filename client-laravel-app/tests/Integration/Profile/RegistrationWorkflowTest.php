<?php

namespace Tests\Integration\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRegistrationStep;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\VolunteeringCategory;
use App\Models\City;
use App\Models\Country;
use App\Services\UserProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class RegistrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private UserProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = new UserProfileService();
        Mail::fake();
        Notification::fake();
    }

    public function test_complete_registration_workflow()
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $country = Country::factory()->create();
        $category = VolunteeringCategory::factory()->create();

        // Step 1: Basic Info
        $response = $this->actingAs($user)
            ->post(route('registration.step.basic-info'), [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => $user->email,
                'phone_number' => '+1234567890'
            ]);

        $response->assertRedirect(route('registration.step.profile-details'));
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => true
        ]);

        // Step 2: Profile Details
        $response = $this->actingAs($user)
            ->post(route('registration.step.profile-details'), [
                'bio' => 'I am passionate about volunteering',
                'date_of_birth' => '1990-01-01',
                'address' => '123 Main Street',
                'city_id' => $city->id,
                'country_id' => $country->id
            ]);

        $response->assertRedirect(route('registration.step.interests'));
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'profile_details',
            'is_completed' => true
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'I am passionate about volunteering',
            'city_id' => $city->id
        ]);

        // Step 3: Interests and Skills
        $response = $this->actingAs($user)
            ->post(route('registration.step.interests'), [
                'volunteering_interests' => [$category->id],
                'interest_levels' => [$category->id => 'high'],
                'skills' => [
                    ['skill_name' => 'Leadership', 'proficiency_level' => 'advanced', 'years_experience' => 5],
                    ['skill_name' => 'Communication', 'proficiency_level' => 'expert', 'years_experience' => 8]
                ]
            ]);

        $response->assertRedirect(route('registration.step.verification'));
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'interests',
            'is_completed' => true
        ]);

        $this->assertDatabaseHas('user_volunteering_interests', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'interest_level' => 'high'
        ]);

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_name' => 'Leadership',
            'proficiency_level' => 'advanced'
        ]);

        // Step 4: Verification (Email confirmation)
        $response = $this->actingAs($user)
            ->post(route('registration.step.verification'), [
                'email_verified' => true
            ]);

        $response->assertRedirect(route('registration.complete'));
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'verification',
            'is_completed' => true
        ]);

        // Verify registration is complete
        $user->refresh();
        $this->assertTrue($user->hasCompletedRegistration());
        $this->assertNull($user->getNextRegistrationStep());

        // Check welcome email was sent
        Notification::assertSentTo($user, \App\Notifications\WelcomeEmail::class);
    }

    public function test_registration_progress_tracking()
    {
        $user = User::factory()->create();

        // Initially no progress
        $progress = $this->profileService->getRegistrationProgress($user);
        $this->assertEquals(0, $progress['overall']['completed_steps']);
        $this->assertEquals(0, $progress['overall']['percentage']);

        // Complete first step
        $this->profileService->completeRegistrationStep($user, 'basic_info', [
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $progress = $this->profileService->getRegistrationProgress($user);
        $this->assertEquals(1, $progress['overall']['completed_steps']);
        $this->assertEquals(25, $progress['overall']['percentage']);
        $this->assertTrue($progress['basic_info']['completed']);
        $this->assertFalse($progress['profile_details']['completed']);

        // Complete second step
        $this->profileService->completeRegistrationStep($user, 'profile_details', [
            'bio' => 'Test bio'
        ]);

        $progress = $this->profileService->getRegistrationProgress($user);
        $this->assertEquals(2, $progress['overall']['completed_steps']);
        $this->assertEquals(50, $progress['overall']['percentage']);
    }

    public function test_registration_step_navigation()
    {
        $user = User::factory()->create();

        // Should start with basic_info
        $this->assertEquals('basic_info', $user->getNextRegistrationStep());

        // Complete basic_info
        $this->profileService->completeRegistrationStep($user, 'basic_info');
        $this->assertEquals('profile_details', $user->getNextRegistrationStep());

        // Complete profile_details
        $this->profileService->completeRegistrationStep($user, 'profile_details');
        $this->assertEquals('interests', $user->getNextRegistrationStep());

        // Complete interests
        $this->profileService->completeRegistrationStep($user, 'interests');
        $this->assertEquals('verification', $user->getNextRegistrationStep());

        // Complete verification
        $this->profileService->completeRegistrationStep($user, 'verification');
        $this->assertNull($user->getNextRegistrationStep());
    }

    public function test_registration_abandonment_recovery()
    {
        $user = User::factory()->create();

        // Start registration but don't complete
        $this->profileService->completeRegistrationStep($user, 'basic_info', [
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        // Simulate abandonment recovery email
        $response = $this->actingAs($user)
            ->get(route('registration.resume'));

        $response->assertRedirect(route('registration.step.profile-details'));

        // Should be able to continue from where left off
        $this->assertEquals('profile_details', $user->getNextRegistrationStep());
    }

    public function test_registration_data_persistence()
    {
        $user = User::factory()->create();

        // Save partial data in step
        $stepData = [
            'bio' => 'Partial bio',
            'phone_number' => '+1234567890'
        ];

        $step = $this->profileService->completeRegistrationStep($user, 'profile_details', $stepData);

        // Data should be retrievable
        $this->assertEquals($stepData, $step->step_data);

        // Should be able to retrieve and continue
        $savedStep = $user->registrationSteps()->where('step_name', 'profile_details')->first();
        $this->assertEquals($stepData, $savedStep->step_data);
    }

    public function test_registration_validation_prevents_skipping_steps()
    {
        $user = User::factory()->create();

        // Try to access interests step without completing previous steps
        $response = $this->actingAs($user)
            ->get(route('registration.step.interests'));

        $response->assertRedirect(route('registration.step.basic-info'));

        // Complete basic_info but try to skip profile_details
        $this->profileService->completeRegistrationStep($user, 'basic_info');

        $response = $this->actingAs($user)
            ->get(route('registration.step.interests'));

        $response->assertRedirect(route('registration.step.profile-details'));
    }

    public function test_completed_registration_redirects_to_dashboard()
    {
        $user = User::factory()->create();

        // Complete all steps
        $requiredSteps = ['basic_info', 'profile_details', 'interests', 'verification'];
        foreach ($requiredSteps as $step) {
            $this->profileService->completeRegistrationStep($user, $step);
        }

        // Accessing registration should redirect to dashboard
        $response = $this->actingAs($user)
            ->get(route('registration.wizard'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_registration_updates_profile_completion()
    {
        $user = User::factory()->create();
        $city = City::factory()->create();

        // Create profile during registration
        $this->profileService->createProfile($user, [
            'bio' => 'Test bio',
            'phone_number' => '+1234567890',
            'city_id' => $city->id
        ]);

        // Add skills and interests
        $this->profileService->addSkill($user, [
            'skill_name' => 'Leadership',
            'proficiency_level' => 'advanced'
        ]);

        $category = VolunteeringCategory::factory()->create();
        $this->profileService->addVolunteeringInterest($user, $category->id);

        $user->refresh();
        $this->assertGreaterThan(50, $user->profile_completion_percentage);
    }
}