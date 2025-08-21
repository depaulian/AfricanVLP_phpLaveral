<?php

namespace Database\Factories;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecurityEvent>
 */
class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $eventTypes = array_keys(SecurityEvent::TYPES);
        $riskLevels = array_keys(SecurityEvent::RISK_LEVELS);

        return [
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'event_description' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'location_data' => [
                'city' => $this->faker->city(),
                'region' => $this->faker->state(),
                'country' => $this->faker->country(),
                'lat' => $this->faker->latitude(),
                'lon' => $this->faker->longitude(),
            ],
            'risk_level' => $this->faker->randomElement($riskLevels),
            'is_resolved' => $this->faker->boolean(30), // 30% chance of being resolved
            'resolved_at' => function (array $attributes) {
                return $attributes['is_resolved'] ? $this->faker->dateTimeBetween($attributes['created_at'] ?? '-1 week', 'now') : null;
            },
            'resolved_by' => function (array $attributes) {
                return $attributes['is_resolved'] ? User::factory() : null;
            },
            'additional_data' => function () {
                return $this->faker->boolean(50) ? [
                    'session_id' => $this->faker->uuid(),
                    'factors' => $this->faker->randomElements(['New location', 'Multiple sessions', 'Unusual time'], $this->faker->numberBetween(1, 3)),
                ] : null;
            },
        ];
    }

    /**
     * Indicate that the security event is high risk.
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => $this->faker->randomElement(['high', 'critical']),
        ]);
    }

    /**
     * Indicate that the security event is low risk.
     */
    public function lowRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'low',
        ]);
    }

    /**
     * Indicate that the security event is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
            'resolved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'resolved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the security event is unresolved.
     */
    public function unresolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => false,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    /**
     * Create a login success event.
     */
    public function loginSuccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'login_success',
            'event_description' => 'User logged in successfully',
            'risk_level' => 'low',
        ]);
    }

    /**
     * Create a login failed event.
     */
    public function loginFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'login_failed',
            'event_description' => 'Failed login attempt',
            'risk_level' => $this->faker->randomElement(['medium', 'high']),
        ]);
    }

    /**
     * Create a suspicious activity event.
     */
    public function suspiciousActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'suspicious_activity',
            'event_description' => 'Suspicious activity detected',
            'risk_level' => $this->faker->randomElement(['high', 'critical']),
            'additional_data' => [
                'factors' => $this->faker->randomElements([
                    'New geographic location',
                    'Multiple concurrent sessions',
                    'Unusual login time',
                    'New device or browser',
                ], $this->faker->numberBetween(1, 3)),
            ],
        ]);
    }

    /**
     * Create a password change event.
     */
    public function passwordChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'password_change',
            'event_description' => 'User changed password',
            'risk_level' => 'medium',
        ]);
    }
}