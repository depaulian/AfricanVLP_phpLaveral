<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProfileActivityLog;
use App\Services\ProfileAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class ProfileAnalyticsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ProfileAnalyticsService $analyticsService;
    private User $adminUser;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = app(ProfileAnalyticsService::class);
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => 'admin'
        ]);
        
        // Create test user with profile
        $this->testUser = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->testUser->id]);
    }

    /** @test */
    public function it_can_get_user_engagement_analytics()
    {
        // Create some test data
        $this->createTestActivityLogs();
        
        $analytics = $this->analyticsService->getUserEngagementAnalytics();
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('period', $analytics);
        $this->assertArrayHasKey('user_metrics', $analytics);
        $this->assertArrayHasKey('profile_metrics', $analytics);
        $this->assertArrayHasKey('registration_metrics', $analytics);
        
        $this->assertArrayHasKey('total_users', $analytics['user_metrics']);
        $this->assertArrayHasKey('active_users', $analytics['user_metrics']);
        $this->assertArrayHasKey('engagement_rate', $analytics['user_metrics']);
    }

    /** @test */
    public function it_can_get_profile_completion_insights()
    {
        $insights = $this->analyticsService->getProfileCompletionInsights();
        
        $this->assertIsArray($insights);
        $this->assertArrayHasKey('total_users', $insights);
        $this->assertArrayHasKey('field_completion_rates', $insights);
        $this->assertArrayHasKey('completeness_distribution', $insights);
        $this->assertArrayHasKey('most_completed_fields', $insights);
        $this->assertArrayHasKey('least_completed_fields', $insights);
        $this->assertArrayHasKey('average_completion_percentage', $insights);
    }

    /** @test */
    public function it_can_get_user_behavior_analytics()
    {
        $this->createTestActivityLogs();
        
        $analytics = $this->analyticsService->getUserBehaviorAnalytics();
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('period', $analytics);
        $this->assertArrayHasKey('activity_patterns', $analytics);
        $this->assertArrayHasKey('common_activities', $analytics);
        $this->assertArrayHasKey('session_analytics', $analytics);
        $this->assertArrayHasKey('update_patterns', $analytics);
        
        $this->assertArrayHasKey('hourly', $analytics['activity_patterns']);
        $this->assertArrayHasKey('daily', $analytics['activity_patterns']);
    }

    /** @test */
    public function it_can_get_demographic_analytics()
    {
        $analytics = $this->analyticsService->getDemographicAnalytics();
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('age_distribution', $analytics);
        $this->assertArrayHasKey('gender_distribution', $analytics);
        $this->assertArrayHasKey('geographic_distribution', $analytics);
        $this->assertArrayHasKey('interest_distribution', $analytics);
        $this->assertArrayHasKey('skills_distribution', $analytics);
    }

    /** @test */
    public function it_can_get_profile_performance_metrics()
    {
        $this->createTestActivityLogs();
        
        $metrics = $this->analyticsService->getProfilePerformanceMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('view_metrics', $metrics);
        $this->assertArrayHasKey('most_viewed_profiles', $metrics);
        $this->assertArrayHasKey('correlations', $metrics);
        $this->assertArrayHasKey('completion_metrics', $metrics);
    }

    /** @test */
    public function it_can_get_admin_dashboard_data()
    {
        $this->createTestActivityLogs();
        
        $dashboardData = $this->analyticsService->getAdminDashboardData();
        
        $this->assertIsArray($dashboardData);
        $this->assertArrayHasKey('key_metrics', $dashboardData);
        $this->assertArrayHasKey('growth_trends', $dashboardData);
        $this->assertArrayHasKey('recent_activity', $dashboardData);
        $this->assertArrayHasKey('quick_stats', $dashboardData);
        
        $this->assertArrayHasKey('total_users', $dashboardData['key_metrics']);
        $this->assertArrayHasKey('profile_completion_rate', $dashboardData['quick_stats']);
    }

    /** @test */
    public function admin_can_access_analytics_dashboard()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get(route('admin.profile-analytics.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.profile-analytics.index');
        $response->assertViewHas('dashboardData');
    }

    /** @test */
    public function non_admin_cannot_access_analytics_dashboard()
    {
        $this->actingAs($this->testUser);
        
        $response = $this->get(route('admin.profile-analytics.index'));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_user_engagement_via_ajax()
    {
        $this->actingAs($this->adminUser);
        $this->createTestActivityLogs();
        
        $response = $this->getJson(route('admin.profile-analytics.user-engagement'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'period',
                'user_metrics',
                'profile_metrics',
                'registration_metrics'
            ]
        ]);
    }

    /** @test */
    public function it_can_get_profile_completion_via_ajax()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson(route('admin.profile-analytics.profile-completion'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_users',
                'field_completion_rates',
                'completeness_distribution'
            ]
        ]);
    }

    /** @test */
    public function it_can_export_analytics_data()
    {
        $this->actingAs($this->adminUser);
        $this->createTestActivityLogs();
        
        $response = $this->getJson(route('admin.profile-analytics.export', [
            'type' => 'engagement',
            'format' => 'json'
        ]));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function it_can_clear_analytics_cache()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->postJson(route('admin.profile-analytics.clear-cache'));
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Analytics cache cleared successfully'
        ]);
    }

    /** @test */
    public function it_can_get_realtime_updates()
    {
        $this->actingAs($this->adminUser);
        $this->createTestActivityLogs();
        
        $response = $this->getJson(route('admin.profile-analytics.realtime-updates'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'key_metrics',
                'recent_activity',
                'quick_stats',
                'last_updated'
            ]
        ]);
    }

    /** @test */
    public function it_validates_export_parameters()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson(route('admin.profile-analytics.export', [
            'type' => 'invalid',
            'format' => 'invalid'
        ]));
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type', 'format']);
    }

    /** @test */
    public function it_handles_date_range_filters()
    {
        $this->actingAs($this->adminUser);
        $this->createTestActivityLogs();
        
        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        
        $response = $this->getJson(route('admin.profile-analytics.user-engagement', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));
        
        $response->assertStatus(200);
        $response->assertJsonPath('data.period.start_date', $startDate);
        $response->assertJsonPath('data.period.end_date', $endDate);
    }

    /** @test */
    public function it_tracks_profile_activities()
    {
        $this->actingAs($this->testUser);
        
        // Simulate profile view
        ProfileActivityLog::logActivity(
            $this->testUser->id,
            ProfileActivityLog::ACTIVITY_PROFILE_VIEWED,
            $this->testUser->id,
            'Viewed own profile'
        );
        
        $this->assertDatabaseHas('profile_activity_logs', [
            'user_id' => $this->testUser->id,
            'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_VIEWED
        ]);
    }

    /** @test */
    public function it_calculates_profile_completeness_correctly()
    {
        // Create user with partial profile
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ]);
        
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'date_of_birth' => '1990-01-01'
        ]);
        
        $insights = $this->analyticsService->getProfileCompletionInsights();
        
        $this->assertGreaterThan(0, $insights['average_completion_percentage']);
    }

    private function createTestActivityLogs(): void
    {
        // Create various activity logs for testing
        ProfileActivityLog::factory()->count(10)->create([
            'user_id' => $this->testUser->id,
            'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_VIEWED,
            'created_at' => now()->subDays(rand(1, 30))
        ]);
        
        ProfileActivityLog::factory()->count(5)->create([
            'user_id' => $this->testUser->id,
            'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_UPDATED,
            'created_at' => now()->subDays(rand(1, 30))
        ]);
        
        ProfileActivityLog::factory()->count(3)->create([
            'user_id' => $this->testUser->id,
            'activity_type' => ProfileActivityLog::ACTIVITY_SKILL_ADDED,
            'created_at' => now()->subDays(rand(1, 30))
        ]);
    }
}