<?php

namespace Database\Factories;

use App\Models\ProfileAchievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileAchievement>
 */
class ProfileAchievementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(ProfileAchievement::TYPES);
        $type = $this->faker->randomElement($types);
        
        $achievements = [
            'profile_completion' => [
                ['name' => 'Profile Builder', 'description' => 'Completed 50% of profile information', 'icon' => 'fas fa-hammer', 'color' => 'bronze', 'points' => 25],
                ['name' => 'Profile Expert', 'description' => 'Completed 80% of profile information', 'icon' => 'fas fa-medal', 'color' => 'silver', 'points' => 50],
                ['name' => 'Profile Master', 'description' => 'Completed 100% of profile information', 'icon' => 'fas fa-star', 'color' => 'gold', 'points' => 100],
            ],
            'skill_verification' => [
                ['name' => 'First Skill Verified', 'description' => 'Verified your first skill', 'icon' => 'fas fa-check-circle', 'color' => 'green', 'points' => 15],
                ['name' => 'Skill Expert', 'description' => 'Verified 5+ skills', 'icon' => 'fas fa-award', 'color' => 'blue', 'points' => 40],
                ['name' => 'Skill Master', 'description' => 'Verified 10+ skills', 'icon' => 'fas fa-certificate', 'color' => 'purple', 'points' => 75],
            ],
            'document_upload' => [
                ['name' => 'First Document', 'description' => 'Uploaded your first document', 'icon' => 'fas fa-file-upload', 'color' => 'teal', 'points' => 10],
                ['name' => 'Document Collector', 'description' => 'Uploaded 5+ documents', 'icon' => 'fas fa-folder', 'color' => 'orange', 'points' => 30],
            ],
            'volunteering_history' => [
                ['name' => 'First Volunteer Experience', 'description' => 'Added your first volunteer experience', 'icon' => 'fas fa-hands-helping', 'color' => 'green', 'points' => 20],
                ['name' => 'Volunteer Hero', 'description' => 'Contributed 100+ volunteer hours', 'icon' => 'fas fa-heart', 'color' => 'red', 'points' => 75],
                ['name' => 'Volunteer Champion', 'description' => 'Contributed 500+ volunteer hours', 'icon' => 'fas fa-trophy', 'color' => 'gold', 'points' => 200],
            ],
            'social_connection' => [
                ['name' => 'Social Connector', 'description' => 'Connected with other volunteers', 'icon' => 'fas fa-users', 'color' => 'blue', 'points' => 25],
                ['name' => 'Community Builder', 'description' => 'Active in community discussions', 'icon' => 'fas fa-comments', 'color' => 'purple', 'points' => 50],
            ],
            'platform_engagement' => [
                ['name' => 'Platform Pro', 'description' => 'Achieved 75+ overall profile score', 'icon' => 'fas fa-gem', 'color' => 'blue', 'points' => 75],
                ['name' => 'Platform Elite', 'description' => 'Achieved 90+ overall profile score', 'icon' => 'fas fa-crown', 'color' => 'gold', 'points' => 150],
            ],
        ];

        $typeAchievements = $achievements[$type] ?? $achievements['profile_completion'];
        $achievement = $this->faker->randomElement($typeAchievements);

        return [
            'user_id' => User::factory(),
            'achievement_type' => $type,
            'achievement_name' => $achievement['name'],
            'achievement_description' => $achievement['description'],
            'badge_icon' => $achievement['icon'],
            'badge_color' => $achievement['color'],
            'points_awarded' => $achievement['points'],
            'earned_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
        ];
    }

    /**
     * Indicate that the achievement is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the achievement was earned recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'earned_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create achievement of specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'achievement_type' => $type,
        ]);
    }
}