<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\ForumReputationHistory;
use App\Models\ForumPost;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumReputationHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_reputation_history()
    {
        $user = User::factory()->create();
        
        $history = ForumReputationHistory::create([
            'user_id' => $user->id,
            'action' => 'post_created',
            'points_change' => 5,
            'points_before' => 0,
            'points_after' => 5,
            'source_type' => 'forum_post',
            'source_id' => 1,
            'description' => 'Created a forum post',
            'metadata' => ['post_title' => 'Test Post'],
        ]);

        $this->assertInstanceOf(ForumReputationHistory::class, $history);
        $this->assertEquals($user->id, $history->user_id);
        $this->assertEquals('post_created', $history->action);
        $this->assertEquals(5, $history->points_change);
        $this->assertEquals(['post_title' => 'Test Post'], $history->metadata);
    }

    public function test_belongs_to_user()
    {
        $user = User::factory()->create();
        $history = ForumReputationHistory::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $history->user);
        $this->assertEquals($user->id, $history->user->id);
    }

    public function test_metadata_is_cast_to_array()
    {
        $user = User::factory()->create();
        $metadata = ['source' => 'test', 'value' => 123];
        
        $history = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($history->metadata);
        $this->assertEquals($metadata, $history->metadata);
    }

    public function test_scope_positive()
    {
        $user = User::factory()->create();
        
        $positiveHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => 10,
        ]);
        
        $negativeHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => -5,
        ]);

        $positiveRecords = ForumReputationHistory::positive()->get();
        
        $this->assertCount(1, $positiveRecords);
        $this->assertEquals($positiveHistory->id, $positiveRecords->first()->id);
    }

    public function test_scope_negative()
    {
        $user = User::factory()->create();
        
        $positiveHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => 10,
        ]);
        
        $negativeHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => -5,
        ]);

        $negativeRecords = ForumReputationHistory::negative()->get();
        
        $this->assertCount(1, $negativeRecords);
        $this->assertEquals($negativeHistory->id, $negativeRecords->first()->id);
    }

    public function test_scope_action()
    {
        $user = User::factory()->create();
        
        $postHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'post_created',
        ]);
        
        $voteHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'vote_received',
        ]);

        $postRecords = ForumReputationHistory::action('post_created')->get();
        
        $this->assertCount(1, $postRecords);
        $this->assertEquals($postHistory->id, $postRecords->first()->id);
    }

    public function test_scope_recent()
    {
        $user = User::factory()->create();
        
        $recentHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);
        
        $oldHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(40),
        ]);

        $recentRecords = ForumReputationHistory::recent(30)->get();
        
        $this->assertCount(1, $recentRecords);
        $this->assertEquals($recentHistory->id, $recentRecords->first()->id);
    }

    public function test_get_for_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        ForumReputationHistory::factory()->count(3)->create(['user_id' => $user1->id]);
        ForumReputationHistory::factory()->count(2)->create(['user_id' => $user2->id]);

        $user1History = ForumReputationHistory::getForUser($user1->id, 10);
        
        $this->assertCount(3, $user1History);
        foreach ($user1History as $history) {
            $this->assertEquals($user1->id, $history->user_id);
        }
    }

    public function test_get_for_user_limits_results()
    {
        $user = User::factory()->create();
        ForumReputationHistory::factory()->count(10)->create(['user_id' => $user->id]);

        $history = ForumReputationHistory::getForUser($user->id, 5);
        
        $this->assertCount(5, $history);
    }

    public function test_get_for_user_orders_by_created_at_desc()
    {
        $user = User::factory()->create();
        
        $firstHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $secondHistory = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(1), // Most recent
        ]);

        $history = ForumReputationHistory::getForUser($user->id, 10);
        
        $this->assertEquals($secondHistory->id, $history[0]->id);
        $this->assertEquals($firstHistory->id, $history[1]->id);
    }

    public function test_get_recent_gains()
    {
        $user = User::factory()->create();
        
        // Recent gains
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => 10,
            'created_at' => now()->subDays(3),
        ]);
        
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => 5,
            'created_at' => now()->subDays(1),
        ]);
        
        // Recent loss (should not be included)
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => -2,
            'created_at' => now()->subDays(2),
        ]);
        
        // Old gain (should not be included)
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => 20,
            'created_at' => now()->subDays(10),
        ]);

        $recentGains = ForumReputationHistory::getRecentGains($user->id, 7);
        
        $this->assertEquals(15, $recentGains); // 10 + 5
    }

    public function test_get_action_description()
    {
        $user = User::factory()->create();
        $history = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'post_created',
        ]);

        $description = $history->getActionDescription();
        
        $this->assertEquals('Created a post', $description);
    }

    public function test_get_action_description_unknown_action()
    {
        $user = User::factory()->create();
        $history = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'unknown_action',
        ]);

        $description = $history->getActionDescription();
        
        $this->assertEquals('unknown_action', $description);
    }

    public function test_get_formatted_points_change_positive()
    {
        $user = User::factory()->create();
        $history = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => 10,
        ]);

        $formatted = $history->getFormattedPointsChange();
        
        $this->assertEquals('+10', $formatted);
    }

    public function test_get_formatted_points_change_negative()
    {
        $user = User::factory()->create();
        $history = ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'points_change' => -5,
        ]);

        $formatted = $history->getFormattedPointsChange();
        
        $this->assertEquals('-5', $formatted);
    }

    public function test_get_user_summary()
    {
        $user = User::factory()->create();
        
        // Create various history records
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'post_created',
            'points_change' => 5,
            'created_at' => now()->subDays(5),
        ]);
        
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'vote_received',
            'points_change' => 2,
            'created_at' => now()->subDays(3),
        ]);
        
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'post_created',
            'points_change' => 5,
            'created_at' => now()->subDays(1),
        ]);
        
        ForumReputationHistory::factory()->create([
            'user_id' => $user->id,
            'action' => 'manual_adjustment',
            'points_change' => -3,
            'created_at' => now()->subDays(2),
        ]);

        $summary = ForumReputationHistory::getUserSummary($user->id, 30);
        
        $this->assertEquals(12, $summary['total_gains']); // 5 + 2 + 5
        $this->assertEquals(3, $summary['total_losses']); // 3
        $this->assertEquals(9, $summary['net_change']); // 12 - 3
        $this->assertEquals(4, $summary['total_activities']);
        $this->assertEquals(0.3, $summary['average_per_day']); // 9 / 30
        
        $this->assertArrayHasKey('post_created', $summary['action_breakdown']);
        $this->assertEquals(2, $summary['action_breakdown']['post_created']);
        $this->assertEquals(1, $summary['action_breakdown']['vote_received']);
    }

    public function test_get_user_summary_empty_history()
    {
        $user = User::factory()->create();

        $summary = ForumReputationHistory::getUserSummary($user->id, 30);
        
        $this->assertEquals(0, $summary['total_gains']);
        $this->assertEquals(0, $summary['total_losses']);
        $this->assertEquals(0, $summary['net_change']);
        $this->assertEquals(0, $summary['total_activities']);
        $this->assertEquals(0, $summary['average_per_day']);
        $this->assertEmpty($summary['action_breakdown']);
    }
}