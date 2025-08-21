<?php

namespace Database\Factories;

use App\Models\UserSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSession>
 */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $deviceTypes = ['desktop', 'mobile', 'tablet'];
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $platforms = ['Windows', 'macOS', 'Linux', 'iOS', 'Android'];

        $createdAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $lastActivity = $this->faker->dateTimeBetween($createdAt, 'now');

        return [
            'user_id' => User::factory(),
            'session_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'device_type' => $this->faker->randomElement($deviceTypes),
            'browser' => $this->faker->randomElement($browsers),
            'platform' => $this->faker->randomElement($platforms),
            'location_data' => [
                'city' => $this->faker->city(),
                'region' => $this->faker->state(),
                'country' => $this->faker->country(),
                'lat' => $this->faker->latitude(),
                'lon' => $this->faker->longitude(),
            ],
            'is_current' => false,
            'last_activity' => $lastActivity,
            'expires_at' => $this->faker->dateTimeBetween($lastActivity, '+2 hours'),
            'created_at' => $createdAt,
            'updated_at' => $lastActivity,
        ];
    }

    /**
     * Indicate that the session is current.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
            'last_activity' => now(),
            'expires_at' => now()->addHours(2),
        ]);
    }

    /**
     * Indicate that the session is active (not expired).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours($this->faker->numberBetween(1, 24)),
            'last_activity' => now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate that the session is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'is_current' => false,
        ]);
    }

    /**
     * Create a mobile session.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'mobile',
            'browser' => $this->faker->randomElement(['Chrome Mobile', 'Safari Mobile', 'Firefox Mobile']),
            'platform' => $this->faker->randomElement(['iOS', 'Android']),
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ]);
    }

    /**
     * Create a desktop session.
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'desktop',
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'platform' => $this->faker->randomElement(['Windows', 'macOS', 'Linux']),
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
    }

    /**
     * Create a tablet session.
     */
    public function tablet(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'tablet',
            'browser' => $this->faker->randomElement(['Chrome', 'Safari', 'Firefox']),
            'platform' => $this->faker->randomElement(['iOS', 'Android']),
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ]);
    }

    /**
     * Create a session from a suspicious location.
     */
    public function suspiciousLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_data' => [
                'city' => 'Unknown City',
                'region' => 'Unknown Region',
                'country' => 'Unknown Country',
                'lat' => null,
                'lon' => null,
            ],
            'ip_address' => $this->faker->randomElement([
                '1.2.3.4',      // Unusual IP
                '8.8.8.8',      // Google DNS
                '1.1.1.1',      // Cloudflare DNS
            ]),
        ]);
    }

    /**
     * Create a long-running session.
     */
    public function longRunning(): static
    {
        $createdAt = $this->faker->dateTimeBetween('-7 days', '-1 day');
        
        return $this->state(fn (array $attributes) => [
            'created_at' => $createdAt,
            'last_activity' => now()->subMinutes($this->faker->numberBetween(5, 30)),
            'expires_at' => now()->addHours(2),
        ]);
    }
}