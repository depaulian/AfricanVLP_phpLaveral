<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileScore>
 */
class ProfileScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $completionScore = $this->faker->numberBetween(0, 100);
        $qualityScore = $this->faker->numberBetween(0, 100);
        $engagementScore = $this->faker->numberBetween(0, 100);
        $verificationScore = $this->faker->numberBetween(0, 100);
        
        $totalScore = round(($completionScore + $qualityScore + $engagementScore + $verificationScore) / 4);

        return [
            'user_id' => User::factory(),
            'completion_score' => $completionScore,
            'quality_score' => $qualityScore,
            'engagement_score' => $engagementScore,
            'verification_score' => $verificationScore,
            'total_score' => $totalScore,
            'rank_position' => $this->faker->numberBetween(1, 1000),
            'last_calculated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the score is excellent (90+).
     */
    public function excellent(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_score' => $this->faker->numberBetween(85, 100),
            'quality_score' => $this->faker->numberBetween(85, 100),
            'engagement_score' => $this->faker->numberBetween(85, 100),
            'verification_score' => $this->faker->numberBetween(85, 100),
            'total_score' => $this->faker->numberBetween(90, 100),
            'rank_position' => $this->faker->numberBetween(1, 50),
        ]);
    }

    /**
     * Indicate that the score is very good (75-89).
     */
    public function veryGood(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_score' => $this->faker->numberBetween(70, 90),
            'quality_score' => $this->faker->numberBetween(70, 90),
            'engagement_score' => $this->faker->numberBetween(70, 90),
            'verification_score' => $this->faker->numberBetween(70, 90),
            'total_score' => $this->faker->numberBetween(75, 89),
            'rank_position' => $this->faker->numberBetween(51, 200),
        ]);
    }

    /**
     * Indicate that the score is good (60-74).
     */
    public function good(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_score' => $this->faker->numberBetween(50, 80),
            'quality_score' => $this->faker->numberBetween(50, 80),
            'engagement_score' => $this->faker->numberBetween(50, 80),
            'verification_score' => $this->faker->numberBetween(50, 80),
            'total_score' => $this->faker->numberBetween(60, 74),
            'rank_position' => $this->faker->numberBetween(201, 500),
        ]);
    }

    /**
     * Indicate that the score needs improvement (below 40).
     */
    public function needsImprovement(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_score' => $this->faker->numberBetween(0, 50),
            'quality_score' => $this->faker->numberBetween(0, 50),
            'engagement_score' => $this->faker->numberBetween(0, 50),
            'verification_score' => $this->faker->numberBetween(0, 50),
            'total_score' => $this->faker->numberBetween(0, 39),
            'rank_position' => $this->faker->numberBetween(800, 1000),
        ]);
    }

    /**
     * Indicate that the score was calculated recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_calculated_at' => now(),
        ]);
    }

    /**
     * Indicate that the score needs recalculation.
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_calculated_at' => $this->faker->dateTimeBetween('-1 month', '-2 days'),
        ]);
    }
}