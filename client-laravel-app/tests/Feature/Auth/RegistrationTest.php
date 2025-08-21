<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SecurityMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    public function test_new_users_can_register()
    {
        Event::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Event::assertDispatched(Registered::class);
    }

    public function test_registration_requires_name()
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_registration_requires_email()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_requires_valid_email()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_requires_unique_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_requires_password()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_requires_password_confirmation()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_requires_matching_password_confirmation()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_requires_strong_password()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_requires_terms_acceptance()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('terms');
        $this->assertGuest();
    }

    public function test_registration_hashes_password()
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertNotEquals('Password123!', $user->password);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_registration_sets_default_status()
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertEquals('active', $user->status);
    }

    public function test_registration_sets_default_role()
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertEquals('user', $user->role);
    }

    public function test_registration_with_optional_fields()
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '+1234567890',
            'bio' => 'This is my bio',
            'location' => 'New York, NY',
            'terms' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'bio' => 'This is my bio',
            'location' => 'New York, NY',
        ]);
    }

    public function test_registration_with_volunteering_interests()
    {
        $interests = ['education', 'environment', 'health'];

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'volunteering_interests' => $interests,
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertEquals($interests, $user->volunteering_interests);
    }

    public function test_registration_with_skills()
    {
        $skills = ['PHP', 'Laravel', 'JavaScript'];

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'skills' => $skills,
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertEquals($skills, $user->skills);
    }

    public function test_registration_sanitizes_input()
    {
        $this->post('/register', [
            'name' => '<script>alert("xss")</script>Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'bio' => '<script>alert("xss")</script>My bio',
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertStringNotContainsString('<script>', $user->name);
        $this->assertStringNotContainsString('<script>', $user->bio);
    }

    public function test_registration_rate_limiting()
    {
        // Make multiple registration attempts from same IP
        for ($i = 0; $i < 4; $i++) {
            $this->post('/register', [
                'name' => "Test User {$i}",
                'email' => "test{$i}@example.com",
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'terms' => true,
            ]);
        }

        // 4th attempt should be rate limited
        $response = $this->post('/register', [
            'name' => 'Test User 4',
            'email' => 'test4@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_registration_sends_verification_email()
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        Mail::assertSent(\Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_registration_logs_security_events()
    {
        $this->mock(SecurityMonitoringService::class, function ($mock) {
            $mock->shouldReceive('recordEvent')
                 ->with('user_registered', \Mockery::any())
                 ->once();
        });

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);
    }

    public function test_registration_form_has_csrf_protection()
    {
        $response = $this->get('/register');

        $response->assertSee('csrf-token');
        $response->assertSee('_token');
    }

    public function test_registration_form_has_honeypot_field()
    {
        $response = $this->get('/register');

        $response->assertSee('name="honeypot"', false);
    }

    public function test_registration_blocks_honeypot_submissions()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
            'honeypot' => 'bot-filled-this',
        ]);

        $response->assertSessionHasErrors('honeypot');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_validates_phone_format()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => 'invalid-phone',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_registration_limits_bio_length()
    {
        $longBio = str_repeat('a', 1001); // Assuming 1000 char limit

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'bio' => $longBio,
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('bio');
        $this->assertGuest();
    }
}