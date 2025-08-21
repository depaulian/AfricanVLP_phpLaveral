<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRegistrationStep;
use App\Models\City;
use App\Models\Country;
use App\Models\VolunteeringCategory;
use App\Services\RegistrationService;
use App\Notifications\WelcomeEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->country = Country::factory()->create(['name' => 'Test Country']);
        $this->city = City::factory()->create([
            'name' => 'Test City',
            'country_id' => $this->country->id
        ]);
        $this->category = VolunteeringCategory::factory()->create(['name' => 'Education']);
    }

    public function test_user_can_access_registration_index()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('registration.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('client.registration.index');
        $response->assertViewHas(['progress', 'nextStep']);
    }

    public function test_registration_is_initialized_for_new_user()
    {
        $user = User::factory()->create();
        $registrationService = app(RegistrationService::class);
        
        $this->actingAs($user)->get(route('registration.index'));
        
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => false
        ]);
        
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'verification',
            'is_completed' => false
        ]);
    }

    public function test_user_can_complete_basic_info_step()
    {
        $user = User::factory()->create();
        
        $stepData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'bio' => 'I love volunteering',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone_number' => '+1234567890'
        ];
        
        $response = $this->actingAs($user)
            ->post(route('registration.process-step', 'basic_info'), $stepData);
        
        $response->assertRedirect(route('registration.step', 'profile_details'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => true
        ]);
        
        $user->refresh();
        $this->assertEquals('John', $user->first_name);
        $this->assertEquals('Doe', $user->last_name);
    }

    public function test_user_can_complete_profile_details_step()
    {
        $user = User::factory()->create();
        
        // Complete basic info first
        UserRegistrationStep::create([
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => true,
            'completed_at' => now()
        ]);
        
        $stepData = [
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'address' => '123 Test Street'
        ];
        
        $response = $this->actingAs($user)
            ->post(route('registration.process-step', 'profile_details'), $stepData);
        
        $response->assertRedirect(route('registration.step', 'interests'));
        
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'profile_details',
            'is_completed' => true
        ]);
    }

    public function test_user_can_complete_interests_step()
    {
        $user = User::factory()->create();
        
        // Complete previous steps
        foreach (['basic_info', 'profile_details'] as $step) {
            UserRegistrationStep::create([
                'user_id' => $user->id,
                'step_name' => $step,
                'is_completed' => true,
                'completed_at' => now()
            ]);
        }
        
        $stepData = [
            'volunteering_interests' => [$this->category->id],
            'interest_levels' => [$this->category->id => 'high'],
            'skills' => [
                [
                    'name' => 'Teaching',
                    'proficiency' => 'intermediate',
                    'years_experience' => 3
                ]
            ]
        ];
        
        $response = $this->actingAs($user)
            ->post(route('registration.process-step', 'interests'), $stepData);
        
        $response->assertRedirect(route('registration.step', 'verification'));
        
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'interests',
            'is_completed' => true
        ]);
        
        $this->assertDatabaseHas('user_volunteering_interests', [
            'user_id' => $user->id,
            'category_id' => $this->category->id,
            'interest_level' => 'high'
        ]);
        
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_name' => 'Teaching',
            'proficiency_level' => 'intermediate',
            'years_experience' => 3
        ]);
    }

    public function test_user_can_skip_interests_step()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post(route('registration.skip-step', 'interests'));
        
        $response->assertRedirect();
        $response->assertSessionHas('info');
        
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'interests',
            'is_completed' => true
        ]);
    }

    public function test_registration_completion_sends_welcome_email()
    {
        Notification::fake();
        
        $user = User::factory()->create();
        
        // Complete all steps except verification
        foreach (['basic_info', 'profile_details', 'interests'] as $step) {
            UserRegistrationStep::create([
                'user_id' => $user->id,
                'step_name' => $step,
                'is_completed' => true,
                'completed_at' => now()
            ]);
        }
        
        $stepData = [
            'email_verified' => true,
            'terms_accepted' => true
        ];
        
        $response = $this->actingAs($user)
            ->post(route('registration.process-step', 'verification'), $stepData);
        
        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');
        
        // Check registration completion
        $user->refresh();
        $this->assertNotNull($user->registration_completed_at);
        $this->assertTrue($user->onboarding_completed);
        
        // Check welcome email was sent
        Notification::assertSentTo($user, WelcomeEmail::class);
    }

    public function test_registration_progress_is_calculated_correctly()
    {
        $user = User::factory()->create();
        $registrationService = app(RegistrationService::class);
        
        // Initialize registration
        $registrationService->initializeRegistration($user);
        
        $progress = $registrationService->getRegistrationProgress($user);
        
        $this->assertEquals(0, $progress['overall_percentage']);
        $this->assertEquals(0, $progress['completed_steps']);
        $this->assertEquals(4, $progress['total_steps']);
        $this->assertFalse($progress['is_complete']);
        $this->assertEquals('basic_info', $progress['next_step']);
        
        // Complete one step
        $registrationService->completeStep($user, 'basic_info', ['test' => 'data']);
        
        $progress = $registrationService->getRegistrationProgress($user);
        $this->assertEquals(25, $progress['overall_percentage']);
        $this->assertEquals(1, $progress['completed_steps']);
        $this->assertEquals('profile_details', $progress['next_step']);
    }

    public function test_auto_save_functionality()
    {
        $user = User::factory()->create();
        
        $stepData = [
            'step_name' => 'basic_info',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ];
        
        $response = $this->actingAs($user)
            ->postJson(route('registration.auto-save'), $stepData);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('user_registration_steps', [
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => false
        ]);
    }

    public function test_registration_analytics_for_admin()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $registrationService = app(RegistrationService::class);
        
        // Create some test data
        $user1 = User::factory()->create(['created_at' => now()->subDays(5)]);
        $user2 = User::factory()->create(['created_at' => now()->subDays(3)]);
        
        $registrationService->initializeRegistration($user1);
        $registrationService->initializeRegistration($user2);
        
        // Complete registration for user1
        foreach (['basic_info', 'profile_details', 'interests', 'verification'] as $step) {
            $registrationService->completeStep($user1, $step, ['test' => 'data']);
        }
        
        $response = $this->actingAs($admin)
            ->getJson(route('admin.registration.analytics'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'analytics' => [
                'total_registrations',
                'completed_registrations',
                'conversion_rate',
                'step_completion_rates'
            ],
            'funnel'
        ]);
    }

    public function test_non_admin_cannot_access_analytics()
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)
            ->getJson(route('admin.registration.analytics'));
        
        $response->assertStatus(403);
    }

    public function test_completed_registration_redirects_to_profile()
    {
        $user = User::factory()->create([
            'registration_completed_at' => now(),
            'onboarding_completed' => true
        ]);
        
        $response = $this->actingAs($user)->get(route('registration.index'));
        
        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('info');
    }

    public function test_registration_step_validation()
    {
        $user = User::factory()->create();
        
        // Try to submit basic_info without required fields
        $response = $this->actingAs($user)
            ->post(route('registration.process-step', 'basic_info'), []);
        
        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_invalid_step_name_returns_404()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('registration.step', 'invalid_step'));
        
        $response->assertStatus(404);
    }
}