<?php

namespace Database\Factories;

use App\Models\ForumUserBadge;
use App\Models\User;
use App\Models\ForumBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumUserBadgeFactory extends Factory
{
    protected $model = ForumUserBadge::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'forum_badge_id' => ForumBadge::factory(),
            'earned_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'earning_context' => [
                'source' => $this->faker->randomElement(['post_created', 'vote_received', 'solution_marked']),
                'source_id' => $this->faker->numberBetween(1, 100),
            ],
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'earned_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'earned_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
        ]);
    }

    public function withContext(): static
    {
        return $this->state(fn (array $attributes) => [
            'earning_context' => [
                'source' => 'forum_post',
                'source_id' => $this->faker->numberBetween(1, 1000),
                'description' => $this->faker->sentence(),
                'metadata' => [
                    'thread_title' => $this->faker->sentence(),
                    'forum_name' => $this->faker->words(2, true),
                ],
            ],
        ]);
    }

    public function fromPostCreation(): static
    {
        return $this->state(fn (array $attributes) => [
            'earning_context' => [
                'source' => 'post_created',
                'source_id' => $this->faker->numberBetween(1, 1000),
                'description' => 'Earned by creating a forum post',
            ],
        ]);
    }

    public function fromVoteReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'earning_context' => [
                'source' => 'vote_received',
                'source_id' => $this->faker->numberBetween(1, 1000),
                'description' => 'Earned by receiving upvotes',
                'metadata' => [
                    'votes_count' => $this->faker->numberBetween(10, 50),
                ],
            ],
        ]);
    }

    public function fromSolutionMarked(): static
    {
        return $this->state(fn (array $attributes) => [
            'earning_context' => [
                'source' => 'solution_marked',
                'source_id' => $this->faker->numberBetween(1, 1000),
                'description' => 'Earned by providing accepted solutions',
                'metadata' => [
                    'solutions_count' => $this->faker->numberBetween(1, 10),
                ],
            ],
        ]);
    }
}