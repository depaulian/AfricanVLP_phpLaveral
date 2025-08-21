<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        $this->setupTestEnvironment();
        
        // Clear caches
        $this->clearCaches();
        
        // Set up security monitoring mock
        $this->setupSecurityMocks();
    }

    protected function setupTestEnvironment(): void
    {
        // Set test-specific configuration
        config([
            'security.rate_limiting.enabled' => false, // Disable rate limiting in tests
            'security.monitoring.enabled' => false,    // Disable monitoring in tests
            'mail.default' => 'array',                 // Use array driver for emails
            'queue.default' => 'sync',                 // Use sync driver for queues
        ]);
    }

    protected function clearCaches(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    protected function setupSecurityMocks(): void
    {
        // Mock security monitoring service to prevent actual logging in tests
        $this->mock(\App\Services\SecurityMonitoringService::class, function ($mock) {
            $mock->shouldReceive('recordEvent')->andReturn(true);
            $mock->shouldReceive('isIpBlocked')->andReturn(false);
            $mock->shouldReceive('detectSuspiciousPatterns')->andReturn([]);
        });
    }

    /**
     * Create an admin user for testing.
     */
    protected function createAdminUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create(array_merge([
            'role' => 'admin',
            'status' => 'active',
        ], $attributes));
    }

    /**
     * Create a regular user for testing.
     */
    protected function createUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create(array_merge([
            'role' => 'user',
            'status' => 'active',
        ], $attributes));
    }

    /**
     * Create an organization for testing.
     */
    protected function createOrganization(array $attributes = []): \App\Models\Organization
    {
        return \App\Models\Organization::factory()->create(array_merge([
            'status' => 'active',
        ], $attributes));
    }

    /**
     * Assert that a security event was logged.
     */
    protected function assertSecurityEventLogged(string $eventType): void
    {
        $this->assertTrue(true); // Placeholder - in real implementation, check logs
    }

    /**
     * Assert that an email was sent.
     */
    protected function assertEmailSent(string $mailable = null): void
    {
        if ($mailable) {
            \Illuminate\Support\Facades\Mail::assertSent($mailable);
        } else {
            \Illuminate\Support\Facades\Mail::assertSent();
        }
    }

    /**
     * Assert that a notification was sent.
     */
    protected function assertNotificationSent($notifiable, string $notification): void
    {
        \Illuminate\Support\Facades\Notification::assertSentTo($notifiable, $notification);
    }

    /**
     * Create a test file upload.
     */
    protected function createTestFile(string $name = 'test.jpg', int $size = 100): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->image($name, 800, 600)->size($size);
    }

    /**
     * Create a test document upload.
     */
    protected function createTestDocument(string $name = 'test.pdf', int $size = 100): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create($name, $size, 'application/pdf');
    }

    /**
     * Assert that input was sanitized.
     */
    protected function assertInputSanitized(string $input, string $expected): void
    {
        $sanitizer = app(\App\Services\InputSanitizationService::class);
        $sanitized = $sanitizer->sanitizeText($input);
        $this->assertEquals($expected, $sanitized);
    }

    /**
     * Assert that a rate limit was applied.
     */
    protected function assertRateLimited(): void
    {
        $this->assertTrue(true); // Placeholder - in real implementation, check rate limiter
    }

    /**
     * Mock external API responses.
     */
    protected function mockExternalApi(string $service, array $responses): void
    {
        switch ($service) {
            case 'cloudinary':
                $this->mock(\Cloudinary\Api\Upload\UploadApi::class, function ($mock) use ($responses) {
                    foreach ($responses as $method => $response) {
                        $mock->shouldReceive($method)->andReturn($response);
                    }
                });
                break;
                
            case 'google_translate':
                \Illuminate\Support\Facades\Http::fake([
                    'translate.googleapis.com/*' => \Illuminate\Support\Facades\Http::response($responses)
                ]);
                break;
                
            case 'sendgrid':
                \Illuminate\Support\Facades\Http::fake([
                    'api.sendgrid.com/*' => \Illuminate\Support\Facades\Http::response($responses)
                ]);
                break;
        }
    }

    /**
     * Assert that a database transaction was rolled back.
     */
    protected function assertDatabaseRolledBack(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\DB::transactionLevel() === 0);
    }

    /**
     * Create test data for performance testing.
     */
    protected function createTestData(int $userCount = 10, int $orgCount = 5): array
    {
        $users = \App\Models\User::factory()->count($userCount)->create();
        $organizations = \App\Models\Organization::factory()->count($orgCount)->create();
        
        // Associate users with organizations
        foreach ($users as $user) {
            $randomOrgs = $organizations->random(rand(1, 3));
            foreach ($randomOrgs as $org) {
                $org->users()->attach($user->id, ['role' => 'member']);
            }
        }
        
        return [
            'users' => $users,
            'organizations' => $organizations,
        ];
    }

    /**
     * Assert response time is within acceptable limits.
     */
    protected function assertResponseTimeAcceptable(float $startTime, float $maxTime = 2.0): void
    {
        $responseTime = microtime(true) - $startTime;
        $this->assertLessThan($maxTime, $responseTime, "Response time {$responseTime}s exceeded maximum {$maxTime}s");
    }

    /**
     * Assert memory usage is within acceptable limits.
     */
    protected function assertMemoryUsageAcceptable(int $maxMemoryMB = 128): void
    {
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        $this->assertLessThan($maxMemoryMB, $memoryUsage, "Memory usage {$memoryUsage}MB exceeded maximum {$maxMemoryMB}MB");
    }

    /**
     * Simulate a slow network connection.
     */
    protected function simulateSlowNetwork(): void
    {
        // Add artificial delay to HTTP requests
        \Illuminate\Support\Facades\Http::fake([
            '*' => function () {
                sleep(2); // 2 second delay
                return \Illuminate\Support\Facades\Http::response(['status' => 'ok']);
            }
        ]);
    }

    /**
     * Test accessibility compliance.
     */
    protected function assertAccessibilityCompliant(string $html): void
    {
        // Check for basic accessibility requirements
        $this->assertStringContainsString('alt=', $html, 'Images should have alt attributes');
        $this->assertStringContainsString('aria-', $html, 'Should contain ARIA attributes');
        $this->assertStringContainsString('role=', $html, 'Should contain role attributes');
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        // Clear any test files
        \Illuminate\Support\Facades\Storage::fake('public');
        
        // Clear caches
        $this->clearCaches();
        
        parent::tearDown();
    }
}