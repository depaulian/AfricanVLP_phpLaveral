<?php

namespace Database\Factories;

use App\Models\ForumReputationHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumReputationHistoryFactory extends Factory
{
    protected $model = ForumReputationHistory::class;

    public function definition(): array
    {
        $pointsChange = $this->faker->numberBetween(-10, 50);
        $pointsBefore = $this->faker->numberBetween(0, 500);
        
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'post_created',
                'thread_created',
                'vote_received',
                'solution_marked',
                'badge_earned',
                'daily_activity',
                'consecutive_days',
                'manual_adjustment',
            ]),
            'points_change' => $pointsChange,
            'points_before' => $pointsBefore,
            'points_after' => $pointsBefore + $pointsChange,
            'source_type' => $this->faker->optional()->randomElement([
                'forum_post',
                'forum_thread',
                'forum_badge',
            ]),
            'source_id' => $this->faker->optional()->numberBetween(1, 1000),
            'description' => $this->faker->optional()->sentence(),
            'metadata' => $this->faker->optional()->randomElement([
                ['post_title' => $this->faker->sentence()],
                ['badge_name' => $this->faker->words(2, true)],
                ['thread_title' => $this->faker->sentence()],
            ]),
        ];
    }

    public function postCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'post_created',
            'points_change' => 5,
            'source_type' => 'forum_post',
            'source_id' => $this->faker->numberBetween(1, 1000),
            'description' => 'Created a forum post',
        ]);
    }

    public function threadCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'thread_created',
            'points_change' => 10,
            'source_type' => 'forum_thread',
            'source_id' => $this->faker->numberBetween(1, 1000),
            'description' => 'Started a forum thread',
        ]);
    }

    public function voteReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'vote_received',
            'points_change' => 2,
            'source_type' => 'forum_post',
            'source_id' => $this->faker->numberBetween(1, 1000),
            'description' => 'Received an upvote',
        ]);
    }

    public function solutionMarked(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'solution_marked',
            'points_change' => 25,
            'source_type' => 'forum_post',
            'source_id' => $this->faker->numberBetween(1, 1000),
            'description' => 'Post marked as solution',
        ]);
    }

    public function badgeEarned(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'badge_earned',
            'points_change' => $this->faker->numberBetween(10, 100),
            'source_type' => 'forum_badge',
            'source_id' => $this->faker->numberBetween(1, 50),
            'description' => 'Earned a badge: ' . $this->faker->words(2, true),
            'metadata' => [
                'badge_slug' => $this->faker->slug(),
                'badge_rarity' => $this->faker->randomElement(['common', 'uncommon', 'rare', 'epic', 'legendary']),
            ],
        ]);
    }

    public function dailyActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'daily_activity',
            'points_change' => 1,
            'description' => 'Daily forum activity',
        ]);
    }

    public function consecutiveDays(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'consecutive_days',
            'points_change' => 5,
            'description' => 'Consecutive activity bonus: ' . $this->faker->numberBetween(7, 30) . ' days',
        ]);
    }

    public function manualAdjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'manual_adjustment',
            'points_change' => $this->faker->numberBetween(-50, 50),
            'description' => 'Manual reputation adjustment by administrator',
            'metadata' => [
                'admin_id' => $this->faker->numberBetween(1, 10),
                'reason' => $this->faker->sentence(),
            ],
        ]);
    }

    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'points_change' => $this->faker->numberBetween(1, 50),
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'points_change' => $this->faker->numberBetween(-50, -1),
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
        ]);
    }

    public function withMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'post_title' => $this->faker->sentence(),
                'forum_name' => $this->faker->words(2, true),
                'thread_id' => $this->faker->numberBetween(1, 1000),
                'additional_info' => $this->faker->sentence(),
            ],
        ]);
    }
}