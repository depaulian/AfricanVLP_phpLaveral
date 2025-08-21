<?php

namespace Database\Factories;

use App\Models\ForumBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumBadgeFactory extends Factory
{
    protected $model = ForumBadge::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->randomElement(['fas fa-star', 'fas fa-trophy', 'fas fa-medal', 'fas fa-crown']),
            'color' => $this->faker->hexColor(),
            'type' => $this->faker->randomElement(['activity', 'achievement', 'milestone', 'special']),
            'rarity' => $this->faker->randomElement(['common', 'uncommon', 'rare', 'epic', 'legendary']),
            'points_value' => $this->faker->numberBetween(5, 100),
            'criteria' => [
                'posts_count' => $this->faker->numberBetween(1, 50),
            ],
            'is_active' => true,
            'awarded_count' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function activity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'activity',
            'criteria' => [
                'posts_count' => $this->faker->numberBetween(5, 20),
            ],
        ]);
    }

    public function achievement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'achievement',
            'criteria' => [
                'votes_received' => $this->faker->numberBetween(10, 50),
            ],
        ]);
    }

    public function milestone(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'milestone',
            'criteria' => [
                'rank_level' => $this->faker->numberBetween(2, 5),
            ],
        ]);
    }

    public function special(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'special',
            'rarity' => 'legendary',
            'points_value' => $this->faker->numberBetween(100, 200),
        ]);
    }

    public function common(): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity' => 'common',
            'points_value' => $this->faker->numberBetween(5, 15),
            'color' => '#9CA3AF',
        ]);
    }

    public function rare(): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity' => 'rare',
            'points_value' => $this->faker->numberBetween(25, 50),
            'color' => '#3B82F6',
        ]);
    }

    public function epic(): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity' => 'epic',
            'points_value' => $this->faker->numberBetween(50, 100),
            'color' => '#8B5CF6',
        ]);
    }

    public function legendary(): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity' => 'legendary',
            'points_value' => $this->faker->numberBetween(100, 200),
            'color' => '#F59E0B',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'awarded_count' => $this->faker->numberBetween(50, 200),
        ]);
    }

    public function firstPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'First Steps',
            'slug' => 'first-steps',
            'description' => 'Created your first forum post',
            'type' => 'milestone',
            'rarity' => 'common',
            'criteria' => ['first_post' => true],
            'points_value' => 10,
        ]);
    }

    public function contributor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Contributor',
            'slug' => 'contributor',
            'description' => 'Made 10 helpful posts',
            'type' => 'activity',
            'rarity' => 'uncommon',
            'criteria' => ['posts_count' => 10],
            'points_value' => 25,
        ]);
    }

    public function problemSolver(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Problem Solver',
            'slug' => 'problem-solver',
            'description' => 'Provided 5 accepted solutions',
            'type' => 'achievement',
            'rarity' => 'rare',
            'criteria' => ['solutions_provided' => 5],
            'points_value' => 50,
        ]);
    }
}