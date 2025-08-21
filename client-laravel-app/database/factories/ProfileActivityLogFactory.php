<?php

namespace Database\Factories;

use App\Models\ProfileActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileActivityLog>
 */
class ProfileActivityLogFactory extends Factory
{
    protected $model = ProfileActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activityTypes = [
            ProfileActivityLog::ACTIVITY_PROFILE_VIEWED,
            ProfileActivityLog::ACTIVITY_PROFILE_UPDATED,
            ProfileActivityLog::ACTIVITY_PROFILE_CREATED,
            ProfileActivityLog::ACTIVITY_DOCUMENT_UPLOADED,
            ProfileActivityLog::ACTIVITY_SKILL_ADDED,
            ProfileActivityLog::ACTIVITY_SKILL_REMOVED,
            ProfileActivityLog::ACTIVITY_INTEREST_ADDED,
            ProfileActivityLog::ACTIVITY_INTEREST_REMOVED,
            ProfileActivityLog::ACTIVITY_PRIVACY_UPDATED,
            ProfileActivityLog::ACTIVITY_PROFILE_PHOTO_UPDATED,
            ProfileActivityLog::ACTIVITY_CONTACT_INFO_UPDATED,
            ProfileActivityLog::ACTIVITY_LOCATION_UPDATED,
            ProfileActivityLog::ACTIVITY_SOCIAL_LINKS_UPDATED,
            ProfileActivityLog::ACTIVITY_VOLUNTEERING_HISTORY_ADDED,
            ProfileActivityLog::ACTIVITY_ALUMNI_ORGANIZATION_ADDED,
        ];

        $activityType = $this->faker->randomElement($activityTypes);
        
        return [
            'user_id' => User::factory(),
            'target_user_id' => $this->faker->boolean(30) ? User::factory() : null,
            'activity_type' => $activityType,
            'description' => $this->getDescriptionForActivity($activityType),
            'metadata' => $this->getMetadataForActivity($activityType),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Generate description based on activity type
     */
    private function getDescriptionForActivity(string $activityType): string
    {
        return match ($activityType) {
            ProfileActivityLog::ACTIVITY_PROFILE_VIEWED => 'Viewed profile',
            ProfileActivityLog::ACTIVITY_PROFILE_UPDATED => 'Updated profile information',
            ProfileActivityLog::ACTIVITY_PROFILE_CREATED => 'Created profile',
            ProfileActivityLog::ACTIVITY_DOCUMENT_UPLOADED => 'Uploaded document',
            ProfileActivityLog::ACTIVITY_SKILL_ADDED => 'Added new skill',
            ProfileActivityLog::ACTIVITY_SKILL_REMOVED => 'Removed skill',
            ProfileActivityLog::ACTIVITY_INTEREST_ADDED => 'Added new interest',
            ProfileActivityLog::ACTIVITY_INTEREST_REMOVED => 'Removed interest',
            ProfileActivityLog::ACTIVITY_PRIVACY_UPDATED => 'Updated privacy settings',
            ProfileActivityLog::ACTIVITY_PROFILE_PHOTO_UPDATED => 'Updated profile photo',
            ProfileActivityLog::ACTIVITY_CONTACT_INFO_UPDATED => 'Updated contact information',
            ProfileActivityLog::ACTIVITY_LOCATION_UPDATED => 'Updated location information',
            ProfileActivityLog::ACTIVITY_SOCIAL_LINKS_UPDATED => 'Updated social media links',
            ProfileActivityLog::ACTIVITY_VOLUNTEERING_HISTORY_ADDED => 'Added volunteering history',
            ProfileActivityLog::ACTIVITY_ALUMNI_ORGANIZATION_ADDED => 'Added alumni organization',
            default => 'Performed activity'
        };
    }

    /**
     * Generate metadata based on activity type
     */
    private function getMetadataForActivity(string $activityType): array
    {
        return match ($activityType) {
            ProfileActivityLog::ACTIVITY_SKILL_ADDED => [
                'skill_name' => $this->faker->randomElement([
                    'PHP', 'Laravel', 'JavaScript', 'Python', 'Java', 'React', 'Vue.js', 'Node.js'
                ])
            ],
            ProfileActivityLog::ACTIVITY_DOCUMENT_UPLOADED => [
                'document_type' => $this->faker->randomElement(['resume', 'certificate', 'portfolio']),
                'document_name' => $this->faker->word() . '.pdf',
                'file_size' => $this->faker->numberBetween(100000, 5000000)
            ],
            ProfileActivityLog::ACTIVITY_PRIVACY_UPDATED => [
                'privacy_settings' => [
                    'profile_visibility' => $this->faker->randomElement(['public', 'private', 'friends']),
                    'contact_visibility' => $this->faker->randomElement(['public', 'private', 'friends']),
                    'location_visibility' => $this->faker->randomElement(['public', 'private', 'friends'])
                ]
            ],
            ProfileActivityLog::ACTIVITY_PROFILE_UPDATED => [
                'updated_fields' => $this->faker->randomElements([
                    'bio', 'phone_number', 'date_of_birth', 'gender', 'linkedin_url', 'website_url'
                ], $this->faker->numberBetween(1, 3))
            ],
            ProfileActivityLog::ACTIVITY_LOCATION_UPDATED => [
                'country' => $this->faker->country(),
                'city' => $this->faker->city(),
                'address' => $this->faker->address()
            ],
            ProfileActivityLog::ACTIVITY_SOCIAL_LINKS_UPDATED => [
                'platforms' => $this->faker->randomElements([
                    'linkedin', 'twitter', 'facebook', 'instagram', 'github'
                ], $this->faker->numberBetween(1, 2))
            ],
            default => [
                'route' => $this->faker->randomElement([
                    'profile.show', 'profile.edit', 'profile.update'
                ]),
                'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'PATCH']),
                'timestamp' => now()->toISOString()
            ]
        };
    }

    /**
     * Create activity for profile view
     */
    public function profileView(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_VIEWED,
            'target_user_id' => User::factory(),
            'description' => 'Viewed profile',
            'metadata' => [
                'route' => 'profile.show',
                'method' => 'GET',
                'referrer' => $this->faker->url()
            ]
        ]);
    }

    /**
     * Create activity for profile update
     */
    public function profileUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_UPDATED,
            'description' => 'Updated profile information',
            'metadata' => [
                'updated_fields' => $this->faker->randomElements([
                    'bio', 'phone_number', 'date_of_birth', 'gender'
                ], $this->faker->numberBetween(1, 2)),
                'route' => 'profile.update',
                'method' => 'PUT'
            ]
        ]);
    }

    /**
     * Create activity for skill addition
     */
    public function skillAdded(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => ProfileActivityLog::ACTIVITY_SKILL_ADDED,
            'description' => 'Added new skill',
            'metadata' => [
                'skill_name' => $this->faker->randomElement([
                    'PHP', 'Laravel', 'JavaScript', 'Python', 'Java'
                ]),
                'skill_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
                'route' => 'profile.skills.store',
                'method' => 'POST'
            ]
        ]);
    }

    /**
     * Create activity for document upload
     */
    public function documentUploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => ProfileActivityLog::ACTIVITY_DOCUMENT_UPLOADED,
            'description' => 'Uploaded document',
            'metadata' => [
                'document_type' => $this->faker->randomElement(['resume', 'certificate', 'portfolio']),
                'document_name' => $this->faker->word() . '.pdf',
                'file_size' => $this->faker->numberBetween(100000, 5000000),
                'route' => 'profile.documents.store',
                'method' => 'POST'
            ]
        ]);
    }

    /**
     * Create activity for recent time period
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now')
        ]);
    }

    /**
     * Create activity for specific user
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id
        ]);
    }

    /**
     * Create activity targeting specific user
     */
    public function targetingUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'target_user_id' => $user->id
        ]);
    }

    /**
     * Create activity during business hours
     */
    public function duringBusinessHours(): static
    {
        return $this->state(function (array $attributes) {
            $date = $this->faker->dateTimeBetween('-30 days', 'now');
            $businessHour = $this->faker->numberBetween(9, 17);
            $date->setTime($businessHour, $this->faker->numberBetween(0, 59));
            
            return [
                'created_at' => $date
            ];
        });
    }

    /**
     * Create activity with specific IP address
     */
    public function fromIp(string $ipAddress): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ipAddress
        ]);
    }
}