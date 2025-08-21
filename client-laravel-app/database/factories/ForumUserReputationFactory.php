<?php

namespace Database\Factories;

use App\Models\ForumUserReputation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumUserReputationFactory extends Factory
{
    protected $model = ForumUserReputation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total_points' => $this->faker->numberBetween(0, 1000),
            'post_points' => $this->faker->numberBetween(0, 200),
            'vote_points' => $this->faker->numberBetween(0, 100),
            'solution_points' => $this->faker->numberBetween(0, 300),
            'badge_points' => $this->faker->numberBetween(0, 150),
            'rank' => $this->faker->randomElement(['Newcomer', 'Contributor', 'Regular', 'Veteran', 'Expert', 'Master', 'Legend']),
            'rank_level' => $this->faker->numberBetween(1, 7),
            'posts_count' => $this->faker->numberBetween(0, 50),
            'threads_count' => $this->faker->numberBetween(0, 20),
            'votes_received' => $this->faker->numberBetween(0, 100),
            'solutions_provided' => $this->faker->numberBetween(0, 10),
            'consecutive_days_active' => $this->faker->numberBetween(0, 30),
            'last_activity_date' => $this->faker->optional()->date(),
        ];
    }

    public function newcomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => $this->faker->numberBetween(0, 99),
            'rank' => 'Newcomer',
            'rank_level' => 1,
        ]);
    }

    public function contributor(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => $this->faker->numberBetween(100, 499),
            'rank' => 'Contributor',
            'rank_level' => 2,
        ]);
    }

    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => $this->faker->numberBetween(3000, 5999),
            'rank' => 'Expert',
            'rank_level' => 5,
        ]);
    }

    public function legend(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => $this->faker->numberBetween(12000, 20000),
            'rank' => 'Legend',
            'rank_level' => 7,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'consecutive_days_active' => $this->faker->numberBetween(7, 30),
            'last_activity_date' => now()->subDays($this->faker->numberBetween(0, 2)),
        ]);
    }

    public function withActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'posts_count' => $this->faker->numberBetween(10, 50),
            'threads_count' => $this->faker->numberBetween(5, 20),
            'votes_received' => $this->faker->numberBetween(20, 100),
            'solutions_provided' => $this->faker->numberBetween(2, 10),
        ]);
    }
}