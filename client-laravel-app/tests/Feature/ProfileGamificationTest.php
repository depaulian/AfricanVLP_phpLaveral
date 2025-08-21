<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\ProfileAchievement;
use App\Models\ProfileScore;
use App\Services\ProfileGamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProfileGamificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ProfileGamificationService $gamificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gamificationService = app(ProfileGamificationService::class);
    }

    public function test_user_can_view_gamification_dashboard()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('profile.gamification.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.gamification.dashboard');
    }

    public function test_profile_score_calculation()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '+1234567890',
            'address' => 'Test address',
            'profile_image_url' => 'test.jpg'
        ]);

        // Add some skills
        UserSkill::factory()->count(3)->create(['user_id' => $user->id]);
        
        // Add volunteering interests
        UserVolunteeringInterest::factory()->count(2)->create(['user_id' => $user->id]);
        
        // Add volunteering history
        UserVolunteeringHistory::factory()->create(['user_id' => $user->id]);

        $profileScore = $this->gamificationService->calculateProfileScore($user);

        $this->assertInstanceOf(ProfileScore::class, $profileScore);
        $this->assertGreaterThan(0, $profileScore->total_score);
        $this->assertGreaterThan(0, $profileScore->completion_score);
        $this->assertGreaterThan(0, $profileScore->quality_score);
    }

    public function test_achievements_are_awarded_automatically()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => str_repeat('Test bio content. ', 50), // Long bio for quality score
            'date_of_birth' => '1990-01-01',
            'phone_number' => '+1234567890',
            'address' => 'Test address',
            'profile_image_url' => 'test.jpg'
        ]);

        // Add skills to trigger skill achievements
        UserSkill::factory()->count(5)->create(['user_id' => $user->id]);

        $this->gamificationService->calculateProfileScore($user);

        // Check that achievements were created
        $achievements = $user->profileAchievements;
        $this->assertGreaterThan(0, $achievements->count());

        // Check for specific achievements
        $this->assertTrue($achievements->contains('achievement_name', 'Profile Builder'));
    }

    public function test_user_can_view_achievements_page()
    {
        $user = User::factory()->create();
        
        // Create some achievements
        ProfileAchievement::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('profile.gamification.achievements'));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.gamification.achievements');
        $response->assertViewHas('achievements');
        $response->assertViewHas('achievementStats');
    }

    public function test_user_can_view_leaderboard()
    {
        // Create multiple users with scores
        $users = User::factory()->count(5)->create();
        
        foreach ($users as $index => $user) {
            ProfileScore::factory()->create([
                'user_id' => $user->id,
                'total_score' => 100 - ($index * 10), // Descending scores
                'rank_position' => $index + 1
            ]);
        }

        $response = $this->actingAs($users->first())
            ->get(route('profile.gamification.leaderboard'));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.gamification.leaderboard');
        $response->assertViewHas('leaderboard');
    }

    public function test_score_recalculation_endpoint()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('profile.gamification.recalculate-score'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // Verify score was created
        $this->assertDatabaseHas('profile_scores', [
            'user_id' => $user->id
        ]);
    }

    public function test_completion_progress_endpoint()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'profile_image_url' => 'test.jpg'
        ]);

        UserSkill::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson(route('profile.gamification.completion-progress'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'overall_percentage',
                'sections' => [
                    'basic_info',
                    'skills',
                    'interests',
                    'history',
                    'documents'
                ]
            ]
        ]);
    }

    public function test_achievement_stats_endpoint()
    {
        $user = User::factory()->create();
        
        // Create achievements
        ProfileAchievement::factory()->count(3)->create([
            'user_id' => $user->id,
            'points_awarded' => 50
        ]);
        
        ProfileAchievement::factory()->create([
            'user_id' => $user->id,
            'is_featured' => true,
            'points_awarded' => 100
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('profile.gamification.achievement-stats'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'stats' => [
                'total_achievements',
                'total_points',
                'featured_achievements',
                'recent_achievements',
                'achievements_by_type',
                'latest_achievement'
            ]
        ]);
    }

    public function test_completion_suggestions_are_generated()
    {
        $user = User::factory()->create();
        // Create minimal profile to trigger suggestions
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => null, // Missing bio should trigger suggestion
        ]);

        $suggestions = $this->gamificationService->getCompletionSuggestions($user);

        $this->assertIsArray($suggestions);
        $this->assertGreaterThan(0, count($suggestions));
        
        // Check that bio suggestion is included
        $bioSuggestion = collect($suggestions)->firstWhere('title', 'Add Your Bio');
        $this->assertNotNull($bioSuggestion);
        $this->assertEquals('high', $bioSuggestion['priority']);
    }

    public function test_leaderboard_generation()
    {
        // Create users with different scores
        $users = User::factory()->count(10)->create();
        
        foreach ($users as $index => $user) {
            ProfileScore::factory()->create([
                'user_id' => $user->id,
                'total_score' => rand(50, 100),
                'rank_position' => $index + 1
            ]);
        }

        $leaderboard = $this->gamificationService->getLeaderboard(5);

        $this->assertEquals(5, $leaderboard->count());
        
        // Verify leaderboard is sorted by score descending
        $scores = $leaderboard->pluck('score')->toArray();
        $sortedScores = collect($scores)->sortDesc()->values()->toArray();
        $this->assertEquals($sortedScores, $scores);
    }

    public function test_profile_strength_levels()
    {
        $user = User::factory()->create();
        
        // Test different score levels
        $testCases = [
            ['score' => 95, 'expected' => 'Excellent'],
            ['score' => 80, 'expected' => 'Very Good'],
            ['score' => 65, 'expected' => 'Good'],
            ['score' => 45, 'expected' => 'Fair'],
            ['score' => 25, 'expected' => 'Needs Improvement'],
        ];

        foreach ($testCases as $case) {
            $profileScore = ProfileScore::factory()->create([
                'user_id' => $user->id,
                'total_score' => $case['score']
            ]);

            $this->assertEquals($case['expected'], $profileScore->getStrengthLevel());
        }
    }
}