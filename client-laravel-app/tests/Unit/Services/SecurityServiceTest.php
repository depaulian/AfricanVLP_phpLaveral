<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\SecurityEvent;
use App\Models\UserSession;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityService $securityService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = app(SecurityService::class);
        $this->user = User::factory()->create();
    }

    public function test_can_log_security_event(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->server->set('HTTP_USER_AGENT', 'Test Browser');

        $event = $this->securityService->logSecurityEvent(
            $this->user,
            'login_success',
            'User logged in successfully',
            $request,
            'low'
        );

        $this->assertInstanceOf(SecurityEvent::class, $event);
        $this->assertEquals($this->user->id, $event->user_id);
        $this->assertEquals('login_success', $event->event_type);
        $this->assertEquals('User logged in successfully', $event->event_description);
        $this->assertEquals('low', $event->risk_level);
        $this->assertEquals('192.168.1.1', $event->ip_address);
        $this->assertEquals('Test Browser', $event->user_agent);
    }

    public function test_can_create_user_session(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->server->set('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $session = $this->securityService->createSession($this->user, $request, 'test-session-id');

        $this->assertInstanceOf(UserSession::class, $session);
        $this->assertEquals($this->user->id, $session->user_id);
        $this->assertEquals('test-session-id', $session->session_id);
        $this->assertEquals('192.168.1.1', $session->ip_address);
        $this->assertTrue($session->is_current);
    }

    public function test_can_check_password_strength(): void
    {
        $weakPassword = '123456';
        $strongPassword = 'MyStr0ng!P@ssw0rd123';

        $weakResult = $this->securityService->checkPasswordStrength($weakPassword);
        $strongResult = $this->securityService->checkPasswordStrength($strongPassword);

        $this->assertEquals('weak', $weakResult['strength']);
        $this->assertGreaterThan(0, count($weakResult['feedback']));

        $this->assertEquals('strong', $strongResult['strength']);
        $this->assertLessThanOrEqual(1, count($strongResult['feedback']));
    }

    public function test_can_generate_backup_codes(): void
    {
        $codes = $this->securityService->generateBackupCodes(5);

        $this->assertCount(5, $codes);
        
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        }

        // Ensure codes are unique
        $this->assertEquals(5, count(array_unique($codes)));
    }

    public function test_can_get_security_recommendations(): void
    {
        $recommendations = $this->securityService->getSecurityRecommendations($this->user);

        $this->assertIsArray($recommendations);
        
        // Should recommend 2FA since user doesn't have it enabled
        $twoFactorRecommendation = collect($recommendations)->firstWhere('type', 'two_factor');
        $this->assertNotNull($twoFactorRecommendation);
        $this->assertEquals('high', $twoFactorRecommendation['priority']);
    }

    public function test_can_terminate_session(): void
    {
        $session = UserSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-session-id',
            'expires_at' => now()->addHours(2),
        ]);

        $result = $this->securityService->terminateSession($this->user, 'test-session-id');

        $this->assertTrue($result);
        
        $session->refresh();
        $this->assertFalse($session->is_current);
        $this->assertTrue($session->expires_at->isPast());
    }

    public function test_can_terminate_all_other_sessions(): void
    {
        // Create multiple sessions
        UserSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(2),
        ]);

        $currentSessionId = 'current-session';
        UserSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => $currentSessionId,
            'expires_at' => now()->addHours(2),
        ]);

        $count = $this->securityService->terminateAllOtherSessions($this->user, $currentSessionId);

        $this->assertEquals(3, $count);
        
        // Check that only the current session is still active
        $activeSessions = UserSession::where('user_id', $this->user->id)
            ->active()
            ->count();
        
        $this->assertEquals(1, $activeSessions);
    }

    public function test_detects_suspicious_activity(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '1.2.3.4'); // Different IP
        $request->server->set('HTTP_USER_AGENT', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');

        // Create multiple sessions to trigger suspicious activity
        UserSession::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(2),
        ]);

        $session = $this->securityService->createSession($this->user, $request, 'new-session-id');

        // Should have logged suspicious activity
        $suspiciousEvents = SecurityEvent::where('user_id', $this->user->id)
            ->where('event_type', 'suspicious_activity')
            ->count();

        $this->assertGreaterThan(0, $suspiciousEvents);
    }

    public function test_can_cleanup_old_data(): void
    {
        // Create old security events
        SecurityEvent::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'risk_level' => 'low',
            'created_at' => now()->subMonths(7),
        ]);

        // Create old expired sessions
        UserSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subDays(35),
            'created_at' => now()->subDays(35),
        ]);

        $result = $this->securityService->cleanupOldData();

        $this->assertEquals(5, $result['deleted_events']);
        $this->assertEquals(3, $result['deleted_sessions']);
    }

    public function test_is_account_locked_detection(): void
    {
        // Create multiple failed login attempts
        SecurityEvent::factory()->count(6)->create([
            'user_id' => $this->user->id,
            'event_type' => 'login_failed',
            'created_at' => now()->subMinutes(30),
        ]);

        $isLocked = $this->securityService->isAccountLocked($this->user);

        $this->assertTrue($isLocked);
    }

    public function test_account_not_locked_with_few_attempts(): void
    {
        // Create only a few failed login attempts
        SecurityEvent::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'event_type' => 'login_failed',
            'created_at' => now()->subMinutes(30),
        ]);

        $isLocked = $this->securityService->isAccountLocked($this->user);

        $this->assertFalse($isLocked);
    }
}