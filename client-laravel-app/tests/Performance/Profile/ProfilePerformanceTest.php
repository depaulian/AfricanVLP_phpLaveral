<?php

namespace Tests\Performance\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use App\Models\VolunteeringCategory;
use App\Models\Organization;
use App\Services\UserProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProfilePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private UserProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = new UserProfileService();
    }

    public function test_profile_loading_performance_with_large_dataset()
    {
        // Create user with extensive profile data
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        
        // Create large amounts of related data
        UserSkill::factory()->count(50)->create(['user_id' => $user->id]);
        UserVolunteeringInterest::factory()->count(20)->create(['user_id' => $user->id]);
        UserVolunteeringHistory::factory()->count(30)->create(['user_id' => $user->id]);
        UserDocument::factory()->count(15)->create(['user_id' => $user->id]);
        UserAlumniOrganization::factory()->count(5)->create(['user_id' => $user->id]);

        // Measure query count and execution time
        DB::enableQueryLog();
        $startTime = microtime(true);

        $response = $this->actingAs($user)->get(route('profile.show'));

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $queryCount = count(DB::getQueryLog());

        $response->assertStatus(200);

        // Performance assertions
        $this->assertLessThan(500, $executionTime, 'Profile loading should take less than 500ms');
        $this->assertLessThan(20, $queryCount, 'Profile loading should use less than 20 queries');

        DB::disableQueryLog();
    }

    public function test_profile_edit_page_performance()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);
        
        // Create related data that needs to be loaded for editing
        UserSkill::factory()->count(25)->create(['user_id' => $user->id]);
        UserVolunteeringInterest::factory()->count(10)->create(['user_id' => $user->id]);
        
        // Create reference data
        VolunteeringCategory::factory()->count(50)->create();

        DB::enableQueryLog();
        $startTime = microtime(true);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        $queryCount = count(DB::getQueryLog());

        $response->assertStatus(200);

        // Performance assertions
        $this->assertLessThan(300, $executionTime, 'Profile edit page should load in less than 300ms');
        $this->assertLessThan(15, $queryCount, 'Profile edit page should use less than 15 queries');

        DB::disableQueryLog();
    }

    public function test_profile_search_performance()
    {
        // Create many users with profiles
        $users = User::factory()->count(1000)->create();
        foreach ($users as $user) {
            UserProfile::factory()->create([
                'user_id' => $user->id,
                'bio' => 'I am passionate about ' . fake()->randomElement(['education', 'healthcare', 'environment', 'community']) . ' volunteering'
            ]);
        }

        DB::enableQueryLog();
        $startTime = microtime(true);

        $response = $this->get(route('profile.search', ['q' => 'education']));

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        $queryCount = count(DB::getQueryLog());

        $response->assertStatus(200);

        // Performance assertions
        $this->assertLessThan(200, $executionTime, 'Profile search should complete in less than 200ms');
        $this->assertLessThan(5, $queryCount, 'Profile search should use less than 5 queries');

        DB::disableQueryLog();
    }

    public function test_profile_statistics_calculation_performance()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);
        
        // Create extensive data for statistics
        UserSkill::factory()->count(100)->create(['user_id' => $user->id]);
        UserVolunteeringHistory::factory()->count(50)->create([
            'user_id' => $user->id,
            'hours_contributed' => fake()->numberBetween(10, 100)
        ]);
        UserDocument::factory()->count(20)->create(['user_id' => $user->id]);

        $startTime = microtime(true);

        $statistics = $this->profileService->getUserStatistics($user);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Verify statistics are calculated correctly
        $this->assertEquals(100, $statistics['skills_count']);
        $this->assertEquals(50, $statistics['volunteering_history_count']);
        $this->assertEquals(20, $statistics['documents_count']);

        // Performance assertion
        $this->assertLessThan(100, $executionTime, 'Statistics calculation should take less than 100ms');
    }

    public function test_profile_completion_calculation_performance()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'phone_number' => '+1234567890',
            'address' => '123 Test Street'
        ]);

        // Add related data
        UserSkill::factory()->count(10)->create(['user_id' => $user->id]);
        UserVolunteeringInterest::factory()->count(5)->create(['user_id' => $user->id]);
        UserVolunteeringHistory::factory()->count(3)->create(['user_id' => $user->id]);

        $startTime = microtime(true);

        $percentage = $profile->calculateCompletionPercentage();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Verify calculation
        $this->assertGreaterThan(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);

        // Performance assertion
        $this->assertLessThan(50, $executionTime, 'Profile completion calculation should take less than 50ms');
    }

    public function test_matching_opportunities_performance()
    {
        $user = User::factory()->create();
        
        // Create user interests and skills
        $categories = VolunteeringCategory::factory()->count(10)->create();
        foreach ($categories->take(5) as $category) {
            UserVolunteeringInterest::factory()->create([
                'user_id' => $user->id,
                'category_id' => $category->id
            ]);
        }

        UserSkill::factory()->count(10)->create(['user_id' => $user->id]);

        // Create many opportunities
        foreach ($categories as $category) {
            \App\Models\VolunteeringOpportunity::factory()->count(20)->create([
                'category_id' => $category->id,
                'status' => 'active'
            ]);
        }

        $startTime = microtime(true);

        $opportunities = $this->profileService->getMatchingOpportunities($user, 20);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Verify matching works
        $this->assertGreaterThan(0, $opportunities->count());
        $this->assertLessThanOrEqual(20, $opportunities->count());

        // Performance assertion
        $this->assertLessThan(150, $executionTime, 'Opportunity matching should take less than 150ms');
    }

    public function test_profile_caching_effectiveness()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);
        UserSkill::factory()->count(20)->create(['user_id' => $user->id]);

        // Clear cache
        Cache::flush();

        // First request (should cache)
        DB::enableQueryLog();
        $startTime = microtime(true);

        $response1 = $this->actingAs($user)->get(route('profile.show'));

        $endTime = microtime(true);
        $firstRequestTime = ($endTime - $startTime) * 1000;
        $firstRequestQueries = count(DB::getQueryLog());

        DB::flushQueryLog();

        // Second request (should use cache)
        $startTime = microtime(true);

        $response2 = $this->actingAs($user)->get(route('profile.show'));

        $endTime = microtime(true);
        $secondRequestTime = ($endTime - $startTime) * 1000;
        $secondRequestQueries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Cache should improve performance
        $this->assertLessThan($firstRequestTime, $secondRequestTime, 'Cached request should be faster');
        $this->assertLessThan($firstRequestQueries, $secondRequestQueries, 'Cached request should use fewer queries');
    }

    public function test_bulk_profile_operations_performance()
    {
        $users = User::factory()->count(100)->create();
        
        $startTime = microtime(true);

        // Bulk create profiles
        $profileData = [];
        foreach ($users as $user) {
            $profileData[] = [
                'user_id' => $user->id,
                'bio' => 'Bulk created profile',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        UserProfile::insert($profileData);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Verify all profiles were created
        $this->assertEquals(100, UserProfile::count());

        // Performance assertion
        $this->assertLessThan(500, $executionTime, 'Bulk profile creation should take less than 500ms');
    }

    public function test_profile_analytics_aggregation_performance()
    {
        // Create users with various profile completion levels
        for ($i = 0; $i < 1000; $i++) {
            $user = User::factory()->create();
            UserProfile::factory()->create([
                'user_id' => $user->id,
                'profile_completion_percentage' => fake()->numberBetween(10, 100)
            ]);
        }

        $startTime = microtime(true);

        // Calculate analytics
        $analytics = [
            'total_profiles' => UserProfile::count(),
            'avg_completion' => UserProfile::avg('profile_completion_percentage'),
            'completion_distribution' => UserProfile::selectRaw('
                CASE 
                    WHEN profile_completion_percentage < 25 THEN "low"
                    WHEN profile_completion_percentage < 75 THEN "medium"
                    ELSE "high"
                END as completion_level,
                COUNT(*) as count
            ')->groupBy('completion_level')->get()
        ];

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Verify analytics
        $this->assertEquals(1000, $analytics['total_profiles']);
        $this->assertGreaterThan(0, $analytics['avg_completion']);
        $this->assertCount(3, $analytics['completion_distribution']);

        // Performance assertion
        $this->assertLessThan(200, $executionTime, 'Analytics aggregation should take less than 200ms');
    }

    public function test_concurrent_profile_updates_performance()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $startTime = microtime(true);

        // Simulate concurrent updates
        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = function() use ($profile) {
                $profile->update(['bio' => 'Updated bio ' . time()]);
                $profile->calculateCompletionPercentage();
            };
        }

        // Execute all updates
        foreach ($promises as $promise) {
            $promise();
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Verify final state
        $profile->refresh();
        $this->assertStringContains('Updated bio', $profile->bio);

        // Performance assertion
        $this->assertLessThan(300, $executionTime, 'Concurrent updates should complete in less than 300ms');
    }
}