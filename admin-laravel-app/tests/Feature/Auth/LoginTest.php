<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SecurityMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login');
    }

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_invalid_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_users_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_suspended_users_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_email()
    {
        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_password()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_login_validates_email_format()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_rate_limiting()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_successful_login_updates_last_login_timestamp()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'last_login_at' => null,
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_logs_security_events()
    {
        $this->mock(SecurityMonitoringService::class, function ($mock) {
            $mock->shouldReceive('recordEvent')
                 ->with('failed_login', \Mockery::any())
                 ->once();
        });

        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_login_with_remember_me()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertCookie('remember_web_' . sha1(config('app.key')));
    }

    public function test_login_redirects_to_intended_url()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // First, try to access a protected route
        $this->get('/admin/users');

        // Then login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/users');
    }

    public function test_admin_users_can_access_admin_routes()
    {
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($adminUser);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_non_admin_users_cannot_access_admin_routes()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_csrf_protection_on_login()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], [
            'X-CSRF-TOKEN' => 'invalid-token',
        ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_login_form_has_csrf_token()
    {
        $response = $this->get('/login');

        $response->assertSee('csrf-token');
        $response->assertSee('_token');
    }

    public function test_login_sanitizes_input()
    {
        $response = $this->post('/login', [
            'email' => '<script>alert("xss")</script>test@example.com',
            'password' => 'password',
        ]);

        // Should not contain the script tag in the response
        $response->assertDontSee('<script>');
    }

    public function test_login_prevents_sql_injection()
    {
        $response = $this->post('/login', [
            'email' => "'; DROP TABLE users; --",
            'password' => 'password',
        ]);

        // Should handle the malicious input gracefully
        $response->assertSessionHasErrors('email');
        
        // Verify users table still exists
        $this->assertDatabaseCount('users', 0);
    }

    public function test_logout_functionality()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_logout_invalidates_session()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $sessionId = session()->getId();
        
        $this->post('/logout');
        
        $this->assertNotEquals($sessionId, session()->getId());
    }
}