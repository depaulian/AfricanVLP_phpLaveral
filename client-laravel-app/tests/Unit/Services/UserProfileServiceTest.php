<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use App\Models\UserRegistrationStep;
use App\Models\VolunteeringCategory;
use App\Models\VolunteeringOpportunity;
use App\Services\UserProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserProfileService();
        Storage::fake('public');
        Storage::fake('private');
    }

    public function test_create_profile()
    {
        $user = User::factory()->create();
        $data = [
            'bio' => 'Test bio',
            'phone_number' => '+1234567890',
            'address' => '123 Test Street'
        ];

        $profile = $this->service->createProfile($user, $data);

        $this->assertInstanceOf(UserProfile::class, $profile);
        $this->assertEquals('Test bio', $profile->bio);
        $this->assertEquals('+1234567890', $profile->phone_number);
        $this->assertGreaterThan(0, $profile->profile_completion_percentage);
    }

    public function test_update_profile()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id, 'bio' => 'Old bio']);
        
        $data = ['bio' => 'Updated bio'];
        $updatedProfile = $this->service->updateProfile($profile, $data);

        $this->assertEquals('Updated bio', $updatedProfile->bio);
        $this->assertGreaterThan(0, $updatedProfile->profile_completion_percentage);
    }

    public function test_upload_profile_image()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('profile.jpg');

        $path = $this->service->uploadProfileImage($user, $file);

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        
        $user->refresh();
        $this->assertNotNull($user->profile->profile_image_url);
    }

    public function test_add_skill()
    {
        $user = User::factory()->create();
        $skillData = [
            'skill_name' => 'Laravel',
            'proficiency_level' => 'advanced',
            'years_experience' => 3
        ];

        $skill = $this->service->addSkill($user, $skillData);

        $this->assertInstanceOf(UserSkill::class, $skill);
        $this->assertEquals('Laravel', $skill->skill_name);
        $this->assertEquals('advanced', $skill->proficiency_level);
        $this->assertEquals(3, $skill->years_experience);
    }

    public function test_add_volunteering_interest()
    {
        $user = User::factory()->create();
        $category = VolunteeringCategory::factory()->create();

        $interest = $this->service->addVolunteeringInterest($user, $category->id, 'high');

        $this->assertInstanceOf(UserVolunteeringInterest::class, $interest);
        $this->assertEquals($category->id, $interest->category_id);
        $this->assertEquals('high', $interest->interest_level);
    }

    public function test_add_volunteering_history()
    {
        $user = User::factory()->create();
        $historyData = [
            'organization_name' => 'Test Organization',
            'role_title' => 'Volunteer Coordinator',
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonths(6),
            'hours_contributed' => 100,
            'description' => 'Coordinated volunteer activities'
        ];

        $history = $this->service->addVolunteeringHistory($user, $historyData);

        $this->assertInstanceOf(UserVolunteeringHistory::class, $history);
        $this->assertEquals('Test Organization', $history->organization_name);
        $this->assertEquals('Volunteer Coordinator', $history->role_title);
        $this->assertEquals(100, $history->hours_contributed);
    }

    public function test_upload_document()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        $document = $this->service->uploadDocument($user, $file, 'resume');

        $this->assertInstanceOf(UserDocument::class, $document);
        $this->assertEquals('resume', $document->document_type);
        $this->assertEquals('resume.pdf', $document->file_name);
        $this->assertEquals('application/pdf', $document->mime_type);
        $this->assertEquals('pending', $document->verification_status);
        Storage::disk('private')->assertExists($document->file_path);
    }

    public function test_add_alumni_organization()
    {
        $user = User::factory()->create();
        $alumniData = [
            'organization_name' => 'Test University',
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
            'graduation_year' => 2020,
            'status' => 'graduate'
        ];

        $alumni = $this->service->addAlumniOrganization($user, $alumniData);

        $this->assertInstanceOf(UserAlumniOrganization::class, $alumni);
        $this->assertEquals('Test University', $alumni->organization_name);
        $this->assertEquals('Bachelor of Science', $alumni->degree);
        $this->assertEquals('Computer Science', $alumni->field_of_study);
        $this->assertEquals(2020, $alumni->graduation_year);
    }

    public function test_complete_registration_step()
    {
        $user = User::factory()->create();
        $stepData = ['field1' => 'value1', 'field2' => 'value2'];

        $step = $this->service->completeRegistrationStep($user, 'basic_info', $stepData);

        $this->assertInstanceOf(UserRegistrationStep::class, $step);
        $this->assertEquals('basic_info', $step->step_name);
        $this->assertEquals($stepData, $step->step_data);
        $this->assertTrue($step->is_completed);
        $this->assertNotNull($step->completed_at);
    }

    public function test_get_registration_progress()
    {
        $user = User::factory()->create();
        
        // Complete some steps
        UserRegistrationStep::factory()->create([
            'user_id' => $user->id,
            'step_name' => 'basic_info',
            'is_completed' => true,
            'completed_at' => now()
        ]);

        UserRegistrationStep::factory()->create([
            'user_id' => $user->id,
            'step_name' => 'profile_details',
            'is_completed' => false
        ]);

        $progress = $this->service->getRegistrationProgress($user);

        $this->assertIsArray($progress);
        $this->assertTrue($progress['basic_info']['completed']);
        $this->assertFalse($progress['profile_details']['completed']);
        $this->assertFalse($progress['interests']['completed']);
        $this->assertFalse($progress['verification']['completed']);
        
        $this->assertEquals(1, $progress['overall']['completed_steps']);
        $this->assertEquals(4, $progress['overall']['total_steps']);
        $this->assertEquals(25, $progress['overall']['percentage']);
    }

    public function test_get_matching_opportunities_with_interests()
    {
        $user = User::factory()->create();
        $category = VolunteeringCategory::factory()->create();
        
        // Add user interest
        UserVolunteeringInterest::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Create matching opportunity
        $opportunity = VolunteeringOpportunity::factory()->create([
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $opportunities = $this->service->getMatchingOpportunities($user);

        $this->assertCount(1, $opportunities);
        $this->assertEquals($opportunity->id, $opportunities->first()->id);
    }

    public function test_get_matching_opportunities_with_skills()
    {
        $user = User::factory()->create();
        
        // Add user skill
        UserSkill::factory()->create([
            'user_id' => $user->id,
            'skill_name' => 'Laravel'
        ]);

        // Create matching opportunity
        $opportunity = VolunteeringOpportunity::factory()->create([
            'required_skills' => ['Laravel', 'PHP'],
            'status' => 'active'
        ]);

        $opportunities = $this->service->getMatchingOpportunities($user);

        $this->assertCount(1, $opportunities);
        $this->assertEquals($opportunity->id, $opportunities->first()->id);
    }

    public function test_get_matching_opportunities_returns_empty_without_interests_or_skills()
    {
        $user = User::factory()->create();

        $opportunities = $this->service->getMatchingOpportunities($user);

        $this->assertCount(0, $opportunities);
    }

    public function test_get_user_statistics()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'profile_completion_percentage' => 80
        ]);

        // Create related data
        UserSkill::factory()->count(3)->create(['user_id' => $user->id, 'verified' => false]);
        UserSkill::factory()->count(2)->create(['user_id' => $user->id, 'verified' => true]);
        UserVolunteeringInterest::factory()->count(4)->create(['user_id' => $user->id]);
        UserVolunteeringHistory::factory()->count(2)->create([
            'user_id' => $user->id,
            'hours_contributed' => 50
        ]);
        UserDocument::factory()->count(3)->create(['user_id' => $user->id, 'verification_status' => 'pending']);
        UserDocument::factory()->count(1)->create(['user_id' => $user->id, 'verification_status' => 'verified']);
        UserAlumniOrganization::factory()->count(2)->create(['user_id' => $user->id, 'is_verified' => false]);
        UserAlumniOrganization::factory()->count(1)->create(['user_id' => $user->id, 'is_verified' => true]);

        $statistics = $this->service->getUserStatistics($user);

        $this->assertEquals(80, $statistics['profile_completion']);
        $this->assertEquals(5, $statistics['skills_count']);
        $this->assertEquals(2, $statistics['verified_skills_count']);
        $this->assertEquals(4, $statistics['interests_count']);
        $this->assertEquals(2, $statistics['volunteering_history_count']);
        $this->assertEquals(100, $statistics['total_volunteering_hours']);
        $this->assertEquals(4, $statistics['documents_count']);
        $this->assertEquals(1, $statistics['verified_documents_count']);
        $this->assertEquals(3, $statistics['alumni_organizations_count']);
        $this->assertEquals(1, $statistics['verified_alumni_count']);
    }
}