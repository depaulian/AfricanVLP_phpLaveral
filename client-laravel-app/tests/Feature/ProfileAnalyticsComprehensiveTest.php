<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProfileActivityLog;
use App\Models\UserDocument;
use App\Models\UserSkill;
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileScoringService;
use App\Services\BehavioralAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class ProfileAnalyticsComprehensiveTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected ProfileAnalyticsService $analyticsService;
    protected ProfileScoringService $scoringService;
    protected BehavioralAnalyticsService $behavioralService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->analyticsService = app(ProfileAnalyticsService::class);
        $this->scoringService = app(ProfileScoringService::class);
        $this->behavioralService = app(BehavioralAnalyticsService::class);
    }

    /** @test */
    public function it_can_calculate_comprehensive_profile_score()
    {
        // Create a complete user profile
        $this->createCompleteUserProfile();
        
        $score = $this->scoringService->calculateComprehensiveScore($this->user);
        
        $this->assertIsArray($score);
        $this->assertArrayHasKey('total_score', $score);
        $this->assertArrayHasKey('grade', $score);
        $this->assertArrayHasKey('category_scores', $score);
        $this->assertArrayHasKey('improvement_areas', $score);
        $this->assertArrayHasKey('strengths', $score);
        $this->assertArrayHasKey('next_milestone', $score);
        
        $this->assertIsFloat($score['total_score']);
        $this->assertGreaterThanOrEqual(0, $score['total_score']);
        $this->assertLessThanOrEqual(100, $score['total_score']);
        
        $this->assertArrayHasKey('letter', $score['grade']);
        $this->assertArrayHasKey('description', $score['grade']);
        $this->assertArrayHasKey('color', $score['grade']);
    }

    /** @test */
    public function it_can_analyze_user_behavior_patterns()
    {
        // Create activity logs for behavioral analysis
        $this->createUserActivityLogs();
        
        $behavioral = $this->behavioralService->analyzeUserBehavior($this->user);
        
        $this->assertIsArray($behavioral);
        $this->assertArrayHasKey('usage_patterns', $behavioral);
        $this->assertArrayHasKey('engagement_patterns', $behavioral);
        $this->assertArrayHasKey('activity_heatmap', $behavioral);
        $this->assertArrayHasKey('session_analysis', $behavioral);
        $this->assertArrayHasKey('feature_usage', $behavioral);
        $this->assertArrayHasKey('behavioral_insights', $behavioral);
        $this->assertArrayHasKey('user_journey', $behavioral);
        $this->assertArrayHasKey('predictive_metrics', $behavioral);
        
        // Test usage patterns
        $usagePatterns = $behavioral['usage_patterns'];
        $this->assertArrayHasKey('hourly_distribution', $usagePatterns);
        $this->assertArrayHasKey('daily_distribution', $usagePatterns);
        $this->assertArrayHasKey('peak_hour', $usagePatterns);
        $this->assertArrayHasKey('peak_day', $usagePatterns);
        $this->assertArrayHasKey('most_active_period', $usagePatterns);
        
        // Test predictive metrics
        $predictive = $behavioral['predictive_metrics'];
        $this->assertArrayHasKey('churn_risk', $predictive);
        $this->assertArrayHasKey('engagement_prediction', $predictive);
        $this->assertArrayHasKey('success_likelihood', $predictive);
        $this->assertArrayHasKey('recommended_actions', $predictive);
    }

    /** @test */
    public function it_can_generate_activity_heatmap()
    {
        $this->createUserActivityLogs();
        
        $behavioral = $this->behavioralService->analyzeUserBehavior($this->user);
        $heatmap = $behavioral['activity_heatmap'];
        
        $this->assertArrayHasKey('data', $heatmap);
        $this->assertArrayHasKey('max_activity', $heatmap);
        $this->assertArrayHasKey('total_activities', $heatmap);
        $this->assertArrayHasKey('active_hours', $heatmap);
        $this->assertArrayHasKey('quiet_periods', $heatmap);
        
        // Verify heatmap structure (7 days x 24 hours)
        $this->assertCount(7, $heatmap['data']);
        foreach ($heatmap['data'] as $day => $hours) {
            $this->assertCount(24, $hours);
        }
    }

    /** @test */
    public function it_can_classify_user_types()
    {
        $this->createCompleteUserProfile();
        $this->createUserActivityLogs();
        
        $behavioral = $this->behavioralService->analyzeUserBehavior($this->user);
        $insights = $behavioral['behavioral_insights'];
        
        $userTypeInsight = collect($insights)->firstWhere('type', 'classification');
        
        $this->assertNotNull($userTypeInsight);
        $this->assertArrayHasKey('insight', $userTypeInsight);
        $this->assertArrayHasKey('description', $userTypeInsight);
        $this->assertArrayHasKey('confidence', $userTypeInsight);
        
        $validUserTypes = ['Power User', 'Active User', 'Casual User', 'New/Inactive User'];
        $this->assertContains($userTypeInsight['insight'], $validUserTypes);
    }

    /** @test */
    public function it_can_calculate_churn_risk()
    {
        $this->createUserActivityLogs();
        
        $behavioral = $this->behavioralService->analyzeUserBehavior($this->user);
        $churnRisk = $behavioral['predictive_metrics']['churn_risk'];
        
        $this->assertArrayHasKey('risk_level', $churnRisk);
        $this->assertArrayHasKey('risk_score', $churnRisk);
        $this->assertArrayHasKey('factors', $churnRisk);
        $this->assertArrayHasKey('confidence', $churnRisk);
        
        $validRiskLevels = ['low', 'medium', 'high'];
        $this->assertContains($churnRisk['risk_level'], $validRiskLevels);
        
        $this->assertIsFloat($churnRisk['risk_score']);
        $this->assertGreaterThanOrEqual(0, $churnRisk['risk_score']);
        $this->assertLessThanOrEqual(100, $churnRisk['risk_score']);
    }

    /** @test */
    public function it_can_track_user_journey_progress()
    {
        $this->createCompleteUserProfile();
        $this->createUserActivityLogs();
        
        $behavioral = $this->behavioralService->analyzeUserBehavior($this->user);
        $journey = $behavioral['user_journey'];
        
        $this->assertArrayHasKey('days_since_registration', $journey);
        $this->assertArrayHasKey('milestones', $journey);
        $this->assertArrayHasKey('current_stage', $journey);
        $this->assertArrayHasKey('next_stage', $journey);
        $this->assertArrayHasKey('journey_progress', $journey);
        $this->assertArrayHasKey('stage_recommendations', $journey);
        
        $this->assertIsFloat($journey['journey_progress']);
        $this->assertGreaterThanOrEqual(0, $journey['journey_progress']);
        $this->assertLessThanOrEqual(100, $journey['journey_progress']);
    }

    /** @test */
    public function it_can_provide_personalized_recommendations()
    {
        $this->createCompleteUserProfile();
        
        $score = $this->scoringService->calculateComprehensiveScore($this->user);
        $behavioral = $this->behavioralService->analyzeUserBehavior($this->user);
        
        $this->assertIsArray($score['improvement_areas']);
        $this->assertIsArray($behavioral['predictive_metrics']['recommended_actions']);
        
        if (!empty($score['improvement_areas'])) {
            foreach ($score['improvement_areas'] as $area) {
                $this->assertArrayHasKey('category', $area);
                $this->assertArrayHasKey('score', $area);
                $this->assertArrayHasKey('priority', $area);
                $this->assertArrayHasKey('suggestions', $area);
            }
        }
    }

    /** @test */
    public function it_can_generate_score_history()
    {
        $history = $this->scoringService->getScoreHistory($this->user, 30);
        
        $this->assertIsArray($history);
        $this->assertNotEmpty($history);
        
        foreach ($history as $entry) {
            $this->assertArrayHasKey('date', $entry);
            $this->assertArrayHasKey('score', $entry);
            $this->assertArrayHasKey('grade', $entry);
            
            $this->assertIsFloat($entry['score']);
            $this->assertGreaterThanOrEqual(0, $entry['score']);
            $this->assertLessThanOrEqual(100, $entry['score']);
        }
    }

    /** @test */
    public function it_can_access_analytics_dashboard()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('profile.analytics.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewIs('client.profile.analytics-dashboard');
        $response->assertViewHas(['analytics', 'profileScore', 'behavioralAnalytics', 'scoreHistory']);
    }

    /** @test */
    public function it_can_fetch_profile_score_via_api()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('profile.analytics.api.score'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_score',
                'grade',
                'category_scores',
                'improvement_areas',
                'strengths',
                'next_milestone'
            ]
        ]);
    }

    /** @test */
    public function it_can_fetch_behavioral_analytics_via_api()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('profile.analytics.api.behavioral'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'usage_patterns',
                'engagement_patterns',
                'activity_heatmap',
                'behavioral_insights',
                'predictive_metrics'
            ]
        ]);
    }

    /** @test */
    public function it_can_export_analytics_data()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('profile.analytics.api.export'), [
            'format' => 'json',
            'sections' => ['profile_score', 'behavioral']
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function it_requires_authentication_for_analytics_access()
    {
        $response = $this->get(route('profile.analytics.dashboard'));
        $response->assertRedirect(route('login'));
        
        $response = $this->get(route('profile.analytics.api.score'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_handles_users_with_minimal_data()
    {
        // Test with a user that has minimal profile data
        $minimalUser = User::factory()->create();
        
        $score = $this->scoringService->calculateComprehensiveScore($minimalUser);
        $behavioral = $this->behavioralService->analyzeUserBehavior($minimalUser);
        
        $this->assertIsArray($score);
        $this->assertIsArray($behavioral);
        
        // Score should be low but valid
        $this->assertGreaterThanOrEqual(0, $score['total_score']);
        $this->assertLessThan(50, $score['total_score']); // Should be low for minimal profile
        
        // Should have improvement recommendations
        $this->assertNotEmpty($score['improvement_areas']);
    }

    /**
     * Create a complete user profile for testing.
     */
    protected function createCompleteUserProfile(): void
    {
        // Create user profile
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'phone' => $this->faker->phoneNumber,
            'date_of_birth' => $this->faker->date(),
            'bio' => $this->faker->paragraph(5),
            'country' => $this->faker->country,
            'state' => $this->faker->state,
            'city' => $this->faker->city,
            'occupation' => $this->faker->jobTitle,
            'organization' => $this->faker->company,
            'experience_level' => 'intermediate',
            'languages' => json_encode(['English', 'French']),
        ]);

        // Create skills
        UserSkill::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        // Create documents
        UserDocument::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'verification_status' => 'verified',
        ]);
    }

    /**
     * Create user activity logs for behavioral analysis.
     */
    protected function createUserActivityLogs(): void
    {
        $activityTypes = [
            'login', 'profile_update', 'document_upload', 'message_sent',
            'profile_view', 'search_result', 'session', 'application_submitted'
        ];

        // Create activities over the past 90 days
        for ($i = 0; $i < 90; $i++) {
            $date = Carbon::now()->subDays($i);
            
            // Random number of activities per day (0-5)
            $activitiesCount = rand(0, 5);
            
            for ($j = 0; $j < $activitiesCount; $j++) {
                ProfileActivityLog::factory()->create([
                    'user_id' => $this->user->id,
                    'activity_type' => $activityTypes[array_rand($activityTypes)],
                    'created_at' => $date->copy()->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                    'duration_minutes' => rand(1, 120),
                ]);
            }
        }
    }
}